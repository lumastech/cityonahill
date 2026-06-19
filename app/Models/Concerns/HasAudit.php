<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait HasAudit
{
    public static function bootHasAudit(): void
    {
        static::created(function (self $model): void {
            self::writeAuditLog($model, 'created', null, $model->getAttributes());
        });

        static::updated(function (self $model): void {
            self::writeAuditLog($model, 'updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function (self $model): void {
            self::writeAuditLog($model, 'deleted', $model->getAttributes(), null);
        });
    }

    private static function writeAuditLog(self $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'school_id' => $model->school_id ?? null,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }
}
