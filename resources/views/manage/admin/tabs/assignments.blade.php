{{-- Tab panel "assignments" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="assignments" x-show="tab === 'assignments'" x-cloak>
    <section id="coach-assignments" class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Coach-to-Client Assignment Model</h2>
        <p class="text-xs text-graphite/75">
            Assigns designated staff (`coach` or `manager`) to specific customers without granting full Admin privileges. Managers and Coaches can only view health/intake records of customers assigned here. Assignments requested by managers take effect only after an administrator approves them.
        </p>

        <form method="POST" action="{{ route('manage.coach-assignments.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4 border-y border-graphite/10 py-4">
            @csrf
            <div>
                <label for="assign-coach" class="field-label text-xs">Staff Coach</label>
                <select id="assign-coach" name="coach_id" class="field-input text-xs" required>
                    @foreach ($coaches as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->getRoleNames()->implode('/') }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="assign-client" class="field-label text-xs">Customer</label>
                <select id="assign-client" name="client_id" class="field-input text-xs" required>
                    @foreach ($customers as $cl)
                        <option value="{{ $cl->id }}">{{ $cl->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="assign-context" class="field-label text-xs">Scope Context</label>
                <select id="assign-context" name="context" class="field-input text-xs">
                    <option value="coaching">Online Coaching</option>
                    <option value="diet">Diet Preparation</option>
                    <option value="gym">Gym Personal Training</option>
                </select>
            </div>
            <div class="flex items-end">
                <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">Assign Coach</x-ui.button>
            </div>
        </form>

        <div class="space-y-2 text-xs">
            <h3 class="font-bold uppercase text-gold-dark">
                Assignments ({{ $assignments->count() }})
                @if ($assignments->where('status', 'pending')->count())
                    · <span class="text-danger">{{ $assignments->where('status', 'pending')->count() }} awaiting approval</span>
                @endif
            </h3>
            @foreach ($assignments as $a)
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xs px-3.5 py-2 {{ $a->status === 'pending' ? 'border border-gold-dark/40 bg-white' : 'bg-paper' }}">
                    <span>
                        <strong>{{ $a->coach?->name }}</strong> → <strong>{{ $a->client?->name }}</strong> ({{ strtoupper($a->context) }})
                        @if ($a->status === 'pending')
                            <x-ui.badge tone="gold">Pending</x-ui.badge>
                            <span class="text-graphite/60">requested by {{ $a->assigner?->name ?? 'staff' }}</span>
                        @endif
                    </span>
                    @if ($a->status === 'pending')
                        <span class="flex gap-2">
                            <form method="POST" action="{{ route('manage.coach-assignments.approve', $a) }}">
                                @csrf
                                <button type="submit" class="rounded-xs bg-ink px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('manage.coach-assignments.reject', $a) }}">
                                @csrf
                                <button type="submit" class="rounded-xs border border-graphite/25 px-2.5 py-1 text-[10px] font-bold uppercase text-graphite cursor-pointer">Reject</button>
                            </form>
                        </span>
                    @else
                        <span class="text-graphite/60">Since {{ $a->starts_at?->format('d M Y H:i') }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

</div>
