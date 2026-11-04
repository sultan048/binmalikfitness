{{-- Tab panel "sessions" of account/dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="sessions" x-show="tab === 'sessions'" x-cloak>
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 11 · 1-on-1 Consultation Booking & History</span>
            <h2 class="font-display text-2xl uppercase text-graphite">Coach Consultations ({{ $consultations->count() }})</h2>
        </div>

        @if ($consultations->isNotEmpty())
            <div class="space-y-2 text-xs">
                @foreach ($consultations as $con)
                    <div class="flex items-center justify-between rounded-xs bg-paper p-3">
                        <div>
                            <strong class="text-graphite">{{ $con->topic }}</strong>
                            <span class="block text-[11px] text-graphite/70">
                                {{ $con->scheduled_at->format('d M Y, H:i') }} PKT · {{ strtoupper(str_replace('_', ' ', $con->channel)) }}
                                @if ($con->meeting_reference) · {{ $con->meeting_reference }} @endif
                            </span>
                        </div>
                        <span class="rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $con->status }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('account.coaching.consultations.store') }}" class="grid grid-cols-1 gap-2.5 border-t border-graphite/10 pt-3 sm:grid-cols-2 text-xs">
            @csrf
            <div>
                <label class="field-label text-xs">Preferred Date & Time</label>
                <input type="datetime-local" name="scheduled_at" value="{{ now()->addDays(4)->format('Y-m-d\TH:i') }}" class="field-input text-xs py-1.5" required />
            </div>
            <div>
                <label class="field-label text-xs">Channel</label>
                <select name="channel" class="field-input text-xs py-1.5">
                    <option value="whatsapp_video">WhatsApp Video Call</option>
                    <option value="zoom">Zoom Video Session</option>
                    <option value="in_person_gym">In-Person Facility Consultation</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="field-label text-xs">Consultation Topic</label>
                <input type="text" name="topic" placeholder="e.g. Form review & next mesocycle targets" class="field-input text-xs py-1.5" required />
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs">Request 1-on-1 Consultation</button>
            </div>
        </form>
    </section>

    {{-- Secure Customer-Coach Messaging --}}
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Direct Portal Thread · Scoped to Assigned Coach</span>
            <h2 class="font-display text-2xl uppercase text-graphite">Secure Coach Messaging ({{ $coachingMessages->count() }})</h2>
        </div>

        <div class="max-h-56 overflow-y-auto space-y-2 rounded-xs bg-paper p-3 text-xs">
            @forelse ($coachingMessages as $msg)
                <div class="rounded-xs {{ $msg->sender_id === $user->id ? 'bg-white border border-graphite/15' : 'bg-charcoal text-ivory' }} p-2.5">
                    <div class="flex items-center justify-between text-[10px] opacity-75">
                        <strong>{{ $msg->sender?->name ?? 'User' }}</strong>
                        <span>{{ $msg->created_at?->format('d M H:i') }}</span>
                    </div>
                    <p class="mt-1">{{ $msg->body }}</p>
                </div>
            @empty
                <p class="text-graphite/70">No messages yet. Send a question to your assigned coach below.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('account.coaching.messages.store') }}" class="flex gap-2 text-xs">
            @csrf
            @if ($activeSubscription)
                <input type="hidden" name="coaching_subscription_id" value="{{ $activeSubscription->id }}" />
            @endif
            <input type="text" name="body" placeholder="Ask your assigned coach about form, macros, or food swaps..." class="field-input text-xs py-2 flex-1" required />
            <button type="submit" class="btn-light-primary shrink-0 rounded-xs px-4 py-2 text-xs">Send Message</button>
        </form>
    </section>
</div>
