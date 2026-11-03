<?php

namespace Tests\Feature;

use App\Domain\Gym\GymManagementService;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Models\GymCheckIn;
use App\Models\GymClass;
use App\Models\GymClassBooking;
use App\Models\GymMembership;
use App\Models\MembershipPlan;
use App\Models\OpeningHour;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageFiveGymManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_gym_page_renders_packages_classes_timings_holidays_and_trial_enquiry_submission(): void
    {
        $monthly = MembershipPlan::where('slug', 'monthly-gym-access-demo')->firstOrFail();

        $this->get(route('gym.index'))
            ->assertOk()
            ->assertSee('Monthly Gym Floor Access [DEMO]')
            ->assertSee('Quarterly Committed Membership [DEMO]')
            ->assertSee('Annual Executive Strength Membership [DEMO]')
            ->assertSee('Barbell Squat & Deadlift Technique Clinic [DEMO]')
            ->assertSee('Scheduled Equipment Calibration & Floor Maintenance [DEMO]');

        // Submit public trial session booking (no account required)
        $trialDate = now()->addDays(3)->toDateString();
        $this->post(route('gym.enquiries.store'), [
            'type' => 'gym_trial',
            'name' => 'Bilal Ahmed',
            'email' => 'bilal.trial@example.com',
            'phone' => '0301-7654321',
            'city' => 'Lahore',
            'preferred_trial_date' => $trialDate,
            'preferred_time_window' => 'Evening (18:00 – 21:30)',
            'interested_membership_plan_id' => $monthly->id,
            'message' => 'Looking to test the competition racks.',
        ])->assertRedirect();

        $enquiry = Enquiry::where('phone', '0301-7654321')->firstOrFail();
        $this->assertSame('gym_trial', $enquiry->type);
        $this->assertSame($trialDate, $enquiry->preferred_trial_date?->toDateString());

        // Manager updates enquiry status & follow-up notes
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $this->actingAs($manager)
            ->put(route('manage.gym.enquiries.update', $enquiry), [
                'status' => 'trial_scheduled',
                'staff_notes' => 'Scheduled evening trial with floor coach.',
            ])
            ->assertRedirect();

        $enquiry->refresh();
        $this->assertSame('trial_scheduled', $enquiry->status);
    }

    public function test_membership_purchase_manual_verification_counter_registration_and_renewal(): void
    {
        $unassigned = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $monthly = MembershipPlan::where('slug', 'monthly-gym-access-demo')->firstOrFail();

        // 1. Customer orders Monthly plan (PKR 8,000 + PKR 3,000 registration fee = PKR 11,000)
        $this->actingAs($unassigned)
            ->post(route('gym.memberships.purchase', $monthly->slug), [
                'payment_method' => 'bank_transfer',
                'is_renewal' => false,
            ])
            ->assertRedirect();

        $pendingMembership = GymMembership::where('user_id', $unassigned->id)->firstOrFail();
        $this->assertSame(GymMembership::STATUS_PENDING_PAYMENT, $pendingMembership->status);
        $this->assertFalse($pendingMembership->isCurrentlyEligible());
        $this->assertSame(1100000, $pendingMembership->order->total_paisa);

        // 2. Staff verifies payment -> membership activates for 30 days
        $payment = Payment::where('order_id', $pendingMembership->order_id)->firstOrFail();
        $this->actingAs($manager)
            ->post(route('manage.payments.verify', $payment), [
                'notes' => 'Verified bank transfer for new gym membership.',
            ])
            ->assertRedirect();

        $pendingMembership->refresh();
        $this->assertSame(GymMembership::STATUS_ACTIVE, $pendingMembership->status);
        $this->assertTrue($pendingMembership->isCurrentlyEligible());
        $firstEndsAt = $pendingMembership->ends_at->copy();

        // 3. Counter renewal for the same member waives registration fee (PKR 8,000) and extends ends_at by +30 days
        $this->actingAs($manager)
            ->post(route('manage.gym.counter-register'), [
                'user_id' => $unassigned->id,
                'membership_plan_id' => $monthly->id,
                'counter_receipt_ref' => 'COUNTER-RENEW-901',
                'verify_payment_immediately' => true,
            ])
            ->assertRedirect();

        $pendingMembership->refresh();
        $this->assertGreaterThanOrEqual(29, (int) $firstEndsAt->diffInDays($pendingMembership->ends_at));
    }

    public function test_qr_check_in_signature_verification_replay_protection_and_eligibility_checks(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $membership = GymMembership::where('membership_number', 'GYM-DEMO-1001')->firstOrFail();
        $initialVisits = $membership->total_visits_count;

        $gymService = app(GymManagementService::class);
        $qrToken = $gymService->generateQrCheckInToken($membership, 600, 'unique-test-qr-nonce-555');

        // 1. Customer cannot call staff check-in endpoint
        $this->actingAs($customer)
            ->post(route('manage.gym.check-ins.store'), [
                'check_in_mode' => 'qr',
                'qr_token' => $qrToken,
            ])
            ->assertForbidden();

        // 2. Staff verifies signed QR token -> check-in admitted & visit count increments
        $this->actingAs($manager)
            ->post(route('manage.gym.check-ins.store'), [
                'check_in_mode' => 'qr',
                'qr_token' => $qrToken,
                'entry_note' => 'Evening squat session',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $membership->refresh();
        $this->assertSame($initialVisits + 1, $membership->total_visits_count);
        $this->assertTrue(GymCheckIn::where('qr_nonce', 'unique-test-qr-nonce-555')->exists());

        // 3. Replay protection: submitting the exact same QR token again is blocked & audited
        $this->actingAs($manager)
            ->post(route('manage.gym.check-ins.store'), [
                'check_in_mode' => 'qr',
                'qr_token' => $qrToken,
            ])
            ->assertSessionHasErrors('gym_checkin');

        $this->assertTrue(AuditLog::where('action', 'gym.checkin.replay_blocked')->exists());

        // 4. Expired membership cannot check in at desk
        $this->actingAs($manager)
            ->put(route('manage.gym.memberships.update', $membership), [
                'status' => GymMembership::STATUS_EXPIRED,
                'send_expiry_reminder' => true,
            ])
            ->assertRedirect();

        $membership->refresh();
        $this->assertNotNull($membership->expiry_reminder_sent_at);

        $this->actingAs($manager)
            ->post(route('manage.gym.check-ins.store'), [
                'check_in_mode' => 'desk',
                'member_identifier' => $membership->member_card_code,
            ])
            ->assertSessionHasErrors('gym_checkin');
    }

    public function test_gym_class_scheduling_capacity_enforcement_and_booking_cancellation(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $unassigned = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();
        $coach = User::where('email', 'coach.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();

        // 1. Coach schedules a small class with capacity = 1
        $this->actingAs($coach)
            ->post(route('manage.gym.classes.store'), [
                'title' => '1-Spot Overhead Press Clinic',
                'category' => 'Barbell Strength',
                'day_of_week' => 3,
                'starts_at_time' => '19:00',
                'ends_at_time' => '20:00',
                'capacity' => 1,
                'room_or_zone' => 'Platform 1',
            ])
            ->assertRedirect();

        $smallClass = GymClass::where('title', '1-Spot Overhead Press Clinic')->firstOrFail();
        $this->assertSame($coach->id, $smallClass->instructor_id);

        // 2. Customer without active gym membership cannot book a members-only class
        $this->actingAs($unassigned)
            ->post(route('gym.classes.book', $smallClass))
            ->assertSessionHasErrors('class_booking');

        // 3. Active gym member books the 1 spot -> booked_count becomes 1
        $this->actingAs($customer)
            ->post(route('gym.classes.book', $smallClass))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $smallClass->refresh();
        $this->assertSame(1, $smallClass->booked_count);

        // 4. Activate second member and verify capacity limit blocks overbooking
        $monthly = MembershipPlan::where('slug', 'monthly-gym-access-demo')->firstOrFail();
        app(GymManagementService::class)->activateOrRenewMembership($unassigned, $monthly, null, $manager);

        $this->actingAs($unassigned)
            ->post(route('gym.classes.book', $smallClass))
            ->assertSessionHasErrors('class_booking');

        // 5. First member cancels booking -> spot released -> second member can now book
        $booking = GymClassBooking::where('gym_class_id', $smallClass->id)->where('user_id', $customer->id)->firstOrFail();
        $this->actingAs($customer)
            ->delete(route('gym.class-bookings.cancel', $booking))
            ->assertRedirect();

        $smallClass->refresh();
        $this->assertSame(0, $smallClass->booked_count);

        $this->actingAs($unassigned)
            ->post(route('gym.classes.book', $smallClass))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // 6. Coach cannot modify a class taught by Manager
        $managerClass = GymClass::where('slug', 'upper-hypertrophy-execution-lab-demo')->firstOrFail();
        $this->actingAs($coach)
            ->put(route('manage.gym.classes.update', $managerClass), [
                'title' => 'Unauthorized Edit',
                'category' => 'Hypertrophy Foundations',
                'day_of_week' => 4,
                'starts_at_time' => '18:30',
                'ends_at_time' => '19:45',
                'capacity' => 20,
            ])
            ->assertForbidden();
    }

    public function test_admin_vs_manager_gym_authorization_packages_opening_hours_and_holidays(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $monday = OpeningHour::where('day_of_week', 1)->firstOrFail();

        // Manager cannot configure membership packages or modify opening hours
        $this->actingAs($manager)
            ->post(route('manage.gym.plans.store'), [
                'name' => 'Unauthorized Manager Plan',
                'duration_days' => 30,
                'price_pkr' => 5000,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->put(route('manage.gym.opening-hours.update', $monday), [
                'opens_at' => '05:00',
                'closes_at' => '23:30',
            ])
            ->assertForbidden();

        // Admin can configure membership packages, opening hours, and holiday schedules
        $this->actingAs($admin)
            ->post(route('manage.gym.plans.store'), [
                'name' => '6-Month Strength Pass [DEMO]',
                'duration_days' => 180,
                'price_pkr' => 38000,
                'registration_fee_pkr' => 0,
                'access_hours' => 'All Operating Hours',
                'inclusions_csv' => 'Full gym floor, Barbell clinics, Locker room',
            ])
            ->assertRedirect();

        $this->assertTrue(MembershipPlan::where('name', '6-Month Strength Pass [DEMO]')->exists());

        $this->actingAs($admin)
            ->put(route('manage.gym.opening-hours.update', $monday), [
                'opens_at' => '05:30',
                'closes_at' => '23:00',
                'peak_window' => '18:00 – 21:30',
            ])
            ->assertRedirect();

        $monday->refresh();
        $this->assertStringStartsWith('05:30', (string) $monday->opens_at);
    }
}
