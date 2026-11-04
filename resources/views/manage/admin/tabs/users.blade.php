{{-- Tab panel "users" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<section id="users-staff" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4" data-tab-panel="users" x-show="tab === 'users'" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="font-display text-2xl uppercase text-graphite">User, Staff & Role Administration</h2>
            <p class="text-xs text-graphite/70">Admin-only role assignment & account activation/deactivation (deactivating a user immediately terminates their active sessions).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('manage.admin') }}#users" class="flex items-center gap-2 text-xs">
                <label for="user-search" class="sr-only">Search users</label>
                <input id="user-search" type="search" name="user_search" value="{{ $filterUserSearch }}" placeholder="Name, email or phone..." class="field-input py-1 text-xs w-48" />
                <button type="submit" class="rounded-xs bg-ink px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Search</button>
            </form>
            <a href="{{ route('manage.exports.csv', 'customers') }}" class="text-[11px] font-bold uppercase text-gold-dark hover:underline">Customers CSV</a>
            <x-ui.badge tone="gold">UserPolicy Enforced</x-ui.badge>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-graphite/15 text-gold-dark uppercase">
                    <th class="py-2.5 pr-3">Name & Email</th>
                    <th class="py-2.5 px-3">Current Roles</th>
                    <th class="py-2.5 px-3">Status</th>
                    <th class="py-2.5 px-3">Update Roles (Admin Only)</th>
                    <th class="py-2.5 pl-3">Account Status / Confidential</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-graphite/10">
                @foreach ($users as $u)
                    <tr>
                        <td class="py-3 pr-3">
                            <span class="font-bold text-graphite block">{{ $u->name }}</span>
                            <span class="text-graphite/65">{{ $u->email }}</span>
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($u->getRoleNames() as $rName)
                                    <span class="rounded-xs bg-graphite px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $rName }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <span class="font-semibold uppercase {{ $u->status === 'active' ? 'text-success' : 'text-danger' }}">
                                {{ $u->status }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            @if (auth()->id() !== $u->id)
                                <form method="POST" action="{{ route('manage.users.roles.update', $u) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    @method('PUT')
                                    <select name="roles[]" class="field-input py-1 text-xs w-36" aria-label="Role for {{ $u->name }}">
                                        @foreach (['customer', 'coach', 'manager', 'admin'] as $roleOpt)
                                            <option value="{{ $roleOpt }}" @selected($u->hasRole($roleOpt))>{{ strtoupper($roleOpt) }}</option>
                                        @endforeach
                                    </select>
                                    <x-ui.button type="submit" variant="graphite" size="sm">Save</x-ui.button>
                                </form>
                            @else
                                <span class="text-[11px] text-graphite/55 italic">Current Owner Session</span>
                            @endif
                        </td>
                        <td class="py-3 pl-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @if (auth()->id() !== $u->id)
                                    <form method="POST" action="{{ route('manage.users.status.update', $u) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="{{ $u->status === 'active' ? 'deactivated' : 'active' }}" />
                                        <x-ui.button type="submit" variant="outline-dark" size="sm">
                                            {{ $u->status === 'active' ? 'Deactivate' : 'Reactivate' }}
                                        </x-ui.button>
                                    </form>
                                @endif
                                <a href="{{ route('manage.clients.confidential', $u) }}" class="text-xs font-semibold text-gold-dark hover:underline">
                                    Inspect File →
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</section>
