<?php

namespace App\Domain\Platform;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $actor = null,
    ): AuditLog {
        $resolvedActor = $actor ?? ($this->request?->user());

        return AuditLog::create([
            'actor_id' => $resolvedActor?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request ? substr((string) $this->request->userAgent(), 0, 255) : null,
            'created_at' => now(),
        ]);
    }
}
