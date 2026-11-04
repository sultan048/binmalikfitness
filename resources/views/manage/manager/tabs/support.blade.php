{{-- Tab panel "support" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
<div id="support-tickets" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="support" x-show="tab === 'support'" x-cloak>
    <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 10 · Customer Support Queue</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Assigned &amp; Operational Support Tickets</h2>
            </div>
            <x-ui.badge tone="gold">{{ $supportTickets->total() }} Tickets</x-ui.badge>
        </div>

        <div class="space-y-3 text-xs">
            @foreach ($supportTickets as $ticket)
                <div class="rounded-xs border border-graphite/15 bg-paper p-3.5 space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold text-gold-dark">{{ $ticket->ticket_number }}</span>
                            <span class="ml-1 rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $ticket->category }}</span>
                            <strong class="block mt-1 text-graphite">{{ $ticket->subject }}</strong>
                            <span class="text-[11px] text-graphite/70">Customer: {{ $ticket->user?->name }}</span>
                        </div>
                        <x-ui.badge :tone="$ticket->status === 'resolved' ? 'success' : 'gold'">{{ $ticket->status }}</x-ui.badge>
                    </div>

                    <div class="max-h-32 overflow-y-auto space-y-1 rounded-xs bg-white p-2 border border-graphite/10">
                        @foreach ($ticket->messages as $msg)
                            <div class="rounded-xs p-1.5 {{ $msg->is_internal_note ? 'bg-amber-50 border border-amber-300' : 'bg-paper' }}">
                                <strong class="text-[10px] text-graphite/70">{{ $msg->sender?->name }} @if ($msg->is_internal_note) [INTERNAL NOTE] @endif:</strong>
                                <p class="text-graphite">{{ $msg->message }}</p>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('manage.support-tickets.reply', $ticket) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="text" name="message" placeholder="Write response or internal note..." class="field-input py-1 text-xs flex-1" required />
                        <label class="inline-flex items-center gap-1 text-[11px]">
                            <input type="checkbox" name="is_internal_note" value="1" /> Internal
                        </label>
                        <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 font-bold uppercase text-gold cursor-pointer">Reply</button>
                    </form>
                </div>
            @endforeach
        </div>
        {{ $supportTickets->links() }}
    </section>

</div>
