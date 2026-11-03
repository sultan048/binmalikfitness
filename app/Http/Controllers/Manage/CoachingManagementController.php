<?php

namespace App\Http\Controllers\Manage;

use App\Domain\Coaching\CoachingService;
use App\Domain\Coaching\IntakeAndPersonalisationService;
use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\CoachingCheckIn;
use App\Models\CoachingConsultation;
use App\Models\CoachingIntake;
use App\Models\CoachingPackage;
use App\Models\CoachingSubscription;
use App\Models\DietPlan;
use App\Models\PersonalisedPlanRequest;
use App\Models\User;
use App\Models\WorkoutProgram;
use App\Support\Money;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CoachingManagementController extends Controller
{
    public function __construct(
        private readonly CoachingService $coachingService,
        private readonly IntakeAndPersonalisationService $intakeService,
        private readonly AuditLogger $audit,
    ) {}

    public function reviewIntake(Request $request, CoachingIntake $intake): RedirectResponse
    {
        $validated = $request->validate([
            'review_status' => ['required', 'string', 'in:cleared,flagged_medical'],
            'reviewer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->intakeService->reviewIntake(
                actor: $request->user(),
                intake: $intake,
                status: $validated['review_status'],
                notes: $validated['reviewer_notes'] ?? null,
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['review_status' => [$e->getMessage()]]);
        }

        return back()->with('status', "Intake #{$intake->id} marked as {$validated['review_status']}.");
    }

    public function deliverPersonalisedPlan(Request $request, PersonalisedPlanRequest $planRequest): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'daily_calories' => ['required', 'integer', 'min:1100', 'max:5500'],
            'protein_grams' => ['required', 'integer', 'min:60', 'max:350'],
            'carbs_grams' => ['required', 'integer', 'min:50', 'max:650'],
            'fat_grams' => ['required', 'integer', 'min:30', 'max:200'],
            'hydration_target_liters' => ['nullable', 'numeric', 'min:1.5', 'max:8'],
            'meal_1_items' => ['required', 'string', 'max:500'],
            'meal_2_items' => ['required', 'string', 'max:500'],
            'meal_3_items' => ['required', 'string', 'max:500'],
            'meal_4_items' => ['nullable', 'string', 'max:500'],
            'protein_swap_note' => ['nullable', 'string', 'max:300'],
            'carb_swap_note' => ['nullable', 'string', 'max:300'],
            'shopping_staples' => ['nullable', 'string', 'max:600'],
            'coach_notes' => ['nullable', 'string', 'max:1000'],
            'revision_note' => ['nullable', 'string', 'max:255'],
        ]);

        $mealSchedule = [
            ['meal' => 'Meal 1 — Breakfast (08:30)', 'items' => $validated['meal_1_items'], 'macros' => round($validated['protein_grams'] * 0.25).'g P · '.round($validated['carbs_grams'] * 0.25).'g C'],
            ['meal' => 'Meal 2 — Lunch (13:30)', 'items' => $validated['meal_2_items'], 'macros' => round($validated['protein_grams'] * 0.30).'g P · '.round($validated['carbs_grams'] * 0.30).'g C'],
            ['meal' => 'Meal 3 — Pre-Workout / Afternoon (17:15)', 'items' => $validated['meal_3_items'], 'macros' => round($validated['protein_grams'] * 0.20).'g P · '.round($validated['carbs_grams'] * 0.25).'g C'],
        ];

        if (! empty($validated['meal_4_items'])) {
            $mealSchedule[] = [
                'meal' => 'Meal 4 — Post-Workout Dinner (20:30)',
                'items' => $validated['meal_4_items'],
                'macros' => round($validated['protein_grams'] * 0.25).'g P · '.round($validated['carbs_grams'] * 0.20).'g C',
            ];
        }

        $substitutions = [
            [
                'group' => 'Lean Protein Swaps',
                'original_item' => '180g Raw Chicken Breast',
                'swap_options' => array_values(array_filter(array_map('trim', explode(',', ($validated['protein_swap_note'] ?? '') ?: '190g White Fish, 160g Lean Beef Qeema, 6 Egg Whites + 1 Whole Egg')))),
                'notes' => 'Iso-protein approved household swaps.',
            ],
            [
                'group' => 'Carbohydrate Swaps',
                'original_item' => '1 Whole-Wheat Chapati (45g atta)',
                'swap_options' => array_values(array_filter(array_map('trim', explode(',', ($validated['carb_swap_note'] ?? '') ?: '140g Cooked Basmati Rice, 190g Boiled Potato, 45g Rolled Oats')))),
                'notes' => 'Keep carb swaps iso-caloric.',
            ],
        ];

        $shoppingList = [
            'Weekly Core Staples' => array_values(array_filter(array_map('trim', explode(',', ($validated['shopping_staples'] ?? '') ?: 'Chicken breast (2.5 kg), Eggs (2 dozen), Whole-wheat atta (1.5 kg), Basmati rice (1 kg), Low-fat dahi (1 kg), Seasonal vegetables')))),
        ];

        try {
            $version = $this->intakeService->deliverPersonalisedPlan(
                actor: $request->user(),
                planRequest: $planRequest,
                data: [
                    'title' => $validated['title'],
                    'daily_calories' => $validated['daily_calories'],
                    'protein_grams' => $validated['protein_grams'],
                    'carbs_grams' => $validated['carbs_grams'],
                    'fat_grams' => $validated['fat_grams'],
                    'hydration_target_liters' => $validated['hydration_target_liters'] ?? 3.5,
                    'meal_schedule' => $mealSchedule,
                    'substitutions' => $substitutions,
                    'shopping_list' => $shoppingList,
                    'coach_notes' => $validated['coach_notes'] ?? null,
                    'revision_note' => $validated['revision_note'] ?? null,
                ]
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['plan_delivery' => [$e->getMessage()]]);
        }

        return back()->with('status', "Delivered personalised diet plan revision v{$version->version_number} and granted active customer entitlement.");
    }

    public function reviewCheckIn(Request $request, CoachingCheckIn $checkIn): RedirectResponse
    {
        $validated = $request->validate([
            'coach_feedback' => ['required', 'string', 'max:1500'],
        ]);

        try {
            $this->coachingService->reviewCheckIn(
                coach: $request->user(),
                checkIn: $checkIn,
                feedback: $validated['coach_feedback'],
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', "Feedback recorded for {$checkIn->user->name}'s check-in.");
    }

    public function assignWorkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:users,id'],
            'workout_program_id' => ['required', 'exists:workout_programs,id'],
            'coaching_subscription_id' => ['nullable', 'exists:coaching_subscriptions,id'],
            'title_override' => ['nullable', 'string', 'max:160'],
            'custom_guidance' => ['nullable', 'string', 'max:1000'],
            'target_sessions_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
        ]);

        $client = User::findOrFail($validated['client_id']);
        $program = WorkoutProgram::findOrFail($validated['workout_program_id']);

        try {
            $this->coachingService->assignWorkout(
                coach: $request->user(),
                client: $client,
                program: $program,
                data: $validated,
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', "Assigned workout program '{$program->title}' to {$client->name}.");
    }

    public function updateConsultation(Request $request, CoachingConsultation $consultation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,completed,cancelled'],
            'meeting_reference' => ['nullable', 'string', 'max:190'],
            'coach_summary' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->coachingService->updateConsultationStatus(
                staff: $request->user(),
                consultation: $consultation,
                status: $validated['status'],
                meetingReference: $validated['meeting_reference'] ?? null,
                coachSummary: $validated['coach_summary'] ?? null,
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', "Consultation #{$consultation->id} updated to {$validated['status']}.");
    }

    public function sendCoachMessage(Request $request, User $client): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'coaching_subscription_id' => ['nullable', 'exists:coaching_subscriptions,id'],
        ]);

        try {
            $this->coachingService->sendMessage(
                sender: $request->user(),
                client: $client,
                body: $validated['body'],
                subscriptionId: isset($validated['coaching_subscription_id']) ? (int) $validated['coaching_subscription_id'] : null,
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('status', "Sent coaching message to {$client->name}.");
    }

    public function storeCoachingPackage(Request $request): RedirectResponse
    {
        if (! $request->user()->can('coaching.packages.manage')) {
            abort(403, 'Only Administrators may create or modify coaching packages.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'duration_days' => ['required', 'integer', 'min:7', 'max:365'],
            'price_pkr' => ['required', 'numeric', 'min:1000'],
            'checkins_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'tagline' => ['required', 'string', 'max:190'],
        ]);

        $slug = Str::slug($validated['name']).'-'. strtolower(Str::random(4));

        $package = CoachingPackage::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'tagline' => $validated['tagline'],
            'duration_days' => (int) $validated['duration_days'],
            'price' => Money::fromRupees($validated['price_pkr']),
            'billing_note' => 'Manual cycle renewal · Verified payment required',
            'checkins_per_week' => (int) $validated['checkins_per_week'],
            'includes_custom_diet' => true,
            'includes_custom_workout' => true,
            'includes_video_library' => true,
            'includes_consultation' => true,
            'inclusions' => [
                '12-point personalization intake & coach review',
                'Custom training & South Asian meal schedule',
                "{$validated['checkins_per_week']}x weekly check-ins & video library access",
            ],
            'status' => 'active',
        ]);

        $this->audit->record(
            action: 'coaching.package.created',
            subject: $package,
            oldValues: null,
            newValues: ['name' => $package->name, 'price_paisa' => $package->price],
            actor: $request->user(),
        );

        return back()->with('status', "Created coaching package '{$package->name}'.");
    }

    public function storeDietPlan(Request $request): RedirectResponse
    {
        if (! $request->user()->can('diet.plans.manage')) {
            abort(403, 'Only Administrators may create or modify master diet plans.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'in:'.implode(',', DietPlan::CATEGORIES)],
            'type' => ['required', 'string', 'in:free,paid,personalised'],
            'duration_days' => ['required', 'integer', 'min:7', 'max:180'],
            'price_pkr' => ['required', 'numeric', 'min:0'],
            'summary' => ['required', 'string', 'max:255'],
        ]);

        $slug = Str::slug($validated['title']).'-'.strtolower(Str::random(4));

        $plan = DietPlan::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'category' => $validated['category'],
            'type' => $validated['type'],
            'duration_days' => (int) $validated['duration_days'],
            'target_calories' => '1,900 – 2,300 kcal',
            'price' => $validated['type'] === 'free' ? 0 : Money::fromRupees($validated['price_pkr']),
            'summary' => $validated['summary'],
            'disclaimer' => 'Educational nutrition framework for healthy adults. Not a medical prescription.',
            'status' => 'active',
        ]);

        $this->audit->record(
            action: 'diet.plan.created',
            subject: $plan,
            oldValues: null,
            newValues: ['title' => $plan->title, 'type' => $plan->type, 'price_paisa' => $plan->price],
            actor: $request->user(),
        );

        return back()->with('status', "Created diet plan '{$plan->title}'.");
    }
}
