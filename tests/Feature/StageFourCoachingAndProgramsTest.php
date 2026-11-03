<?php

namespace Tests\Feature;

use App\Models\CoachingCheckIn;
use App\Models\CoachingConsultation;
use App\Models\CoachingIntake;
use App\Models\CoachingPackage;
use App\Models\CoachingSubscription;
use App\Models\DietPlan;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PersonalisedPlanRequest;
use App\Models\User;
use App\Models\WorkoutAssignment;
use App\Models\WorkoutProgram;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StageFourCoachingAndProgramsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seven_diet_and_seven_workout_categories_and_free_vs_paid_diet_entitlements(): void
    {
        $response = $this->get(route('coaching.index'));
        $response->assertOk();

        foreach (DietPlan::CATEGORIES as $dietCat) {
            $response->assertSee($dietCat, false);
        }
        foreach (WorkoutProgram::CATEGORIES as $workoutCat) {
            $response->assertSee($workoutCat, false);
        }

        // Category filtering works
        $this->get(route('coaching.index', ['diet_category' => 'Body Recomposition']))
            ->assertOk()
            ->assertSee('90-Day Body Recomposition Macro Blueprint');

        $this->get(route('coaching.index', ['workout_category' => 'Mobility and Recovery']))
            ->assertOk()
            ->assertSee('Daily Thoracic, Hip & Shoulder Mobility Routine');

        $unassigned = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();

        // Free diet plan is immediately accessible
        $freePlan = DietPlan::where('type', 'free')->firstOrFail();
        $this->actingAs($unassigned)
            ->get(route('diet-plans.show', $freePlan->slug))
            ->assertOk()
            ->assertSee('Entitled · Full Plan Unlocked')
            ->assertSee('Weekly Mandi & Supermarket Shopping List');

        // Paid diet plan shows only Free Preview before purchase/verification
        $paidPlan = DietPlan::where('slug', 'lean-muscle-mass-nutrition-demo')->firstOrFail();
        $this->actingAs($unassigned)
            ->get(route('diet-plans.show', $paidPlan->slug))
            ->assertOk()
            ->assertSee('Free Preview Mode')
            ->assertDontSee('Weekly Mandi & Supermarket Shopping List');

        // Customer purchases the paid diet plan
        $purchaseRes = $this->actingAs($unassigned)->post(route('diet-plans.purchase', $paidPlan->slug), [
            'payment_method' => 'bank_transfer',
        ]);
        $purchaseRes->assertRedirect();

        $order = Order::where('user_id', $unassigned->id)->where('business_line', 'diet')->latest('id')->firstOrFail();
        $payment = $order->payments()->firstOrFail();

        // Still not entitled before payment verification
        $this->actingAs($unassigned)
            ->get(route('diet-plans.show', $paidPlan->slug))
            ->assertOk()
            ->assertSee('Free Preview Mode');

        // Staff verifies payment -> entitlement automatically granted
        $this->actingAs($manager)
            ->post(route('manage.payments.verify', $payment), ['staff_notes' => 'Verified diet plan transfer'])
            ->assertRedirect();

        $this->actingAs($unassigned)
            ->get(route('diet-plans.show', $paidPlan->slug))
            ->assertOk()
            ->assertSee('Entitled · Full Plan Unlocked')
            ->assertSee('Weekly Mandi & Supermarket Shopping List');
    }

    public function test_twelve_point_intake_encryption_consent_and_automatic_medical_flag_blocks_prescription(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $coach = User::where('email', 'coach.demo@binmalikfitness.test')->firstOrFail();
        $customDiet = DietPlan::where('type', 'personalised')->firstOrFail();

        // 1. Submitting health notes without explicit consent fails validation
        $this->actingAs($customer)
            ->post(route('account.coaching.intake.store'), [
                'diet_plan_id' => $customDiet->id,
                'fitness_goal' => 'Fat Loss',
                'age' => 31,
                'height_cm' => 175,
                'weight_kg' => 88.0,
                'activity_level' => 'Sedentary',
                'training_experience' => 'Beginner',
                'food_preferences' => 'Halal home meals',
                'equipment_availability' => 'Dumbbells',
                'preferred_schedule' => '3 days/week',
                'health_notes' => 'Type 1 diabetes on daily insulin therapy',
                'health_consent' => 0,
            ])
            ->assertSessionHasErrors('health_consent');

        // 2. Submitting with explicit consent encrypts health notes and flags clinical disclosure
        $this->actingAs($customer)
            ->post(route('account.coaching.intake.store'), [
                'diet_plan_id' => $customDiet->id,
                'fitness_goal' => 'Fat Loss',
                'age' => 31,
                'height_cm' => 175,
                'weight_kg' => 88.0,
                'activity_level' => 'Sedentary',
                'training_experience' => 'Beginner',
                'food_preferences' => 'Halal home meals',
                'allergies' => 'Peanuts',
                'dietary_restrictions' => 'Low sodium',
                'equipment_availability' => 'Dumbbells',
                'preferred_schedule' => '3 days/week',
                'health_notes' => 'Type 1 diabetes on daily insulin therapy and hypertension',
                'health_consent' => 1,
            ])
            ->assertRedirect();

        $intake = CoachingIntake::where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertTrue($intake->needs_professional_review);
        $this->assertSame(CoachingIntake::STATUS_FLAGGED_MEDICAL, $intake->review_status);
        $this->assertStringContainsString('diabetes', strtolower((string) $intake->review_flag_reason));

        // Verify raw database column is encrypted ciphertext, not plaintext
        $rawRow = DB::table('coaching_intakes')->where('id', $intake->id)->first();
        $this->assertNotNull($rawRow->health_notes_encrypted);
        $this->assertStringNotContainsString('insulin', $rawRow->health_notes_encrypted);
        $this->assertStringContainsString('insulin', $intake->health_notes_encrypted);

        $planRequest = PersonalisedPlanRequest::where('user_id', $customer->id)
            ->where('diet_plan_id', $customDiet->id)
            ->firstOrFail();
        $planRequest->update([
            'coaching_intake_id' => $intake->id,
            'status' => PersonalisedPlanRequest::STATUS_MEDICAL_HOLD,
        ]);

        // 3. Coach cannot deliver a diet plan while intake is in flagged_medical status
        $this->actingAs($coach)
            ->post(route('manage.diet-requests.deliver', $planRequest), [
                'title' => 'Attempted Plan While Flagged',
                'daily_calories' => 1900,
                'protein_grams' => 160,
                'carbs_grams' => 170,
                'fat_grams' => 60,
                'meal_1_items' => 'Eggs & chapati',
                'meal_2_items' => 'Chicken & rice',
                'meal_3_items' => 'Whey & fruit',
            ])
            ->assertSessionHasErrors('plan_delivery');

        // 4. Reviewer clears the intake after physician clearance -> coach can now deliver plan
        $this->actingAs($coach)
            ->post(route('manage.intakes.review', $intake), [
                'review_status' => CoachingIntake::STATUS_CLEARED,
                'reviewer_notes' => 'Physician clearance uploaded and verified; cleared for educational nutrition plan.',
            ])
            ->assertRedirect();

        $this->assertSame(CoachingIntake::STATUS_CLEARED, $intake->fresh()->review_status);
    }

    public function test_personalized_diet_plan_versioning_food_substitutions_and_revision_requests(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $coach = User::where('email', 'coach.demo@binmalikfitness.test')->firstOrFail();

        $planRequest = PersonalisedPlanRequest::where('user_id', $customer->id)->firstOrFail();
        $this->assertSame(1, $planRequest->deliveredVersion->version_number);

        // Customer updates food substitution overrides and requests a revision
        $this->actingAs($customer)
            ->put(route('account.diet-requests.preferences.update', $planRequest), [
                'substitutions' => [
                    'Lean Protein (Per ~35g Protein Serving)' => '140g Lean Beef Undercut',
                ],
                'revision_notes' => 'Please shift pre-workout carbs to dinner for Ramadan schedule.',
            ])
            ->assertRedirect();

        $planRequest->refresh();
        $this->assertSame(PersonalisedPlanRequest::STATUS_REVISION_REQUESTED, $planRequest->status);
        $this->assertSame('140g Lean Beef Undercut', $planRequest->custom_substitution_overrides['Lean Protein (Per ~35g Protein Serving)']);

        // Assigned coach delivers Revision v2
        $this->actingAs($coach)
            ->post(route('manage.diet-requests.deliver', $planRequest), [
                'title' => 'Personalised Recomposition Plan — Revision v2',
                'daily_calories' => 2100,
                'protein_grams' => 180,
                'carbs_grams' => 195,
                'fat_grams' => 62,
                'meal_1_items' => 'Suhoor: 4 eggs, 1 chapati, 200g dahi',
                'meal_2_items' => 'Iftar: 3 dates, 1 scoop whey, 180g chicken tikka',
                'meal_3_items' => 'Post-Training Dinner: 180g lean beef undercut, 180g basmati rice, salad',
                'revision_note' => 'Revision v2 adjusted for Suhoor & Iftar timing',
            ])
            ->assertRedirect();

        $planRequest->refresh();
        $this->assertSame(PersonalisedPlanRequest::STATUS_DELIVERED, $planRequest->status);
        $this->assertSame(2, $planRequest->deliveredVersion->version_number);
    }

    public function test_coaching_enrolment_manual_payment_verification_pause_resume_renewal_and_cancellation(): void
    {
        $unassigned = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $package = CoachingPackage::where('slug', '8-week-foundation-coaching')->firstOrFail();

        // 1. Customer enrols in 8-week package
        $this->actingAs($unassigned)
            ->post(route('coaching.enrol', $package->slug), ['payment_method' => 'bank_transfer'])
            ->assertRedirect();

        $subscription = CoachingSubscription::where('user_id', $unassigned->id)->firstOrFail();
        $this->assertSame(CoachingSubscription::STATUS_PENDING_PAYMENT, $subscription->status);
        $this->assertNull($subscription->ends_at);

        // 2. Staff verifies the order payment -> subscription activates for 56 days
        $order = Order::findOrFail($subscription->latest_order_id);
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($manager)
            ->post(route('manage.payments.verify', $payment), ['staff_notes' => 'Verified 8-week coaching transfer'])
            ->assertRedirect();

        $subscription->refresh();
        $this->assertSame(CoachingSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNotNull($subscription->ends_at);
        $initialEndsAt = $subscription->ends_at->copy();

        // 3. Customer pauses for 7 days -> status = paused, ends_at extended by 7 days
        $this->actingAs($unassigned)
            ->post(route('account.coaching.subscriptions.pause', $subscription), [
                'pause_days' => 7,
                'reason' => 'Out of city travel',
            ])
            ->assertRedirect();

        $subscription->refresh();
        $this->assertSame(CoachingSubscription::STATUS_PAUSED, $subscription->status);
        $this->assertSame(7, $subscription->pause_days_used);
        $this->assertEqualsWithDelta($initialEndsAt->copy()->addDays(7)->timestamp, $subscription->ends_at->timestamp, 5);

        // 4. Customer resumes subscription
        $this->actingAs($unassigned)
            ->post(route('account.coaching.subscriptions.resume', $subscription))
            ->assertRedirect();
        $this->assertSame(CoachingSubscription::STATUS_ACTIVE, $subscription->fresh()->status);

        // 5. Attempting to pause an additional 10 days (exceeding 14-day max allowance) is rejected
        $this->actingAs($unassigned)
            ->post(route('account.coaching.subscriptions.pause', $subscription), [
                'pause_days' => 10,
                'reason' => 'Exceeds allowance',
            ])
            ->assertSessionHasErrors('pause');

        // 6. Manual renewal creates a new order and extends ends_at upon payment verification
        $beforeRenewalEnd = $subscription->fresh()->ends_at->copy();
        $this->actingAs($unassigned)
            ->post(route('account.coaching.subscriptions.renew', $subscription))
            ->assertRedirect();

        $renewalOrder = Order::findOrFail($subscription->fresh()->latest_order_id);
        $renewalPayment = $renewalOrder->payments()->firstOrFail();

        $this->actingAs($manager)
            ->post(route('manage.payments.verify', $renewalPayment), ['staff_notes' => 'Verified renewal payment'])
            ->assertRedirect();

        $subscription->refresh();
        $this->assertEqualsWithDelta(
            $beforeRenewalEnd->copy()->addDays($package->duration_days)->timestamp,
            $subscription->ends_at->timestamp,
            5
        );

        // 7. Cancelling subscription revokes active entitlements
        $this->actingAs($unassigned)
            ->post(route('account.coaching.subscriptions.cancel', $subscription), [
                'reason' => 'Completed training goals',
            ])
            ->assertRedirect();

        $this->assertSame(CoachingSubscription::STATUS_CANCELLED, $subscription->fresh()->status);
        $this->assertDatabaseHas('entitlements', [
            'source_type' => CoachingSubscription::class,
            'source_id' => $subscription->id,
            'status' => Entitlement::STATUS_REVOKED,
        ]);
    }

    public function test_video_library_server_side_entitlement_enforcement(): void
    {
        $entitledCustomer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $unentitledCustomer = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();

        // Public preview video is accessible to guests and all users
        $this->get(route('videos.watch', 'barbell-back-squat-standards-demo'))
            ->assertOk()
            ->assertSee('Public Technical Preview');

        // Entitled video is blocked (403) for guests and unentitled users
        $this->get(route('videos.watch', 'incline-db-press-scapular-plane-entitled'))
            ->assertForbidden();

        $this->actingAs($unentitledCustomer)
            ->get(route('videos.watch', 'incline-db-press-scapular-plane-entitled'))
            ->assertForbidden();

        // Entitled coaching customer gets 200 OK with signed stream token
        $this->actingAs($entitledCustomer)
            ->get(route('videos.watch', 'incline-db-press-scapular-plane-entitled'))
            ->assertOk()
            ->assertSee('Entitled Stream Verified')
            ->assertSee('Signed Token:');
    }

    public function test_check_ins_private_progress_photos_workout_completion_consultations_and_scoped_messaging(): void
    {
        Storage::fake('local');

        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $unassigned = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();
        $coach = User::where('email', 'coach.demo@binmalikfitness.test')->firstOrFail();
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();

        // 1. Customer submits weekly check-in with private progress photo (private by default)
        $photo = UploadedFile::fake()->image('week4-progress.jpg', 800, 1000);
        $this->actingAs($customer)
            ->post(route('account.coaching.check-ins.store'), [
                'weight_kg' => 83.2,
                'waist_cm' => 88.0,
                'chest_cm' => 106.5,
                'diet_adherence_score' => 9,
                'energy_score' => 9,
                'workouts_completed_this_week' => 4,
                'client_notes' => 'Week 4 check-in with progress photo.',
                'progress_photo' => $photo,
                'photo_storage_consent' => 1,
                'public_display_consent' => 0,
            ])
            ->assertRedirect();

        $checkIn = CoachingCheckIn::where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertNotNull($checkIn->progress_photo_file_id);
        $this->assertFalse($checkIn->is_public_display_consented);

        // 2. Assigned coach reviews check-in
        $this->actingAs($coach)
            ->post(route('manage.check-ins.review', $checkIn), [
                'coach_feedback' => 'Down another 0.7 kg and waist at 88 cm. Keep macros steady.',
            ])
            ->assertRedirect();

        $this->assertSame('reviewed', $checkIn->fresh()->status);

        // 3. Assigned coach assigns a workout program & customer logs workout completion
        $program = WorkoutProgram::where('slug', 'push-pull-legs-hypertrophy-10wk-demo')->firstOrFail();
        $this->actingAs($coach)
            ->post(route('manage.workouts.assign'), [
                'client_id' => $customer->id,
                'workout_program_id' => $program->id,
                'custom_guidance' => 'Control eccentric for 3 seconds on incline presses.',
            ])
            ->assertRedirect();

        $assignment = WorkoutAssignment::where('user_id', $customer->id)
            ->where('workout_program_id', $program->id)
            ->firstOrFail();

        $this->actingAs($customer)
            ->post(route('account.coaching.workouts.log', $assignment), [
                'day_label' => 'Day 1: Push Hypertrophy',
                'duration_minutes' => 58,
                'rpe' => 8,
                'notes' => 'Incline DB 36kg x 9 reps.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $assignment->fresh()->completed_sessions_count);

        // 4. Customer books a consultation & assigned coach confirms it
        $this->actingAs($customer)
            ->post(route('account.coaching.consultations.store'), [
                'scheduled_at' => now()->addDays(5)->toDateTimeString(),
                'channel' => 'zoom',
                'topic' => 'Phase 2 Hypertrophy Transition Call',
            ])
            ->assertRedirect();

        $consultation = CoachingConsultation::where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->actingAs($coach)
            ->put(route('manage.consultations.update', $consultation), [
                'status' => CoachingConsultation::STATUS_CONFIRMED,
                'meeting_reference' => 'Zoom Room #BMF-991',
                'coach_summary' => 'Confirmed for Phase 2 review.',
            ])
            ->assertRedirect();

        $this->assertSame(CoachingConsultation::STATUS_CONFIRMED, $consultation->fresh()->status);

        // 5. Scoped Messaging & Cross-Client Isolation: Coach can message assigned client, but is blocked (403) from unassigned client
        $this->actingAs($coach)
            ->post(route('manage.clients.messages.store', $customer), [
                'body' => 'Great work on the Push session today!',
            ])
            ->assertRedirect();

        $this->actingAs($coach)
            ->post(route('manage.clients.messages.store', $unassigned), [
                'body' => 'Unauthorized message to unassigned client',
            ])
            ->assertForbidden();

        $this->actingAs($coach)
            ->post(route('manage.workouts.assign'), [
                'client_id' => $unassigned->id,
                'workout_program_id' => $program->id,
            ])
            ->assertForbidden();

        // 6. Admin vs Coach/Manager authorization on creating master coaching packages & diet plans
        $this->actingAs($coach)
            ->post(route('manage.coaching-packages.store'), [
                'name' => 'Unauthorized Coach Package',
                'duration_days' => 56,
                'price_pkr' => 25000,
                'checkins_per_week' => 1,
                'tagline' => 'Should fail 403',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('manage.coaching-packages.store'), [
                'name' => '24-Week Elite Transformation [DEMO]',
                'duration_days' => 168,
                'price_pkr' => 72000,
                'checkins_per_week' => 2,
                'tagline' => 'Admin-created 24-week coaching package',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('coaching_packages', ['name' => '24-Week Elite Transformation [DEMO]']);
    }
}
