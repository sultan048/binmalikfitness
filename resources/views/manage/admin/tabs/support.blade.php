{{-- Tab panel "support" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<section id="support-tickets" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-5" data-tab-panel="support" x-show="tab === 'support'" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 17 · Customer Support Desk &amp; Escalations</span>
            <h2 class="font-display text-2xl uppercase text-graphite">Support Tickets, Internal Staff Notes &amp; SLA Status</h2>
            <a href="{{ route('manage.exports.csv', 'support-tickets') }}" class="text-[11px] font-bold uppercase text-gold-dark hover:underline">Export CSV</a>
        </div>
        <form method="GET" action="{{ route('manage.admin') }}#support-tickets" class="flex items-center gap-2 text-xs">
            <select name="ticket_status" class="field-input py-1 text-xs">
                <option value="">All Ticket Statuses</option>
                @foreach (['open', 'in_progress', 'awaiting_customer', 'resolved', 'closed'] as $tStatus)
                    <option value="{{ $tStatus }}" @selected($filterTicketStatus === $tStatus)>{{ strtoupper($tStatus) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 font-bold uppercase text-gold cursor-pointer">Filter</button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($supportTickets as $ticket)
            <div class="rounded-xs border border-graphite/15 bg-paper p-4 space-y-3 text-xs">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="font-mono font-bold text-gold-dark">{{ $ticket->ticket_number }}</span>
                        <span class="ml-1.5 rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $ticket->category }} · {{ $ticket->priority }}</span>
                        <h3 class="mt-1 font-semibold text-sm text-graphite">{{ $ticket->subject }}</h3>
                        <p class="text-[11px] text-graphite/70">
                            Customer: <strong>{{ $ticket->user?->name }}</strong>
                            @if ($ticket->order) · Order: {{ $ticket->order->order_number }} @endif
                        </p>
                    </div>
                    <x-ui.badge :tone="$ticket->status === 'resolved' ? 'success' : 'gold'">{{ $ticket->status }}</x-ui.badge>
                </div>

                <div class="max-h-40 overflow-y-auto space-y-1.5 rounded-xs bg-white p-2.5 border border-graphite/10">
                    @foreach ($ticket->messages as $msg)
                        <div class="rounded-xs p-2 {{ $msg->is_internal_note ? 'bg-amber-50 border border-amber-300' : 'bg-paper' }}">
                            <div class="flex items-center justify-between text-[10px] text-graphite/65">
                                <strong>{{ $msg->sender?->name }} @if ($msg->is_internal_note) [INTERNAL STAFF NOTE] @endif</strong>
                                <span>{{ $msg->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="mt-0.5 text-graphite">{{ $msg->message }}</p>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('manage.support-tickets.update', $ticket) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                    @csrf
                    @method('PUT')
                    <select name="status" class="field-input py-1 text-xs">
                        @foreach (['open', 'in_progress', 'awaiting_customer', 'resolved', 'closed'] as $st)
                            <option value="{{ $st }}" @selected($ticket->status === $st)>{{ strtoupper($st) }}</option>
                        @endforeach
                    </select>
                    <select name="priority" class="field-input py-1 text-xs">
                        @foreach (['low', 'normal', 'high', 'urgent'] as $pr)
                            <option value="{{ $pr }}" @selected($ticket->priority === $pr)>{{ strtoupper($pr) }}</option>
                        @endforeach
                    </select>
                    <select name="assigned_to" class="field-input py-1 text-xs">
                        <option value="">Unassigned</option>
                        @foreach ($coaches as $staffUser)
                            <option value="{{ $staffUser->id }}" @selected($ticket->assigned_to === $staffUser->id)>{{ $staffUser->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 font-bold uppercase text-gold cursor-pointer">Update Status</button>
                </form>

                <form method="POST" action="{{ route('manage.support-tickets.reply', $ticket) }}" class="space-y-2 border-t border-graphite/10 pt-2">
                    @csrf
                    <input type="text" name="message" placeholder="Reply to customer or record internal staff note..." class="field-input py-1.5 text-xs" required />
                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center gap-1.5 text-[11px] text-graphite/80">
                            <input type="checkbox" name="is_internal_note" value="1" /> Internal Staff Note (Hidden from Customer)
                        </label>
                        <button type="submit" class="btn-light-primary rounded-xs px-3 py-1 text-xs">Send Reply</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
    {{ $supportTickets->links() }}
</section>
