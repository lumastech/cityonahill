<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\School;
use Illuminate\Database\Seeder;

class LeaveTypesSeeder extends Seeder
{
    private array $types = [
        ['name' => 'Annual Leave',        'days_per_year' => 21, 'accrues' => true],
        ['name' => 'Sick Leave',          'days_per_year' => 14, 'accrues' => false],
        ['name' => 'Maternity Leave',     'days_per_year' => 84, 'accrues' => false],
        ['name' => 'Paternity Leave',     'days_per_year' => 5,  'accrues' => false],
        ['name' => 'Compassionate Leave', 'days_per_year' => 3,  'accrues' => false],
        ['name' => 'Study Leave',         'days_per_year' => 10, 'accrues' => false],
        ['name' => 'Unpaid Leave',        'days_per_year' => 0,  'accrues' => false],
    ];

    public function run(): void
    {
        School::all()->each(function (School $school) {
            foreach ($this->types as $type) {
                LeaveType::firstOrCreate(
                    ['school_id' => $school->id, 'name' => $type['name']],
                    ['days_per_year' => $type['days_per_year'], 'accrues' => $type['accrues']]
                );
            }
        });
    }
}
