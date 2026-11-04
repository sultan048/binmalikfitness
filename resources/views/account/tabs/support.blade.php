{{-- Tab panel "support" of account/dashboard (included from the parent view, shares its variables) --}}
<div id="support-tickets" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="support" x-show="tab === 'support'" x-cloak>
    <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Customer Help Desk &amp; Escalations</span>
                <h2 class="font-display text-2xl uppercase text-graphite">My Support Tickets ({{ $supportTickets->count() }})</h2>
            </div>
            <x-ui.badge tone="gold">Direct Staff Support</x-ui.badge>
        </div>

        <div class="space-y-3 text-xs">
            @forelse ($supportTickets as $ticket)
                <div class="rounded-xs border border-graphite/15 bg-paper p-4 space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold text-gold-dark">{{ $ticket->ticket_number }}</span>
                            <span class="ml-1 rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $ticket->category }}</span>
                            <strong class="block mt-1 text-sm text-graphite">{{ $ticket->subject }}</strong>
                        </div>
                        <x-ui.badge :tone="$ticket->status === 'resolved' ? 'success' : 'gold'">{{ $ticket->status }}</x-ui.badge>
                    </div>

                    <div class="space-y-1.5 rounded-xs bg-white p-2.5 border border-graphite/10">
                        @foreach ($ticket->messages as $msg)
                            <div class="rounded-xs bg-paper p-2">
                                <div class="flex items-center justify-between text-[10px] text-graphite/65">
                                    <strong>{{ $msg->sender?->name }}</strong>
                                    <span>{{ $msg->created_at?->diffForHumans() }}</span>
                                </div>
                                <p class="mt-0.5 text-graphite">{{ $msg->message }}</p>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('account.support-tickets.reply', $ticket) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="text" name="message" placeholder="Add a reply to this support ticket..." class="field-input py-1.5 text-xs flex-1" required />
                        <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 font-bold uppercase text-gold cursor-pointer">Reply</button>
                    </form>
                </div>
            @empty
                <p class="rounded-xs bg-paper p-4 text-graphite/70">You have no open support tickets. Submit a question below if you need help with an order, diet plan, or coaching subscription.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('account.support-tickets.store') }}" class="rounded-xs bg-paper p-4 space-y-2.5 text-xs border-t border-graphite/10">
            @csrf
            <strong class="uppercase text-graphite block">Open New Support Ticket</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <select name="category" class="field-input py-1.5 text-xs">
                    <option value="coaching">Online Coaching</option>
                    <option value="order">Supplement Order</option>
                    <option value="diet">Digital Diet Plan</option>
                    <option value="gym">Gym Membership</option>
                    <option value="billing">Billing &amp; Payment</option>
                </select>
                <select name="priority" class="field-input py-1.5 text-xs">
                    <option value="normal">Normal Priority</option>
                    <option value="high">High Priority</option>
                </select>
                <select name="order_id" class="field-input py-1.5 text-xs">
                    <option value="">Optional: Link Order</option>
                    @foreach ($orders as $ordOpt)
                        <option value="{{ $ordOpt->id }}">{{ $ordOpt->order_number }}</option>
                    @endforeach
                </select>
            </div>
            <input type="text" name="subject" placeholder="Brief subject of your enquiry..." class="field-input py-1.5 text-xs" required />
            <textarea name="message" rows="2" placeholder="Describe your question or issue..." class="field-input py-1.5 text-xs" required></textarea>
            <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs w-full">Submit Support Ticket</button>
        </form>
    </section>

    <section id="privacy-governance" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 16 · Privacy, Consents &amp; Data Export</span>
                <h2 class="font-display text-2xl uppercase text-graphite">My Recorded Consents &amp; Data Rights</h2>
            </div>
            <x-ui.badge tone="light">Audited</x-ui.badge>
        </div>

        <div class="space-y-2 text-xs">
            <strong class="uppercase text-gold-dark block">Active &amp; Historical Consent Records ({{ $consentRecords->count() }})</strong>
            @foreach ($consentRecords as $cr)
                <div class="flex items-center justify-between rounded-xs border border-graphite/10 bg-paper px-3 py-2">
                    <div>
                        <strong class="font-mono text-graphite">{{ $cr->consent_type }}</strong> ({{ $cr->version }})
                        <span class="block text-[10px] text-graphite/65">Granted {{ $cr->granted_at?->format('d M Y') }}</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase {{ $cr->withdrawn_at ? 'text-danger' : 'text-success' }}">
                        {{ $cr->withdrawn_at ? 'Withdrawn' : 'Active' }}
                    </span>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('account.privacy-requests.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Submit Privacy / Data Export / Consent Withdrawal Request</strong>
            <select name="request_type" class="field-input py-1.5 text-xs">
                <option value="data_export">Portable Personal &amp; Training Data Export</option>
                <option value="consent_withdrawal">Withdraw a Specific Consent Record</option>
                <option value="account_deletion_enquiry">Account Deletion / Anonymisation Enquiry</option>
            </select>
            <select name="consent_record_id" class="field-input py-1.5 text-xs">
                <option value="">Optional: Select Consent Record to Withdraw...</option>
                @foreach ($consentRecords->whereNull('withdrawn_at') as $activeCr)
                    <option value="{{ $activeCr->id }}">Consent #{{ $activeCr->id }} ({{ $activeCr->consent_type }})</option>
                @endforeach
            </select>
            <input type="text" name="customer_notes" placeholder="Notes or reason for request..." class="field-input py-1.5 text-xs" />
            <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Submit Privacy Request</button>
        </form>

        @if ($privacyRequests->isNotEmpty())
            <div class="space-y-1.5 text-xs border-t border-graphite/10 pt-3">
                <strong class="uppercase text-graphite block">My Privacy Request History</strong>
                @foreach ($privacyRequests as $pr)
                    <div class="rounded-xs bg-paper px-3 py-2 flex items-center justify-between">
                        <span><strong>{{ $pr->request_type }}</strong> — {{ $pr->customer_notes }}</span>
                        <x-ui.badge :tone="$pr->status === 'completed' ? 'success' : 'gold'">{{ $pr->status }}</x-ui.badge>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
