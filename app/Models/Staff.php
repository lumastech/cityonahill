<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'school_id',
        'employee_no',
        'position',
        'department',
        'subjects_taught',
        'employment_type',
        'employment_date',
        'end_date',
        'basic_salary',
        'bank',
        'bank_account',
        'bank_branch',
        'nrc',
        'tax_id',
        'tpin',
        'napsa_no',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subjects_taught' => 'array',
            'employment_date' => 'date',
            'end_date' => 'date',
            'basic_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function scopeTeachers(Builder $query): Builder
    {
        return $query->whereIn('position', [
            'class_teacher', 'subject_teacher', 'headteacher', 'deputy_headteacher',
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? '';
    }

    public function getSubjectsTaughtAttribute(): array
    {
        return $this->attributes['subjects_taught']
            ? json_decode($this->attributes['subjects_taught'], true)
            : [];
    }
}
