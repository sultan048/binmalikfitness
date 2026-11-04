<?php

namespace App\Console\Commands;

use App\Domain\Platform\DataRetentionAndSecurityService;
use Illuminate\Console\Command;

class PruneSecurityData extends Command
{
    protected $signature = 'security:prune';

    protected $description = 'Delete expired password-reset tokens, idempotency keys, stale guest carts and old audit logs';

    public function handle(DataRetentionAndSecurityService $retention): int
    {
        $summary = $retention->pruneExpiredSecurityTokens();
        $summary['old_audit_logs'] = $retention->pruneAuditLogs((int) config('app.audit_retention_days'));

        foreach ($summary as $label => $count) {
            $this->line(str_pad($label, 26).$count);
        }

        return self::SUCCESS;
    }
}
