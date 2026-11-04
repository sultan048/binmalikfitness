<?php

namespace App\Http\Controllers\Account;

use App\Domain\Engagement\SupportAndOperationsService;
use App\Domain\Gym\GymManagementService;
use App\Domain\Media\PrivateFileService;
use App\Domain\Platform\AuditLogger;
use App\Domain\Platform\EntitlementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Address;
use App\Models\CoachingCheckIn;
use App\Models\CoachingConsultation;
use App\Models\CoachingIntake;
use App\Models\CoachingMessage;
use App\Models\CoachingPackage;
use App\Models\CoachingSubscription;
use App\Models\ConsentRecord;
use App\Models\CustomerProfile;
use App\Models\DietPlan;
use App\Models\Entitlement;
use App\Models\GymCheckIn;
use App\Models\GymClassBooking;
use App\Models\GymMembership;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\PersonalisedPlanRequest;
use App\Models\PrivacyRequest;
use App\Models\RoleNotification;
use App\Models\SupportTicket;
use App\Models\Video;
use App\Models\WishlistItem;
use App\Models\WorkoutAssignment;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerAccountController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function dashboard(Request $request, EntitlementService $entitlements, GymManagementService $gymService): View
    {
        $user = $request->user()->load([
            'customerProfile',
            'addresses',
            'privateFiles',
            'coachAssignmentsAsClient.coach.staffProfile',
        ]);

        $activeSubscription = CoachingSubscription::with(['package', 'coach.staffProfile'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $checkIns = CoachingCheckIn::with(['reviewer', 'progressPhoto'])
            ->where('user_id', $user->id)
            ->orderByDesc('check_in_date')
            ->get();

        $videos = Video::orderBy('access')->orderBy('id')->get();
        $entitledVideoIds = [];
        foreach ($videos as $video) {
            if ($entitlements->allows($user, $video)) {
                $entitledVideoIds[] = $video->id;
            }
        }

        $gymMembership = GymMembership::with(['plan', 'assignedTrainer', 'order'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $qrCheckInToken = ($gymMembership && $gymMembership->isCurrentlyEligible())
            ? $gymService->generateQrCheckInToken($gymMembership)
            : null;

        return view('account.dashboard', [
            'user' => $user,
            'orders' => Order::with(['items', 'payments', 'refunds', 'returnRequests'])
                ->where('user_id', $user->id)
                ->latest('placed_at')
                ->get(),
            'wishlistItems' => WishlistItem::with(['product.defaultVariant', 'product.primaryImage'])
                ->where('user_id', $user->id)
                ->latest()
                ->get(),
            'activeSubscription' => $activeSubscription,
            'latestIntake' => CoachingIntake::where('user_id', $user->id)->latest('id')->first(),
            'planRequests' => PersonalisedPlanRequest::with(['dietPlan', 'deliveredVersion', 'assignedCoach'])
                ->where('user_id', $user->id)
                ->latest('id')
                ->get(),
            'workoutAssignments' => WorkoutAssignment::with(['program', 'assignedBy'])
                ->where('user_id', $user->id)
                ->latest('id')
                ->get(),
            'checkIns' => $checkIns,
            'consultations' => CoachingConsultation::with('coach')
                ->where('user_id', $user->id)
                ->orderBy('scheduled_at')
                ->get(),
            'coachingMessages' => CoachingMessage::with('sender')
                ->where('client_id', $user->id)
                ->orderBy('created_at')
                ->get(),
            'entitlements' => Entitlement::with('entitleable')
                ->where('user_id', $user->id)
                ->where('status', Entitlement::STATUS_ACTIVE)
                ->latest('id')
                ->get(),
            'videos' => $videos,
            'entitledVideoIds' => $entitledVideoIds,
            'gymMembership' => $gymMembership,
            'qrCheckInToken' => $qrCheckInToken,
            'gymCheckIns' => GymCheckIn::with('recorder')
                ->where('user_id', $user->id)
                ->orderByDesc('checked_in_at')
                ->take(10)
                ->get(),
            'gymClassBookings' => GymClassBooking::with(['gymClass.instructor'])
                ->where('user_id', $user->id)
                ->where('status', 'booked')
                ->orderByDesc('booked_at')
                ->get(),
            'membershipPlans' => MembershipPlan::where('status', 'active')->orderBy('sort_order')->get(),
            'featuredPackage' => CoachingPackage::where('is_featured', true)->first(),
            'featuredDiet' => DietPlan::where('is_featured', true)->first(),
            'membershipPlan' => MembershipPlan::where('is_featured', true)->first(),
            'supportTickets' => SupportTicket::with([
                'assignee',
                'order',
                'messages' => fn ($q) => $q->where('is_internal_note', false)->with('sender')->orderBy('created_at'),
            ])
                ->where('user_id', $user->id)
                ->latest('last_reply_at')
                ->get(),
            'roleNotifications' => RoleNotification::where('user_id', $user->id)
                ->orderByRaw('read_at IS NULL DESC')
                ->orderByDesc('created_at')
                ->take(15)
                ->get(),
            'consentRecords' => ConsentRecord::where('user_id', $user->id)
                ->orderByDesc('granted_at')
                ->get(),
            'privacyRequests' => PrivacyRequest::where('user_id', $user->id)
                ->latest('id')
                ->get(),
        ]);
    }

    /**
     * Customer's own order history as CSV (opens in Excel / Google Sheets).
     */
    public function exportOrders(Request $request): StreamedResponse
    {
        $user = $request->user();
        $filename = 'binmalikfitness-orders-'.now()->format('Y-m-d').'.csv';

        $this->audit->record('account.orders.exported', $user, null, null, $user);

        return response()->streamDownload(function () use ($user) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Urdu/PKR text correctly
            fputcsv($out, ['Order number', 'Placed at', 'Status', 'Payment method', 'Payment status', 'Items', 'Subtotal (PKR)', 'Discount (PKR)', 'Shipping (PKR)', 'Total (PKR)', 'Refunded (PKR)', 'Courier', 'Tracking number']);

            Order::with('items')
                ->where('user_id', $user->id)
                ->orderByDesc('placed_at')
                ->chunk(200, function ($orders) use ($out) {
                    foreach ($orders as $order) {
                        fputcsv($out, [
                            $order->order_number,
                            $order->placed_at?->format('Y-m-d H:i'),
                            $order->status,
                            $order->payment_method,
                            $order->payment_status,
                            $order->items->map(fn ($i) => $i->product_name_snapshot.' x'.$i->quantity)->implode('; '),
                            Money::toRupees($order->subtotal_paisa),
                            Money::toRupees($order->discount_paisa),
                            Money::toRupees($order->shipping_paisa),
                            Money::toRupees($order->total_paisa),
                            Money::toRupees($order->refunded_paisa),
                            $order->courier_name,
                            $order->tracking_number,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        CustomerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'fitness_goal' => $validated['fitness_goal'],
                'height_cm' => $validated['height_cm'] ?? null,
                'weight_kg' => $validated['weight_kg'] ?? null,
                'activity_level' => $validated['activity_level'] ?? null,
                'experience_level' => $validated['experience_level'] ?? null,
                'equipment_access' => $validated['equipment_access'] ?? null,
            ]
        );

        $this->audit->record('account.profile.updated', $user, null, ['fitness_goal' => $validated['fitness_goal']], $user);

        return back()->with('status', 'Profile and training parameters updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match your account.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->audit->record('account.password.updated', $user, null, null, $user);

        return back()->with('status', 'Your password has been updated.');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{10,20}$/'],
            'line1' => ['required', 'string', 'max:190'],
            'area' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:80'],
            'province' => ['required', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $address = $user->addresses()->create(array_merge($validated, [
            'is_default' => $user->addresses()->count() === 0,
        ]));

        $this->audit->record('account.address.created', $address, null, ['city' => $address->city], $user);

        return back()->with('status', 'Delivery address saved.');
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        Gate::authorize('delete', $address);

        $this->audit->record('account.address.deleted', $address, ['city' => $address->city], null, $request->user());
        $address->delete();

        return back()->with('status', 'Address removed.');
    }

    public function uploadPrivateFile(Request $request, PrivateFileService $fileService): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:intake_document,progress_photo,payment_receipt'],
            'consent_granted' => ['accepted'],
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $uploadedFile = $request->file('file');
        $originalName = strtolower($uploadedFile->getClientOriginalName());

        // Reject double extensions that contain executable script tokens
        if (preg_match('/\.(php|phtml|phar|exe|sh|bat|cmd|js|html|htm|svg)(\.|$)/i', $originalName)) {
            throw ValidationException::withMessages([
                'file' => ['Executable or script file names are strictly prohibited.'],
            ]);
        }

        $user = $request->user();
        $isHealth = in_array($validated['category'], ['intake_document', 'progress_photo'], true);
        $consentType = $validated['category'] === 'progress_photo' ? 'progress_photos_private' : 'health_intake';

        try {
            $fileService->store(
                uploader: $user,
                owner: $user,
                file: $uploadedFile,
                category: $validated['category'],
                isConfidentialHealth: $isHealth,
                consentType: $consentType,
                ipAddress: $request->ip(),
                userAgent: (string) $request->userAgent(),
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['file' => [$e->getMessage()]]);
        }

        return back()->with('status', 'File uploaded to encrypted/private storage with recorded consent.');
    }

    public function storeSupportTicket(Request $request, SupportAndOperationsService $supportService): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:order,coaching,diet,gym,billing,technical,general'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if (! empty($validated['order_id'])) {
            $ownsOrder = Order::where('id', $validated['order_id'])
                ->where('user_id', $request->user()->id)
                ->exists();
            if (! $ownsOrder) {
                abort(403, 'You may only link your own orders to a support ticket.');
            }
        }

        $ticket = $supportService->createTicket($request->user(), $validated);

        return back()->with('status', "Support Ticket {$ticket->ticket_number} created. Our team will respond shortly.");
    }

    public function replySupportTicket(
        Request $request,
        SupportTicket $ticket,
        SupportAndOperationsService $supportService
    ): RedirectResponse {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $supportService->replyToTicket(
            ticket: $ticket,
            actor: $request->user(),
            messageBody: $validated['message'],
            isInternalNote: false,
        );

        return back()->with('status', "Your reply was added to Ticket {$ticket->ticket_number}.");
    }

    public function markNotificationRead(Request $request, RoleNotification $notification): RedirectResponse
    {
        if ($notification->user_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            abort(403, 'You may only mark your own notifications as read.');
        }

        $notification->update(['read_at' => now()]);

        return back()->with('status', 'Notification marked as read.');
    }

    public function storePrivacyRequest(Request $request, SupportAndOperationsService $supportService): RedirectResponse
    {
        $validated = $request->validate([
            'request_type' => ['required', 'string', 'in:data_export,consent_withdrawal,account_deletion_enquiry'],
            'consent_record_id' => ['nullable', 'exists:consent_records,id'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $supportService->submitPrivacyRequest($request->user(), $validated);

        return back()->with('status', 'Privacy & data governance request submitted and logged.');
    }
}
