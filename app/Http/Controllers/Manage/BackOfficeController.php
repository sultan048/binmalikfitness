<?php

namespace App\Http\Controllers\Manage;

use App\Domain\Identity\CoachAssignmentService;
use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Identity\UserManagementService;
use App\Domain\Platform\AnalyticsAndReportingService;
use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\CmsPage;
use App\Models\CoachAssignment;
use App\Models\CoachingCheckIn;
use App\Models\CoachingConsultation;
use App\Models\CoachingIntake;
use App\Models\CoachingMessage;
use App\Models\CoachingPackage;
use App\Models\CoachingSubscription;
use App\Models\ConsentRecord;
use App\Models\Coupon;
use App\Models\DietPlan;
use App\Models\Enquiry;
use App\Models\GymCheckIn;
use App\Models\GymClass;
use App\Models\GymHoliday;
use App\Models\GymMembership;
use App\Models\InventoryMovement;
use App\Models\MediaAsset;
use App\Models\MembershipPlan;
use App\Models\NotificationTemplate;
use App\Models\OpeningHour;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\Payment;
use App\Models\PersonalisedPlanRequest;
use App\Models\PrivacyRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\RoleNotification;
use App\Models\SeoMetadata;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WorkoutProgram;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BackOfficeController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AnalyticsAndReportingService $analytics,
    ) {}

    public function adminDashboard(Request $request): View
    {
        $from = $request->filled('date_from')
            ? Carbon::parse((string) $request->query('date_from'))->startOfDay()
            : now()->subDays(90)->startOfDay();

        $to = $request->filled('date_to')
            ? Carbon::parse((string) $request->query('date_to'))->endOfDay()
            : now()->endOfDay();

        $orderSearch = trim((string) $request->query('order_search', ''));
        $orderStatus = trim((string) $request->query('order_status', ''));
        $ticketStatus = trim((string) $request->query('ticket_status', ''));

        $ordersQuery = Order::with(['items', 'payments.proofFile', 'refunds', 'returnRequests'])->latest('placed_at');
        if ($orderSearch !== '') {
            $ordersQuery->where(function ($q) use ($orderSearch) {
                $q->where('order_number', 'like', "%{$orderSearch}%")
                    ->orWhere('customer_name', 'like', "%{$orderSearch}%")
                    ->orWhere('customer_email', 'like', "%{$orderSearch}%");
            });
        }
        if ($orderStatus !== '') {
            $ordersQuery->where('status', $orderStatus);
        }

        $ticketsQuery = SupportTicket::with(['user', 'assignee', 'order', 'messages.sender'])->latest('last_reply_at');
        if ($ticketStatus !== '') {
            $ticketsQuery->where('status', $ticketStatus);
        }

        $users = User::with(['roles', 'staffProfile', 'customerProfile'])->orderBy('id')->get();
        $coaches = User::role([RolePermissionMatrix::ROLE_COACH, RolePermissionMatrix::ROLE_MANAGER, RolePermissionMatrix::ROLE_ADMIN])->orderBy('name')->get();
        $customers = User::role(RolePermissionMatrix::ROLE_CUSTOMER)->orderBy('name')->get();
        $assignments = CoachAssignment::with(['coach', 'client'])
            ->whereNull('ends_at')
            ->latest('starts_at')
            ->get();

        return view('manage.admin-dashboard', [
            'users' => $users,
            'coaches' => $coaches,
            'customers' => $customers,
            'assignments' => $assignments,
            'auditLogs' => AuditLog::with('actor')->latest('created_at')->take(15)->get(),
            'productsCount' => Product::count(),
            'categoriesCount' => ProductCategory::count(),
            'coachingPackagesCount' => CoachingPackage::count(),
            'dietPlansCount' => DietPlan::count(),
            'permissionsCount' => count(RolePermissionMatrix::permissions()),
            'roleMap' => RolePermissionMatrix::roleMap(),
            'gymCity' => Setting::getValue('gym.city', ''),
            'gymAddress' => Setting::getValue('gym.address', ''),
            'gymPhone' => Setting::getValue('gym.phone', ''),
            'categories' => ProductCategory::orderBy('sort_order')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'products' => Product::with(['category', 'brand', 'variants.batches', 'documents'])->orderBy('id')->get(),
            'variants' => ProductVariant::with(['product', 'batches'])->orderBy('id')->get(),
            'orders' => $ordersQuery->get(),
            'coupons' => Coupon::latest('id')->get(),
            'reviews' => ProductReview::with(['product', 'user', 'order'])->latest('id')->get(),
            'returnRequests' => OrderReturnRequest::with(['order', 'user'])->latest('id')->get(),
            'recentMovements' => InventoryMovement::with(['variant.product', 'actor', 'batch'])->latest('created_at')->take(12)->get(),
            'coachingPackages' => CoachingPackage::orderBy('sort_order')->get(),
            'dietPlans' => DietPlan::with('versions')->orderBy('id')->get(),
            'workoutPrograms' => WorkoutProgram::orderBy('id')->get(),
            'subscriptions' => CoachingSubscription::with(['user', 'package', 'coach'])->latest('id')->get(),
            'intakes' => CoachingIntake::with(['user', 'reviewer'])->latest('id')->get(),
            'planRequests' => PersonalisedPlanRequest::with(['user', 'dietPlan', 'intake', 'deliveredVersion', 'assignedCoach'])->latest('id')->get(),
            'checkIns' => CoachingCheckIn::with(['user', 'reviewer'])->latest('check_in_date')->take(15)->get(),
            'consultations' => CoachingConsultation::with(['user', 'coach'])->orderBy('scheduled_at')->get(),
            'membershipPlans' => MembershipPlan::orderBy('sort_order')->get(),
            'gymMemberships' => GymMembership::with(['user', 'plan', 'assignedTrainer', 'order'])->latest('id')->get(),
            'gymCheckIns' => GymCheckIn::with(['user', 'membership', 'recorder'])->latest('checked_in_at')->take(15)->get(),
            'gymClasses' => GymClass::with('instructor')->orderBy('day_of_week')->orderBy('starts_at_time')->get(),
            'openingHours' => OpeningHour::orderBy('day_of_week')->get(),
            'gymHolidays' => GymHoliday::orderBy('holiday_date')->get(),
            'enquiries' => Enquiry::with(['assignee', 'interestedPlan'])->latest('id')->take(15)->get(),
            'verifiedGymRevenuePaisa' => Order::where('business_line', 'gym')->whereIn('payment_status', AnalyticsAndReportingService::VERIFIED_PAYMENT_STATUSES)->sum('total_paisa'),
            'financialSummary' => $this->analytics->getReconciledFinancialSummary($from, $to),
            'operationalMetrics' => $this->analytics->getOperationalMetrics($from, $to),
            'systemHealth' => $this->analytics->getSystemHealthSummary(),
            'supportTickets' => $ticketsQuery->get(),
            'cmsPages' => CmsPage::with('editor')->orderBy('section')->orderBy('title')->get(),
            'mediaAssets' => MediaAsset::with('uploader')->latest('id')->get(),
            'testimonials' => Testimonial::with('consentRecord.user')->latest('id')->get(),
            'consentRecords' => ConsentRecord::with('user')->whereNull('withdrawn_at')->latest('granted_at')->get(),
            'seoMetadataRecords' => SeoMetadata::latest('id')->get(),
            'notificationTemplates' => NotificationTemplate::orderBy('key')->get(),
            'privacyRequests' => PrivacyRequest::with(['user', 'processor'])->latest('id')->get(),
            'roleNotifications' => RoleNotification::where('user_id', $request->user()->id)
                ->orWhere('role_scope', 'admin')
                ->latest('created_at')
                ->take(10)
                ->get(),
            'filterDateFrom' => $from->toDateString(),
            'filterDateTo' => $to->toDateString(),
            'filterOrderSearch' => $orderSearch,
            'filterOrderStatus' => $orderStatus,
            'filterTicketStatus' => $ticketStatus,
        ]);
    }

    public function managerDashboard(Request $request): View
    {
        $actor = $request->user();

        $from = $request->filled('date_from')
            ? Carbon::parse((string) $request->query('date_from'))->startOfDay()
            : now()->subDays(90)->startOfDay();

        $to = $request->filled('date_to')
            ? Carbon::parse((string) $request->query('date_to'))->endOfDay()
            : now()->endOfDay();

        $orderSearch = trim((string) $request->query('order_search', ''));
        $ticketStatus = trim((string) $request->query('ticket_status', ''));

        $assignedCustomerIds = CoachAssignment::query()
            ->where('coach_id', $actor->id)
            ->whereNull('ends_at')
            ->pluck('client_id');

        $assignedClients = User::with(['customerProfile', 'privateFiles'])
            ->whereIn('id', $assignedCustomerIds)
            ->get();

        $customersForStatus = User::role(RolePermissionMatrix::ROLE_CUSTOMER)->orderBy('name')->get();
        $staffTrainers = User::role([RolePermissionMatrix::ROLE_COACH, RolePermissionMatrix::ROLE_MANAGER])->orderBy('name')->get();

        $ordersQuery = Order::with(['items', 'payments.proofFile', 'returnRequests'])->latest('placed_at');
        if ($orderSearch !== '') {
            $ordersQuery->where(function ($q) use ($orderSearch) {
                $q->where('order_number', 'like', "%{$orderSearch}%")
                    ->orWhere('customer_name', 'like', "%{$orderSearch}%");
            });
        }

        $ticketsQuery = SupportTicket::with(['user', 'assignee', 'order', 'messages.sender'])->latest('last_reply_at');
        if ($actor->hasRole(RolePermissionMatrix::ROLE_COACH) && ! $actor->hasAnyRole([RolePermissionMatrix::ROLE_ADMIN, RolePermissionMatrix::ROLE_MANAGER])) {
            $ticketsQuery->where(function ($q) use ($actor, $assignedCustomerIds) {
                $q->where('assigned_to', $actor->id)
                    ->orWhereIn('user_id', $assignedCustomerIds);
            });
        }
        if ($ticketStatus !== '') {
            $ticketsQuery->where('status', $ticketStatus);
        }

        return view('manage.manager-dashboard', [
            'actor' => $actor,
            'assignedClients' => $assignedClients,
            'customersForStatus' => $customersForStatus,
            'staffTrainers' => $staffTrainers,
            'enquiries' => Enquiry::with(['assignee', 'interestedPlan'])->latest()->take(12)->get(),
            'managerPermissions' => RolePermissionMatrix::roleMap()[RolePermissionMatrix::ROLE_MANAGER],
            'products' => Product::with(['defaultVariant', 'variants.batches'])->get(),
            'variants' => ProductVariant::with(['product', 'batches'])->orderBy('id')->get(),
            'orders' => $ordersQuery->get(),
            'coupons' => Coupon::latest('id')->get(),
            'returnRequests' => OrderReturnRequest::with(['order', 'user'])->latest('id')->get(),
            'recentMovements' => InventoryMovement::with(['variant.product', 'actor'])->latest('created_at')->take(8)->get(),
            'workoutPrograms' => WorkoutProgram::orderBy('id')->get(),
            'membershipPlans' => MembershipPlan::where('status', 'active')->orderBy('sort_order')->get(),
            'gymMemberships' => GymMembership::with(['user', 'plan', 'assignedTrainer', 'order'])->latest('id')->get(),
            'gymCheckIns' => GymCheckIn::with(['user', 'membership', 'recorder'])->latest('checked_in_at')->take(12)->get(),
            'gymClasses' => GymClass::with('instructor')->orderBy('day_of_week')->orderBy('starts_at_time')->get(),
            'assignedSubscriptions' => CoachingSubscription::with(['user', 'package'])
                ->whereIn('user_id', $assignedCustomerIds)
                ->latest('id')
                ->get(),
            'assignedIntakes' => CoachingIntake::with(['user', 'reviewer'])
                ->whereIn('user_id', $assignedCustomerIds)
                ->latest('id')
                ->get(),
            'assignedPlanRequests' => PersonalisedPlanRequest::with(['user', 'dietPlan', 'intake', 'deliveredVersion'])
                ->whereIn('user_id', $assignedCustomerIds)
                ->latest('id')
                ->get(),
            'assignedCheckIns' => CoachingCheckIn::with(['user', 'reviewer'])
                ->whereIn('user_id', $assignedCustomerIds)
                ->latest('check_in_date')
                ->get(),
            'assignedConsultations' => CoachingConsultation::with(['user', 'coach'])
                ->whereIn('user_id', $assignedCustomerIds)
                ->orderBy('scheduled_at')
                ->get(),
            'assignedMessages' => CoachingMessage::with(['client', 'sender'])
                ->whereIn('client_id', $assignedCustomerIds)
                ->latest('created_at')
                ->take(15)
                ->get(),
            'operationalMetrics' => $this->analytics->getOperationalMetrics($from, $to),
            'supportTickets' => $ticketsQuery->get(),
            'roleNotifications' => RoleNotification::where('user_id', $actor->id)
                ->orWhereIn('role_scope', ['manager', 'coach'])
                ->latest('created_at')
                ->take(10)
                ->get(),
            'filterDateFrom' => $from->toDateString(),
            'filterDateTo' => $to->toDateString(),
            'filterOrderSearch' => $orderSearch,
            'filterTicketStatus' => $ticketStatus,
        ]);
    }

    public function viewClientConfidential(Request $request, User $client): View
    {
        Gate::authorize('viewConfidential', $client);

        $client->load(['customerProfile', 'privateFiles', 'consentRecords']);

        $this->audit->record(
            action: 'health.client_record.viewed',
            subject: $client,
            oldValues: null,
            newValues: ['client_id' => $client->id],
            actor: $request->user(),
        );

        return view('manage.client-confidential', [
            'client' => $client,
        ]);
    }

    public function updateUserRoles(Request $request, User $user, UserManagementService $userMgmt): RedirectResponse
    {
        Gate::authorize('manageRoles', $user);

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'in:admin,manager,coach,customer'],
        ]);

        $userMgmt->syncUserRoles($request->user(), $user, $validated['roles']);

        return back()->with('status', "Updated roles for {$user->name}.");
    }

    public function toggleUserStatus(Request $request, User $user, UserManagementService $userMgmt): RedirectResponse
    {
        Gate::authorize('toggleStatus', $user);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,deactivated'],
        ]);

        $userMgmt->setAccountStatus($request->user(), $user, $validated['status']);

        return back()->with('status', "Account status for {$user->name} set to {$validated['status']}.");
    }

    public function assignCoach(Request $request, CoachAssignmentService $coachService): RedirectResponse
    {
        if (! $request->user()->can('coaching.subscriptions.manage')) {
            abort(403, 'You are not authorized to assign coaches.');
        }

        $validated = $request->validate([
            'coach_id' => ['required', 'exists:users,id'],
            'client_id' => ['required', 'exists:users,id'],
            'context' => ['required', 'string', 'in:coaching,diet,gym'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $coach = User::findOrFail($validated['coach_id']);
        $client = User::findOrFail($validated['client_id']);

        // Assignment unlocks a client's confidential health data, so non-admins may not grant it to themselves.
        if ($coach->is($request->user()) && ! $request->user()->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            abort(403, 'Only administrators may assign themselves to a client.');
        }

        if (! $client->hasRole(RolePermissionMatrix::ROLE_CUSTOMER)) {
            throw ValidationException::withMessages([
                'client_id' => ['Coaches can only be assigned to customer accounts.'],
            ]);
        }

        $coachService->assign(
            actor: $request->user(),
            coach: $coach,
            client: $client,
            context: $validated['context'],
            notes: $validated['notes'] ?? null,
        );

        return back()->with('status', "Assigned {$coach->name} to {$client->name} ({$validated['context']}).");
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        if (! $request->user()->can('settings.manage')) {
            abort(403, 'Managers are not permitted to modify unrestricted system settings.');
        }

        $validated = $request->validate([
            'gym_city' => ['required', 'string', 'max:120'],
            'gym_address' => ['required', 'string', 'max:255'],
            'gym_phone' => ['required', 'string', 'max:40'],
        ]);

        Setting::updateOrCreate(['key' => 'gym.city'], ['group' => 'gym', 'value' => $validated['gym_city'], 'is_demo' => false]);
        Setting::updateOrCreate(['key' => 'gym.address'], ['group' => 'gym', 'value' => $validated['gym_address'], 'is_demo' => false]);
        Setting::updateOrCreate(['key' => 'gym.phone'], ['group' => 'gym', 'value' => $validated['gym_phone'], 'is_demo' => false]);

        $this->audit->record('platform.settings.updated', null, null, $validated, $request->user());

        return back()->with('status', 'Platform facility settings updated and audited.');
    }
}
