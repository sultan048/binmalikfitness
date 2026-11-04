<?php

namespace App\Http\Controllers\Public;

use App\Domain\Coaching\CoachingService;
use App\Domain\Coaching\IntakeAndPersonalisationService;
use App\Domain\Platform\EntitlementService;
use App\Http\Controllers\Controller;
use App\Models\CoachingPackage;
use App\Models\CoachingSubscription;
use App\Models\DietPlan;
use App\Models\PersonalisedPlanRequest;
use App\Models\StaffProfile;
use App\Models\Video;
use App\Models\WorkoutAssignment;
use App\Models\WorkoutProgram;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class CoachingAndProgramsController extends Controller
{
    public function __construct(
        private readonly CoachingService $coachingService,
        private readonly IntakeAndPersonalisationService $intakeService,
        private readonly EntitlementService $entitlements,
    ) {}

    public function coachingIndex(Request $request): View
    {
        $selectedDietCategory = $request->string('diet_category')->toString();
        $selectedWorkoutCategory = $request->string('workout_category')->toString();

        $dietPlansQuery = DietPlan::query()->where('status', 'active')->orderByDesc('is_featured')->orderBy('price');
        if ($selectedDietCategory !== '') {
            $dietPlansQuery->where('category', $selectedDietCategory);
        }

        $workoutProgramsQuery = WorkoutProgram::query()->where('status', 'active')->orderByDesc('is_featured')->orderBy('id');
        if ($selectedWorkoutCategory !== '') {
            $workoutProgramsQuery->where('category', $selectedWorkoutCategory);
        }

        $videos = Video::query()->orderBy('access')->orderBy('id')->get();
        $user = $request->user();

        $entitledVideoIds = [];
        if ($user) {
            foreach ($videos as $video) {
                if ($this->entitlements->allows($user, $video)) {
                    $entitledVideoIds[] = $video->id;
                }
            }
        } else {
            $entitledVideoIds = $videos->where('access', 'public')->pluck('id')->all();
        }

        return view('public.coaching.index', [
            'packages' => CoachingPackage::query()->where('status', 'active')->orderBy('sort_order')->get(),
            'dietCategories' => DietPlan::CATEGORIES,
            'selectedDietCategory' => $selectedDietCategory,
            'dietPlans' => $dietPlansQuery->get(),
            'workoutCategories' => WorkoutProgram::CATEGORIES,
            'selectedWorkoutCategory' => $selectedWorkoutCategory,
            'workoutPrograms' => $workoutProgramsQuery->get(),
            'videos' => $videos,
            'entitledVideoIds' => $entitledVideoIds,
            'headCoach' => StaffProfile::query()->with('user')->where('is_public_coach', true)->first(),
        ]);
    }

    public function coachingShow(Request $request, string $slug): View
    {
        $package = CoachingPackage::query()->where('slug', $slug)->firstOrFail();
        $otherPackages = CoachingPackage::query()->whereKeyNot($package->id)->where('status', 'active')->orderBy('sort_order')->get();
        $user = $request->user();

        $activeSubscription = $user
            ? CoachingSubscription::with(['coach', 'intake'])
                ->where('user_id', $user->id)
                ->where('coaching_package_id', $package->id)
                ->latest('id')
                ->first()
            : null;

        return view('public.coaching.show', [
            'package' => $package,
            'otherPackages' => $otherPackages,
            'activeSubscription' => $activeSubscription,
            'isEntitled' => $user ? $this->entitlements->allows($user, $package) : false,
        ]);
    }

    public function dietPlanShow(Request $request, string $slug): View
    {
        $plan = DietPlan::query()->with(['templateVersion', 'versions'])->where('slug', $slug)->firstOrFail();
        $otherPlans = DietPlan::query()->whereKeyNot($plan->id)->where('status', 'active')->take(3)->get();
        $user = $request->user();

        $isEntitled = $this->entitlements->allows($user, $plan);

        $userPlanRequest = $user
            ? PersonalisedPlanRequest::with(['deliveredVersion', 'intake', 'assignedCoach'])
                ->where('user_id', $user->id)
                ->where('diet_plan_id', $plan->id)
                ->latest('id')
                ->first()
            : null;

        $activeVersion = null;
        if ($isEntitled) {
            $activeVersion = $userPlanRequest?->deliveredVersion
                ?? ($user ? $plan->versions()->where('user_id', $user->id)->where('is_active', true)->first() : null)
                ?? $plan->templateVersion;
        }

        $userVersions = $user
            ? $plan->versions()->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))->get()
            : collect();

        return view('public.programs.diet-show', [
            'plan' => $plan,
            'otherPlans' => $otherPlans,
            'isEntitled' => $isEntitled,
            'activeVersion' => $activeVersion,
            'userPlanRequest' => $userPlanRequest,
            'userVersions' => $userVersions,
        ]);
    }

    public function watchVideo(Request $request, string $slug): View
    {
        $video = Video::query()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        try {
            $playbackDescriptor = $this->entitlements->issueVideoPlaybackToken($user, $video);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        $otherVideos = Video::query()->whereKeyNot($video->id)->orderBy('access')->take(4)->get();

        return view('public.videos.watch', [
            'video' => $video,
            'playback' => $playbackDescriptor,
            'otherVideos' => $otherVideos,
        ]);
    }

    public function enrolCoaching(Request $request, string $slug): RedirectResponse
    {
        $package = CoachingPackage::query()->where('slug', $slug)->where('status', 'active')->firstOrFail();
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:bank_transfer,cod'],
        ]);

        $order = $this->coachingService->createProgramOrder(
            user: $request->user(),
            purchasable: $package,
            paymentMethod: $validated['payment_method'],
        );

        return redirect()
            ->route('orders.confirmation', ['orderNumber' => $order->order_number])
            ->with('status', "Coaching order {$order->order_number} created. Submit your payment proof below; your coaching subscription activates once staff verifies payment.");
    }

    public function purchaseDietPlan(Request $request, string $slug): RedirectResponse
    {
        $plan = DietPlan::query()->where('slug', $slug)->where('status', 'active')->firstOrFail();

        if ($plan->type === 'free') {
            $this->entitlements->grant($request->user(), $plan, $plan, now(), null, $request->user());

            return redirect()
                ->route('diet-plans.show', $plan->slug)
                ->with('status', 'Free diet plan entitlement unlocked.');
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:bank_transfer,cod'],
        ]);

        $order = $this->coachingService->createProgramOrder(
            user: $request->user(),
            purchasable: $plan,
            paymentMethod: $validated['payment_method'],
        );

        return redirect()
            ->route('orders.confirmation', ['orderNumber' => $order->order_number])
            ->with('status', "Diet plan order {$order->order_number} created. Entitlement activates once staff verifies your payment.");
    }

    public function purchaseWorkoutProgram(Request $request, string $slug): RedirectResponse
    {
        $program = WorkoutProgram::query()->where('slug', $slug)->where('status', 'active')->firstOrFail();

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:bank_transfer,cod'],
        ]);

        $order = $this->coachingService->createProgramOrder(
            user: $request->user(),
            purchasable: $program,
            paymentMethod: $validated['payment_method'],
        );

        return redirect()
            ->route('orders.confirmation', ['orderNumber' => $order->order_number])
            ->with('status', "Workout program order {$order->order_number} created. Entitlement activates after payment verification.");
    }

    public function submitIntake(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coaching_subscription_id' => ['nullable', 'exists:coaching_subscriptions,id'],
            'diet_plan_id' => ['nullable', 'exists:diet_plans,id'],
            'fitness_goal' => ['required', 'string', 'max:80'],
            'age' => ['required', 'integer', 'min:16', 'max:85'],
            'height_cm' => ['required', 'integer', 'min:120', 'max:230'],
            'weight_kg' => ['required', 'numeric', 'min:35', 'max:260'],
            'activity_level' => ['required', 'string', 'max:64'],
            'training_experience' => ['required', 'string', 'max:64'],
            'food_preferences' => ['required', 'string', 'max:1000'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'dietary_restrictions' => ['nullable', 'string', 'max:500'],
            'equipment_availability' => ['required', 'string', 'max:120'],
            'preferred_schedule' => ['required', 'string', 'max:160'],
            'health_notes' => ['nullable', 'string', 'max:1500'],
            'health_consent' => ['nullable', 'boolean'],
        ]);

        try {
            $intake = $this->intakeService->submitIntake(
                user: $request->user(),
                data: $validated,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['health_consent' => [$e->getMessage()]]);
        }

        $msg = $intake->needs_professional_review
            ? '12-point intake recorded. Because your notes include a clinical/medical disclosure, your file has been flagged for qualified professional review before plan preparation.'
            : '12-point personalization intake submitted and encrypted for your assigned coach.';

        return back()->with('status', $msg);
    }

    public function submitCheckIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coaching_subscription_id' => ['nullable', 'exists:coaching_subscriptions,id'],
            'check_in_date' => ['nullable', 'date'],
            'weight_kg' => ['required', 'numeric', 'min:35', 'max:260'],
            'waist_cm' => ['nullable', 'numeric', 'min:45', 'max:180'],
            'chest_cm' => ['nullable', 'numeric', 'min:50', 'max:180'],
            'hips_cm' => ['nullable', 'numeric', 'min:50', 'max:180'],
            'arms_cm' => ['nullable', 'numeric', 'min:18', 'max:70'],
            'thighs_cm' => ['nullable', 'numeric', 'min:30', 'max:100'],
            'body_fat_pct' => ['nullable', 'numeric', 'min:4', 'max:60'],
            'sleep_hours' => ['nullable', 'numeric', 'min:1', 'max:16'],
            'water_liters' => ['nullable', 'numeric', 'min:0.5', 'max:12'],
            'step_count' => ['nullable', 'integer', 'min:0', 'max:60000'],
            'diet_adherence_score' => ['required', 'integer', 'min:1', 'max:10'],
            'energy_score' => ['required', 'integer', 'min:1', 'max:10'],
            'workouts_completed_this_week' => ['required', 'integer', 'min:0', 'max:14'],
            'client_notes' => ['nullable', 'string', 'max:1500'],
            'progress_photo' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'photo_storage_consent' => ['nullable', 'boolean'],
            'public_display_consent' => ['nullable', 'boolean'],
        ]);

        try {
            $this->coachingService->submitCheckIn(
                client: $request->user(),
                data: $validated,
                photoFile: $request->file('progress_photo'),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['progress_photo' => [$e->getMessage()]]);
        }

        return back()->with('status', 'Weekly check-in and private measurements recorded.');
    }

    public function logWorkoutCompletion(Request $request, WorkoutAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'day_label' => ['required', 'string', 'max:120'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'rpe' => ['required', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->coachingService->logWorkoutCompletion($request->user(), $assignment, $validated);

        return back()->with('status', 'Workout session logged and completion counter updated.');
    }

    public function bookConsultation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'in:20,30,45,60'],
            'channel' => ['required', 'string', 'in:whatsapp_video,zoom,in_person_gym'],
            'topic' => ['required', 'string', 'max:180'],
            'client_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->coachingService->bookConsultation($request->user(), $validated);

        return back()->with('status', '1-on-1 coach consultation request submitted.');
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coaching_subscription_id' => ['nullable', 'exists:coaching_subscriptions,id'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $this->coachingService->sendMessage(
            sender: $request->user(),
            client: $request->user(),
            body: $validated['body'],
            subscriptionId: isset($validated['coaching_subscription_id']) ? (int) $validated['coaching_subscription_id'] : null,
        );

        return back()->with('status', 'Message sent to your assigned coach.');
    }

    public function pauseSubscription(Request $request, CoachingSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'pause_days' => ['required', 'integer', 'min:1', 'max:14'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->coachingService->pauseSubscription(
                actor: $request->user(),
                subscription: $subscription,
                pauseDays: (int) $validated['pause_days'],
                reason: $validated['reason'],
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['pause' => [$e->getMessage()]]);
        }

        return back()->with('status', 'Coaching subscription paused and end date extended.');
    }

    public function resumeSubscription(Request $request, CoachingSubscription $subscription): RedirectResponse
    {
        try {
            $this->coachingService->resumeSubscription($request->user(), $subscription);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['resume' => [$e->getMessage()]]);
        }

        return back()->with('status', 'Coaching subscription resumed.');
    }

    public function cancelSubscription(Request $request, CoachingSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->coachingService->cancelSubscription($request->user(), $subscription, $validated['reason']);

        return back()->with('status', 'Coaching subscription cancelled and entitlements revoked.');
    }

    public function renewSubscription(Request $request, CoachingSubscription $subscription): RedirectResponse
    {
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $order = $this->coachingService->createProgramOrder(
            user: $request->user(),
            purchasable: $subscription->package,
            paymentMethod: 'bank_transfer',
            renewalSubscription: $subscription,
        );

        return redirect()
            ->route('orders.confirmation', ['orderNumber' => $order->order_number])
            ->with('status', "Manual renewal order {$order->order_number} created. Once payment is verified by staff, your coaching term will be extended.");
    }

    public function updateDietPreferences(Request $request, PersonalisedPlanRequest $planRequest): RedirectResponse
    {
        $validated = $request->validate([
            'substitutions' => ['nullable', 'array'],
            'substitutions.*' => ['nullable', 'string', 'max:160'],
            'revision_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->intakeService->updateCustomerSubstitutionsAndRevision(
            user: $request->user(),
            planRequest: $planRequest,
            substitutionOverrides: $validated['substitutions'] ?? [],
            revisionNotes: $validated['revision_notes'] ?? null,
        );

        return back()->with('status', 'Diet plan substitution preferences and revision notes saved.');
    }
}
