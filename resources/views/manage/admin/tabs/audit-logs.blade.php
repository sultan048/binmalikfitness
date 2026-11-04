{{-- Tab panel "audit" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<section id="audit-logs" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-5" data-tab-panel="audit" x-show="tab === 'audit'" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 20 · System Health, Queue/Storage Monitor &amp; Audit Trail</span>
            <h2 class="font-display text-2xl uppercase text-graphite">Operational Health &amp; Security Audit Logs</h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('manage.security.prune-tokens') }}">
                @csrf
                <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 text-xs font-bold uppercase text-gold cursor-pointer">
                    Prune Expired Tokens &amp; Guest Carts
                </button>
            </form>
            <x-ui.badge tone="success">
                DB: {{ $systemHealth['db_connected'] ? 'Connected' : 'Error' }} · Private Disk: {{ $systemHealth['private_disk_writable'] ? 'Writable' : 'Check Perms' }}
            </x-ui.badge>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 text-xs">
        <div class="rounded-xs bg-paper p-3">
            <span class="text-[10px] uppercase text-graphite/65 block">Runtime Stack</span>
            <strong class="text-graphite">PHP {{ $systemHealth['php_version'] }} / Laravel {{ $systemHealth['laravel_version'] }}</strong>
        </div>
        <div class="rounded-xs bg-paper p-3">
            <span class="text-[10px] uppercase text-graphite/65 block">Encrypted Private Files</span>
            <strong class="text-graphite">{{ $systemHealth['private_files_count'] }} Protected Files</strong>
        </div>
        <div class="rounded-xs bg-paper p-3">
            <span class="text-[10px] uppercase text-graphite/65 block">Queue Health ({{ $systemHealth['queue_driver'] }})</span>
            <strong class="text-graphite">{{ $systemHealth['pending_jobs_count'] }} Pending · {{ $systemHealth['failed_jobs_count'] }} Failed</strong>
        </div>
        <div class="rounded-xs bg-paper p-3">
            <span class="text-[10px] uppercase text-graphite/65 block">Recorded Audit Events</span>
            <strong class="text-graphite">{{ $systemHealth['audit_logs_count'] }} Immutable Logs</strong>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-graphite/12 text-gold-dark uppercase">
                    <th class="py-2 pr-3">Timestamp</th>
                    <th class="py-2 px-3">Actor</th>
                    <th class="py-2 px-3">Action</th>
                    <th class="py-2 px-3">Subject</th>
                    <th class="py-2 pl-3">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-graphite/10">
                @foreach ($auditLogs as $log)
                    <tr>
                        <td class="py-2 pr-3 font-mono text-[11px]">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="py-2 px-3 font-semibold">{{ $log->actor?->name ?? 'System / Guest' }}</td>
                        <td class="py-2 px-3 font-mono text-gold-dark">{{ $log->action }}</td>
                        <td class="py-2 px-3 text-graphite/70">{{ class_basename((string) $log->subject_type) }} #{{ $log->subject_id }}</td>
                        <td class="py-2 pl-3 font-mono text-[11px] text-graphite/60">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
