<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payroll';

    protected $fillable = [
        'school_id',
        'staff_id',
        'month',
        'year',
        'basic_salary',
        'allowances',
        'deductions',
        'napsa_employee',
        'napsa_employer',
        'paye',
        'net_pay',
        'paid_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'deductions' => 'decimal:2',
            'napsa_employee' => 'decimal:2',
            'napsa_employer' => 'decimal:2',
            'paye' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
