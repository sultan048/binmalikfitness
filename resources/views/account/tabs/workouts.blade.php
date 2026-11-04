{{-- Tab panel "workouts" of account/dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="workouts" x-show="tab === 'workouts'" x-cloak>
    {{-- Module 7: Assigned Workout Program & Completion Tracking --}}
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 7 · Custom Workout Assignments & Completion</span>
            <h2 class="font-display text-2xl uppercase text-graphite">My Assigned Workout Schedule</h2>
        </div>

        @forelse ($workoutAssignments as $wa)
            <div class="rounded-xs border border-graphite/12 bg-paper p-4 space-y-3 text-xs">
                <div class="flex items-center justify-between">
                    <strong class="text-sm text-graphite">{{ $wa->title_override ?? $wa->program?->title }}</strong>
                    <span class="rounded-xs bg-charcoal px-2.5 py-1 font-mono text-[11px] font-bold text-gold">
                        {{ $wa->completed_sessions_count }} Sessions Completed
                    </span>
                </div>
                @if ($wa->custom_guidance)
                    <p class="text-graphite/80 border-l-2 border-gold-dark pl-2.5"><strong>Coach Guidance:</strong> {{ $wa->custom_guidance }}</p>
                @endif
                @if (! empty($wa->scheduled_days))
                    <ul class="space-y-1 text-graphite/75">
                        @foreach ($wa->scheduled_days as $dayLine)
                            <li>• {{ $dayLine }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($wa->last_session_log)
                    <p class="text-[11px] text-success font-semibold">
                        Last Logged: {{ $wa->last_session_log['day_label'] ?? '' }} ({{ $wa->last_session_log['duration_minutes'] ?? 60 }} min · RPE {{ $wa->last_session_log['rpe'] ?? 8 }}) — "{{ $wa->last_session_log['notes'] ?? '' }}"
                    </p>
                @endif

                <form method="POST" action="{{ route('account.coaching.workouts.log', $wa) }}" class="grid grid-cols-1 gap-2 border-t border-graphite/10 pt-3 sm:grid-cols-4">
                    @csrf
                    <input type="text" name="day_label" value="Day 1: Upper Strength" class="field-input text-xs py-1.5 sm:col-span-2" placeholder="Session / Day" required />
                    <input type="number" name="duration_minutes" value="60" min="10" max="240" class="field-input text-xs py-1.5" placeholder="Mins" required />
                    <input type="number" name="rpe" value="8" min="1" max="10" class="field-input text-xs py-1.5" placeholder="RPE (1-10)" required />
                    <input type="text" name="notes" placeholder="Top set weights & session notes..." class="field-input text-xs py-1.5 sm:col-span-3" />
                    <button type="submit" class="btn-light-primary rounded-xs px-3 py-1.5 text-[11px]">Log Workout</button>
                </form>
            </div>
        @empty
            <p class="text-xs text-graphite/70">No custom workout assignments yet. Your coach will assign your training split after reviewing your intake.</p>
        @endforelse
    </section>

    {{-- 12-Point Personalization Intake Form --}}
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">12-Point Personalization Intake · Encrypted Health Notes</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Coaching & Diet Intake Questionnaire</h2>
            </div>
            @if ($latestIntake)
                <span class="rounded-xs px-2.5 py-1 text-[11px] font-bold uppercase {{ $latestIntake->needs_professional_review ? 'bg-danger/15 text-danger' : 'bg-success/15 text-success' }}">
                    Status: {{ str_replace('_', ' ', $latestIntake->review_status) }}
                </span>
            @endif
        </div>

        @if ($latestIntake && $latestIntake->needs_professional_review)
            <div class="rounded-xs border border-danger/40 bg-danger/10 p-3 text-xs text-danger">
                <strong>Professional Review Flag:</strong> {{ $latestIntake->review_flag_reason }}
            </div>
        @endif

        <form method="POST" action="{{ route('account.coaching.intake.store') }}" class="space-y-3 text-xs">
            @csrf
            @if ($activeSubscription)
                <input type="hidden" name="coaching_subscription_id" value="{{ $activeSubscription->id }}" />
            @endif
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="col-span-2">
                    <label class="field-label text-xs">1. Primary Goal</label>
                    <input type="text" name="fitness_goal" value="{{ old('fitness_goal', $latestIntake?->fitness_goal ?? 'Body Recomposition') }}" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">2. Age</label>
                    <input type="number" name="age" value="{{ old('age', $latestIntake?->age ?? 29) }}" min="16" max="85" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">3. Height (cm)</label>
                    <input type="number" name="height_cm" value="{{ old('height_cm', $latestIntake?->height_cm ?? 178) }}" min="120" max="230" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">4. Weight (kg)</label>
                    <input type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $latestIntake?->weight_kg ?? 84.0) }}" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">5. Activity Level</label>
                    <input type="text" name="activity_level" value="{{ old('activity_level', $latestIntake?->activity_level ?? 'Moderately Active') }}" class="field-input text-xs py-1.5" required />
                </div>
                <div class="col-span-2">
                    <label class="field-label text-xs">6. Training Experience</label>
                    <input type="text" name="training_experience" value="{{ old('training_experience', $latestIntake?->training_experience ?? 'Intermediate (2+ years)') }}" class="field-input text-xs py-1.5" required />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="field-label text-xs">7. Pakistani / Household Food Preferences</label>
                    <input type="text" name="food_preferences" value="{{ old('food_preferences', $latestIntake?->food_preferences ?? 'Chicken tikka, eggs, whole-wheat chapati, basmati rice, daal, dahi') }}" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">8. Allergies & 9. Dietary Restrictions</label>
                    <input type="text" name="allergies" value="{{ old('allergies', $latestIntake?->allergies ?? 'None') }}" placeholder="Allergies" class="field-input text-xs py-1.5 mb-1.5" />
                    <input type="text" name="dietary_restrictions" value="{{ old('dietary_restrictions', $latestIntake?->dietary_restrictions ?? 'Halal only') }}" placeholder="Restrictions" class="field-input text-xs py-1.5" />
                </div>
                <div>
                    <label class="field-label text-xs">10. Equipment Availability</label>
                    <input type="text" name="equipment_availability" value="{{ old('equipment_availability', $latestIntake?->equipment_availability ?? 'Full Commercial Gym') }}" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">11. Preferred Weekly Schedule</label>
                    <input type="text" name="preferred_schedule" value="{{ old('preferred_schedule', $latestIntake?->preferred_schedule ?? '4 evenings/week (Mon/Tue/Thu/Fri)') }}" class="field-input text-xs py-1.5" required />
                </div>
            </div>

            <div>
                <label class="field-label text-xs">12. Optional Health / Injury Considerations (Encrypted at Rest)</label>
                <input type="text" name="health_notes" placeholder="Any joint discomfort, medical history, or physician guidance..." class="field-input text-xs py-1.5" />
            </div>

            <label class="flex items-start gap-2 text-[11px] text-graphite/80">
                <input type="checkbox" name="health_consent" value="1" checked class="mt-0.5" />
                <span>I explicitly consent to BIN MALIK FITNESS storing my intake & health notes for coaching personalisation. I understand this is not a medical prescription.</span>
            </label>

            <button type="submit" class="btn-light-primary rounded-xs px-4 py-2.5 text-xs">
                Submit / Update 12-Point Personalization Intake
            </button>
        </form>
    </section>
</div>
