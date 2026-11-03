<?php

namespace App\Http\Controllers\Public;

use App\Domain\Gym\GymManagementService;
use App\Http\Controllers\Controller;
use App\Models\GymClass;
use App\Models\GymClassBooking;
use App\Models\GymHoliday;
use App\Models\GymMembership;
use App\Models\MembershipPlan;
use App\Models\OpeningHour;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class GymController extends Controller
{
    public function __construct(
        private readonly GymManagementService $gymService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $plans = MembershipPlan::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $classes = GymClass::query()
            ->with(['instructor'])
            ->where('status', 'scheduled')
            ->orderBy('day_of_week')
            ->orderBy('starts_at_time')
            ->get();

        $openingHours = OpeningHour::query()->orderBy('day_of_week')->get();
        $holidays = GymHoliday::query()
            ->whereDate('holiday_date', '>=', now()->toDateString())
            ->orderBy('holiday_date')
            ->take(6)
            ->get();

        $activeMembership = $user
            ? GymMembership::with(['plan', 'assignedTrainer'])
                ->where('user_id', $user->id)
                ->where('status', GymMembership::STATUS_ACTIVE)
                ->latest('id')
                ->first()
            : null;

        $bookedClassIds = $user
            ? GymClassBooking::query()
                ->where('user_id', $user->id)
                ->where('status', 'booked')
                ->pluck('gym_class_id')
                ->all()
            : [];

        $trainers = User::role(['coach', 'manager'])
            ->with('staffProfile')
            ->where('status', 'active')
            ->get();

        $gymSettings = Setting::query()
            ->where('group', 'gym')
            ->pluck('value', 'key');

        return view('public.gym.index', [
            'plans' => $plans,
            'classes' => $classes,
            'openingHours' => $openingHours,
            'holidays' => $holidays,
            'activeMembership' => $activeMembership,
            'bookedClassIds' => $bookedClassIds,
            'trainers' => $trainers,
            'gymSettings' => $gymSettings,
        ]);
    }

    public function purchaseMembership(Request $request, string $slug): RedirectResponse
    {
        $plan = MembershipPlan::query()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer,cod,counter_cash'],
            'is_renewal' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $hasActive = GymMembership::query()
            ->where('user_id', $user->id)
            ->where('status', GymMembership::STATUS_ACTIVE)
            ->exists();

        try {
            $order = $this->gymService->createMembershipOrder(
                user: $user,
                plan: $plan,
                paymentMethod: $validated['payment_method'],
                isRenewal: (bool) ($validated['is_renewal'] ?? $hasActive)
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.confirmation', ['orderNumber' => $order->order_number, 'token' => $order->access_token])
            ->with('status', 'Gym membership order '.$order->order_number.' created. Membership activates as soon as payment is verified by staff.');
    }

    public function bookClass(Request $request, GymClass $gymClass): RedirectResponse
    {
        try {
            $this->gymService->bookClass($request->user(), $gymClass);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['class_booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Spot reserved in "'.$gymClass->title.'".');
    }

    public function cancelClassBooking(Request $request, GymClassBooking $booking): RedirectResponse
    {
        try {
            $this->gymService->cancelClassBooking($request->user(), $booking);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['class_booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Class booking cancelled and spot released.');
    }

    public function submitEnquiry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:gym_trial,general,membership'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['required', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:80'],
            'preferred_trial_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_time_window' => ['nullable', 'string', 'max:80'],
            'interested_membership_plan_id' => ['nullable', 'exists:membership_plans,id'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->gymService->submitEnquiryOrTrial($validated, $request->ip());

        return back()->with('status', 'Your gym enquiry / trial session request has been recorded. Our desk team will confirm your slot.');
    }
}
