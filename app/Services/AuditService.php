<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(string $action, Model $subject, ?array $before = null, ?array $after = null): void
    {
        AuditLog::query()->create([
            'organization_id' => app()->bound(Organization::class) ? app(Organization::class)->id : null,
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 1000),
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
