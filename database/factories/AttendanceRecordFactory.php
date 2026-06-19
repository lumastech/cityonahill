<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Pupil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id' => AttendanceSession::factory(),
            'pupil_id' => Pupil::factory(),
            'status' => 'present',
            'remarks' => null,
        ];
    }
}
