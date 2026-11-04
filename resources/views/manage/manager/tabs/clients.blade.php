{{-- Tab panel "clients" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
<section id="assigned-clients" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4" data-tab-panel="clients" x-show="tab === 'clients'" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="font-display text-2xl uppercase text-graphite">My Assigned Coaching & Nutrition Clients</h2>
            <p class="text-xs text-graphite/70">
                You can only access confidential intake profiles and private progress files for customers explicitly assigned to you in <code class="font-mono">coach_assignments</code>.
            </p>
        </div>
        <x-ui.badge tone="gold">Assignment Scoped</x-ui.badge>
    </div>

    @if ($assignedClients->isEmpty())
        <x-ui.empty-state
            title="No Coaching Clients Currently Assigned"
            description="When an Admin or Manager assigns a coaching or personalised diet client to your staff profile, their intake and check-in records appear here."
            surface="light"
        />
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-graphite/12 text-gold-dark uppercase">
                        <th class="py-2 pr-3">Client Name</th>
                        <th class="py-2 px-3">Goal & Experience</th>
                        <th class="py-2 px-3">Private Uploads</th>
                        <th class="py-2 pl-3">Authorized Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite/10">
                    @foreach ($assignedClients as $cl)
                        <tr>
                            <td class="py-3 pr-3 font-semibold text-graphite">
                                {{ $cl->name }}
                                <span class="block text-[11px] font-normal text-graphite/65">{{ $cl->email }}</span>
                            </td>
                            <td class="py-3 px-3">
                                {{ $cl->customerProfile?->fitness_goal ?? 'Pending Intake' }}
                                <span class="block text-[11px] text-graphite/65">{{ $cl->customerProfile?->experience_level }}</span>
                            </td>
                            <td class="py-3 px-3">{{ $cl->privateFiles->count() }} private file(s)</td>
                            <td class="py-3 pl-3">
                                <x-ui.button :href="route('manage.clients.confidential', $cl)" variant="graphite" size="sm">
                                    Open Confidential File
                                </x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
