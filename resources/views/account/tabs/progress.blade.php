{{-- Tab panel "progress" of account/dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="progress" x-show="tab === 'progress'" x-cloak>
    <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 9 & 10 · Private Outcome Tracking & Coach Feedback</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Weight & Waist Progress Chart ({{ $checkIns->count() }} Check-Ins)</h2>
            </div>
            <span class="rounded-xs bg-paper px-2.5 py-1 text-[11px] font-semibold text-graphite">Private by Default</span>
        </div>

        @if ($checkIns->isNotEmpty())
            @php
                $chronological = $checkIns->sortBy('check_in_date')->values();
                $firstCheckIn = $chronological->first();
                $latestCheckIn = $chronological->last();
                $weightDelta = round($latestCheckIn->weight_kg - $firstCheckIn->weight_kg, 1);
                $waistDelta = ($latestCheckIn->waist_cm && $firstCheckIn->waist_cm)
                    ? round($latestCheckIn->waist_cm - $firstCheckIn->waist_cm, 1)
                    : null;
            @endphp
            <div class="grid grid-cols-3 gap-3 rounded-xs bg-charcoal p-4 text-xs text-ivory">
                <div>
                    <span class="text-silver block">Starting → Latest Weight</span>
                    <strong class="font-mono text-base text-gold">{{ $firstCheckIn->weight_kg }} kg → {{ $latestCheckIn->weight_kg }} kg ({{ $weightDelta > 0 ? '+'.$weightDelta : $weightDelta }} kg)</strong>
                </div>
                <div>
                    <span class="text-silver block">Waist Circumference Delta</span>
                    <strong class="font-mono text-base text-ivory">{{ $latestCheckIn->waist_cm ?? '—' }} cm {{ $waistDelta !== null ? '('.($waistDelta > 0 ? '+'.$waistDelta : $waistDelta).' cm)' : '' }}</strong>
                </div>
                <div>
                    <span class="text-silver block">Avg Adherence & Energy</span>
                    <strong class="font-mono text-base text-gold">{{ round($checkIns->avg('diet_adherence_score'), 1) }}/10 · {{ round($checkIns->avg('energy_score'), 1) }}/10</strong>
                </div>
            </div>

            {{-- Visual Bar Progression Chart --}}
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                @foreach ($chronological as $ci)
                    <div class="rounded-xs border border-graphite/12 bg-paper p-3 space-y-1 text-xs">
                        <div class="flex items-center justify-between font-mono text-[11px] text-graphite/70">
                            <span>{{ $ci->check_in_date->format('d M Y') }}</span>
                            <span class="font-bold text-gold-dark">{{ $ci-> workouts_completed_this_week }} workouts</span>
                        </div>
                        <div class="font-display text-2xl text-graphite">{{ $ci->weight_kg }} kg <span class="text-xs font-sans text-graphite/70">/ {{ $ci->waist_cm ?? '—' }} cm waist</span></div>
                        <p class="text-[11px] text-graphite/80">{{ $ci->client_notes }}</p>
                        @if ($ci->coach_feedback)
                            <p class="rounded-xs bg-white p-2 text-[11px] text-graphite border-l-2 border-gold-dark">
                                <strong>Coach Feedback:</strong> {{ $ci->coach_feedback }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-graphite/70">Submit your first weekly check-in on the right to start tracking weight, waist, and adherence trends.</p>
        @endif
    </section>

    {{-- Submit Weekly Check-In Form --}}
    <section class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Submit Weekly Check-In & Measurements</h2>
        <form method="POST" action="{{ route('account.coaching.check-ins.store') }}" enctype="multipart/form-data" class="space-y-3 text-xs">
            @csrf
            @if ($activeSubscription)
                <input type="hidden" name="coaching_subscription_id" value="{{ $activeSubscription->id }}" />
            @endif
            <div class="grid grid-cols-3 gap-2.5">
                <div>
                    <label class="field-label text-xs">Weight (kg)*</label>
                    <input type="number" step="0.1" name="weight_kg" value="83.4" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">Waist (cm)</label>
                    <input type="number" step="0.1" name="waist_cm" value="88.5" class="field-input text-xs py-1.5" />
                </div>
                <div>
                    <label class="field-label text-xs">Chest (cm)</label>
                    <input type="number" step="0.1" name="chest_cm" value="106.0" class="field-input text-xs py-1.5" />
                </div>
                <div>
                    <label class="field-label text-xs">Adherence (1-10)</label>
                    <input type="number" name="diet_adherence_score" value="9" min="1" max="10" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">Energy (1-10)</label>
                    <input type="number" name="energy_score" value="9" min="1" max="10" class="field-input text-xs py-1.5" required />
                </div>
                <div>
                    <label class="field-label text-xs">Workouts Done</label>
                    <input type="number" name="workouts_completed_this_week" value="4" min="0" max="14" class="field-input text-xs py-1.5" required />
                </div>
            </div>
            <div>
                <label class="field-label text-xs">Biofeedback, Sleep & Training Notes</label>
                <textarea name="client_notes" rows="2" placeholder="Digestion, sleep quality, strength progression..." class="field-input text-xs"></textarea>
            </div>
            <div>
                <label class="field-label text-xs">Optional Private Progress Photo (Stored Outside /public)</label>
                <input type="file" name="progress_photo" accept=".jpg,.jpeg,.png,.webp" class="field-input text-xs py-1.5" />
            </div>
            <label class="flex items-start gap-2 text-[11px] text-graphite/80">
                <input type="checkbox" name="photo_storage_consent" value="1" checked class="mt-0.5" />
                <span>Consent to store progress photo privately for my assigned coach only (Never displayed publicly unless separately consented).</span>
            </label>
            <button type="submit" class="btn-light-primary w-full rounded-xs px-4 py-2.5 text-xs">
                Record Weekly Check-In
            </button>
        </form>
    </section>
</div>
