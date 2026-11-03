<?php

namespace App\Http\Controllers\Manage;

use App\Domain\Gym\GymManagementService;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\GymClass;
use App\Models\GymMembership;
use App\Models\MembershipPlan;
use App\Models\OpeningHour;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GymManagementController extends Controller
{
    public function __construct(
        private readonly GymManagementService $gymService
    ) {}

    public function registerAtCounter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'assigned_trainer_id' => ['nullable', 'exists:users,id'],
            'counter_receipt_ref' => ['nullable', 'string', 'max:100'],
            'verify_payment_immediately' => ['nullable', 'boolean'],
        ]);

        $member = User::findOrFail($validated['user_id']);
        $plan = MembershipPlan::findOrFail($validated['membership_plan_id']);
        $trainer = ! empty($validated['assigned_trainer_id'])
            ? User::find($validated['assigned_trainer_id'])
            : null;

        try {
            $membership = $this->gymService->registerOrRenewAtCounter(
                staff: $request->user(),
                member: $member,
                plan: $plan,
                assignedTrainer: $trainer,
                counterReceiptRef: (string) ($validated['counter_receipt_ref'] ?? ''),
                verifyPaymentImmediately: (bool) ($validated['verify_payment_immediately'] ?? true)
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['gym_counter' => $e->getMessage()]);
        }

        return back()->with('status', 'Counter registration/renewal processed for '.$member->name.' ('.$membership->membership_number.' · '.$membership->status.').');
    }

    public function updateMembership(Request $request, GymMembership $membership): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:active,paused,expired,cancelled'],
            'assigned_trainer_id' => ['nullable', 'exists:users,id'],
            'staff_notes' => ['nullable', 'string', 'max:500'],
            'send_expiry_reminder' => ['nullable', 'boolean'],
        ]);

        try {
            $this->gymService->updateMembershipByStaff($request->user(), $membership, $validated);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', 'Gym membership '.$membership->membership_number.' updated.');
    }

    public function recordCheckIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'check_in_mode' => ['required', 'in:qr,desk'],
            'qr_token' => ['nullable', 'required_if:check_in_mode,qr', 'string'],
            'member_identifier' => ['nullable', 'required_if:check_in_mode,desk', 'string', 'max:64'],
            'entry_note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            if ($validated['check_in_mode'] === 'qr') {
                $checkIn = $this->gymService->verifyQrCheckInByStaff(
                    staff: $request->user(),
                    qrToken: (string) $validated['qr_token'],
                    entryNote: $validated['entry_note'] ?? null
                );
            } else {
                $identifier = trim((string) $validated['member_identifier']);
                $membership = GymMembership::query()
                    ->where('member_card_code', $identifier)
                    ->orWhere('membership_number', $identifier)
                    ->orWhere('id', is_numeric($identifier) ? (int) $identifier : 0)
                    ->first();

                if (! $membership) {
                    return back()->withErrors(['gym_checkin' => 'No gym membership found matching "'.$identifier.'".']);
                }

                $checkIn = $this->gymService->recordDeskCheckIn(
                    staff: $request->user(),
                    membership: $membership,
                    entryNote: $validated['entry_note'] ?? null
                );
            }
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['gym_checkin' => $e->getMessage()]);
        }

        return back()->with('status', 'Check-in verified and recorded for membership #'.$checkIn->membership->membership_number.' ('.$checkIn->check_in_method.').');
    }

    public function saveClass(Request $request, ?GymClass $gymClass = null): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'category' => ['required', 'string', 'max:80'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'starts_at_time' => ['required', 'string'],
            'ends_at_time' => ['required', 'string'],
            'session_date' => ['nullable', 'date'],
            'room_or_zone' => ['nullable', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'requires_active_membership' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:scheduled,cancelled,completed'],
            'summary' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $saved = $this->gymService->saveGymClass($request->user(), $validated, $gymClass);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', 'Gym class "'.$saved->title.'" saved (Capacity: '.$saved->capacity.').');
    }

    public function savePlan(Request $request, ?MembershipPlan $plan = null): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:730'],
            'price_pkr' => ['required', 'numeric', 'min:500', 'max:500000'],
            'registration_fee_pkr' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'access_hours' => ['nullable', 'string', 'max:120'],
            'inclusions_csv' => ['nullable', 'string', 'max:600'],
            'status' => ['nullable', 'in:active,draft,archived'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        try {
            $saved = $this->gymService->saveMembershipPlan($request->user(), $validated, $plan);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', 'Membership package "'.$saved->name.'" configured.');
    }

    public function updateOpeningHour(Request $request, OpeningHour $hour): RedirectResponse
    {
        $validated = $request->validate([
            'opens_at' => ['nullable', 'string'],
            'closes_at' => ['nullable', 'string'],
            'ladies_only_window' => ['nullable', 'string', 'max:80'],
            'peak_window' => ['nullable', 'string', 'max:80'],
            'is_closed' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:190'],
        ]);

        try {
            $this->gymService->updateOpeningHour($request->user(), $hour, $validated);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', 'Opening hours updated for '.$hour->day_name.'.');
    }

    public function saveHoliday(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'holiday_date' => ['required', 'date'],
            'is_closed' => ['nullable', 'boolean'],
            'special_opens_at' => ['nullable', 'string'],
            'special_closes_at' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $holiday = $this->gymService->saveHoliday($request->user(), $validated);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', 'Holiday schedule saved for '.$holiday->holiday_date->format('d M Y').'.');
    }

    public function updateEnquiry(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,trial_scheduled,converted,closed'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'staff_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->gymService->updateEnquiryByStaff($request->user(), $enquiry, $validated);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', 'Enquiry / trial booking for '.$enquiry->name.' updated to '.$enquiry->status.'.');
    }
}
