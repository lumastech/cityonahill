<?php

namespace App\Models;

use App\Enums\ApplicationStatusEnum;
use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolApplication extends Model
{
    use HasAudit;

    protected $fillable = [
        'applicant_id',
        'status',
        'school_name',
        'subdomain',
        'type',
        'level',
        'province',
        'district',
        'address',
        'contact_phone',
        'contact_email',
        'moe_registration_no',
        'headteacher_name',
        'modules_config',
        'mobile_money_number',
        'reviewer_id',
        'reviewer_notes',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status'         => ApplicationStatusEnum::class,
            'modules_config' => 'array',
            'reviewed_at'    => 'datetime',
            'submitted_at'   => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return HasMany<SchoolApplicationLog> */
    public function logs(): HasMany
    {
        return $this->hasMany(SchoolApplicationLog::class, 'application_id');
    }

    public function isPending(): bool
    {
        return $this->status === ApplicationStatusEnum::Pending;
    }

    public function isNeedsInfo(): bool
    {
        return $this->status === ApplicationStatusEnum::NeedsInfo;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            ApplicationStatusEnum::Pending,
            ApplicationStatusEnum::NeedsInfo,
        ]);
    }
}
