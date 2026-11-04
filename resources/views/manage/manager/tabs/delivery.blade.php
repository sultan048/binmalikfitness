{{-- Tab panel "delivery" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
<div id="coaching-delivery-ops" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="delivery" x-show="tab === 'delivery'" x-cloak>
    {{-- 12-Point Intake Review & Personalised Diet Plan Delivery --}}
    <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 8 · Personalised Diet Plan Preparation & Medical Safeguards</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Assigned Intakes & Custom Diet Delivery ({{ $assignedPlanRequests->count() }})</h2>
            </div>
            <x-ui.badge tone="gold">diet.requests.handle</x-ui.badge>
        </div>

        @if ($assignedIntakes->isNotEmpty())
            <div class="space-y-3 text-xs">
                @foreach ($assignedIntakes as $intake)
                    <div class="rounded-xs border {{ $intake->needs_professional_review ? 'border-danger/50 bg-danger/5' : 'border-graphite/12 bg-paper' }} p-3.5 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <strong>Intake #{{ $intake->id }} — {{ $intake->user?->name }} ({{ $intake->fitness_goal }})</strong>
                            <span class="rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ str_replace('_', ' ', $intake->review_status) }}</span>
                        </div>
                        <p class="text-graphite/75">
                            {{ $intake->age }}y · {{ $intake->height_cm }}cm · {{ $intake->weight_kg }}kg · {{ $intake->activity_level }} · {{ $intake->equipment_availability }}
                        </p>
                        <p class="text-graphite/75"><strong>Preferences:</strong> {{ $intake->food_preferences }} | <strong>Allergies:</strong> {{ $intake->allergies ?? 'None' }}</p>
                        @if ($intake->review_flag_reason)
                            <p class="text-danger font-semibold">{{ $intake->review_flag_reason }}</p>
                        @endif

                        <form method="POST" action="{{ route('manage.intakes.review', $intake) }}" class="flex flex-wrap items-center gap-2 pt-1">
                            @csrf
                            <select name="review_status" class="field-input py-1 text-[11px] w-44">
                                <option value="cleared" @selected($intake->review_status === 'cleared')>Clear Intake for Plan Delivery</option>
                                <option value="flagged_medical" @selected($intake->review_status === 'flagged_medical')>Flag Medical Hold</option>
                            </select>
                            <input type="text" name="reviewer_notes" value="{{ $intake->reviewer_notes }}" placeholder="Qualification / review note..." class="field-input py-1 text-[11px] flex-1" />
                            <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 text-[10px] font-bold uppercase text-gold cursor-pointer">Update Review</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        @foreach ($assignedPlanRequests as $req)
            <form method="POST" action="{{ route('manage.diet-requests.deliver', $req) }}" class="space-y-3 rounded-xs border border-graphite/15 bg-paper p-4 text-xs">
                @csrf
                <div class="flex items-center justify-between">
                    <strong class="text-graphite">Deliver / Revise Diet Plan for {{ $req->user?->name }} ({{ $req->dietPlan?->title }})</strong>
                    <span class="font-mono text-[11px] text-gold-dark">Status: {{ strtoupper($req->status) }}</span>
                </div>
                @if ($req->customer_revision_notes)
                    <p class="rounded-xs bg-white p-2 text-graphite border-l-2 border-gold-dark">
                        <strong>Client Revision Request:</strong> {{ $req->customer_revision_notes }}
                    </p>
                @endif
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                    <div class="col-span-2">
                        <label class="field-label text-[11px]">Version Title</label>
                        <input type="text" name="title" value="Personalised Meal Architecture — Revision v{{ ($req->deliveredVersion?->version_number ?? 0) + 1 }}" class="field-input text-xs py-1.5" required />
                    </div>
                    <div>
                        <label class="field-label text-[11px]">Kcal</label>
                        <input type="number" name="daily_calories" value="2050" class="field-input text-xs py-1.5" required />
                    </div>
                    <div>
                        <label class="field-label text-[11px]">Protein (g)</label>
                        <input type="number" name="protein_grams" value="175" class="field-input text-xs py-1.5" required />
                    </div>
                    <div>
                        <label class="field-label text-[11px]">Carbs / Fat (g)</label>
                        <div class="flex gap-1">
                            <input type="number" name="carbs_grams" value="190" class="field-input text-xs py-1.5" required />
                            <input type="number" name="fat_grams" value="62" class="field-input text-xs py-1.5" required />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <input type="text" name="meal_1_items" value="3 whole eggs + 3 egg whites, 1 whole-wheat chapati (50g atta)" class="field-input text-xs py-1.5" placeholder="Meal 1 (Breakfast)" required />
                    <input type="text" name="meal_2_items" value="200g grilled chicken tikka breast, 160g cooked basmati rice, raita" class="field-input text-xs py-1.5" placeholder="Meal 2 (Lunch)" required />
                    <input type="text" name="meal_3_items" value="1 scoop whey isolate + 1 banana + 10g almonds" class="field-input text-xs py-1.5" placeholder="Meal 3 (Pre-Workout)" required />
                    <input type="text" name="meal_4_items" value="180g lean beef qeema, 1 chapati, 1 cup moong daal, salad" class="field-input text-xs py-1.5" placeholder="Meal 4 (Dinner)" />
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" name="revision_note" placeholder="Revision note (e.g. Adjusted dinner carbs)..." class="field-input text-xs py-1.5 flex-1" />
                    <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-[11px]">Publish Diet Version & Grant Entitlement</button>
                </div>
            </form>
        @endforeach
    </section>

    {{-- Check-In Feedback, Custom Workout Assignment, Consultations & Messages --}}
    <section class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 5, 6 & 9 · Coaching Delivery & Client Feedback</span>
            <h2 class="font-display text-2xl uppercase text-graphite">Check-Ins, Workouts & Messages</h2>
        </div>

        {{-- Assign Workout Form --}}
        @if ($assignedClients->isNotEmpty() && $workoutPrograms->isNotEmpty())
            <form method="POST" action="{{ route('manage.workouts.assign') }}" class="space-y-2 rounded-xs bg-paper p-3.5 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Assign Custom Workout Program</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <select name="client_id" class="field-input text-xs py-1.5" required>
                        @foreach ($assignedClients as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->name }}</option>
                        @endforeach
                    </select>
                    <select name="workout_program_id" class="field-input text-xs py-1.5" required>
                        @foreach ($workoutPrograms as $wp)
                            <option value="{{ $wp->id }}">{{ $wp->title }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="text" name="custom_guidance" placeholder="Custom RPE / exercise modification notes..." class="field-input text-xs py-1.5" />
                <button type="submit" class="btn-light-primary w-full rounded-xs px-3 py-2 text-[11px]">Assign Workout & Unlock Entitlement</button>
            </form>
        @endif

        {{-- Client Check-In Feedback Queue --}}
        <div class="space-y-2.5 text-xs">
            <strong class="uppercase text-graphite block">Recent Client Check-Ins ({{ $assignedCheckIns->count() }})</strong>
            @foreach ($assignedCheckIns->take(3) as $ci)
                <form method="POST" action="{{ route('manage.check-ins.review', $ci) }}" class="rounded-xs border border-graphite/12 bg-paper p-3 space-y-1.5">
                    @csrf
                    <div class="flex items-center justify-between">
                        <strong>{{ $ci->user?->name }} — {{ $ci->weight_kg }} kg / {{ $ci->waist_cm ?? '—' }} cm</strong>
                        <span class="font-mono text-[10px] text-graphite/65">{{ $ci->check_in_date->format('d M Y') }}</span>
                    </div>
                    <p class="text-[11px] text-graphite/75">{{ $ci->client_notes }}</p>
                    <div class="flex gap-1.5">
                        <input type="text" name="coach_feedback" value="{{ $ci->coach_feedback }}" placeholder="Write coach check-in feedback..." class="field-input text-xs py-1 flex-1" required />
                        <button type="submit" class="rounded-xs bg-charcoal px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Save</button>
                    </div>
                </form>
            @endforeach
        </div>

        {{-- Assigned Client Messaging --}}
        @if ($assignedClients->isNotEmpty())
            <form method="POST" action="{{ route('manage.clients.messages.store', $assignedClients->first()) }}" class="space-y-2 border-t border-graphite/10 pt-3 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Send Direct Message to {{ $assignedClients->first()->name }}</strong>
                <div class="flex gap-2">
                    <input type="text" name="body" placeholder="Coach update or check-in reminder..." class="field-input text-xs py-1.5 flex-1" required />
                    <button type="submit" class="btn-light-primary rounded-xs px-3 py-1.5 text-[11px]">Send</button>
                </div>
            </form>
        @endif
    </section>
</div>
