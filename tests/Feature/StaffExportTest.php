<?php

use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->school = School::factory()->create(['code' => 'ZSS']);

    $staffUser = User::factory()->create([
        'school_id' => $this->school->id,
        'name'      => 'Grace Banda',
        'email'     => 'grace@example.com',
    ]);

    Staff::factory()->create([
        'school_id'    => $this->school->id,
        'user_id'      => $staffUser->id,
        'employee_no'  => 'EMP-001',
        'basic_salary' => 10_000,
        'bank_account' => '1234567890',
        'nrc'          => '111111/11/1',
        'status'       => 'active',
    ]);
});

function actingAsRole(string $role): User
{
    $user = User::factory()->create(['school_id' => test()->school->id]);
    $user->assignRole($role);

    return $user;
}

it('exports the full staff CSV including payroll columns for a headteacher', function () {
    $response = $this->actingAs(actingAsRole('headteacher'))->get(route('staff.export'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Grace Banda')
        ->and($csv)->toContain('EMP-001')
        ->and($csv)->toContain('Basic Salary')
        ->and($csv)->toContain('1234567890')
        ->and($csv)->toContain('111111/11/1');
});

it('omits salary, bank and NRC columns for a deputy-headteacher', function () {
    $response = $this->actingAs(actingAsRole('deputy-headteacher'))->get(route('staff.export'));

    $response->assertOk();

    $csv = $response->streamedContent();

    // Directory data is still there...
    expect($csv)->toContain('Grace Banda')
        ->and($csv)->toContain('EMP-001')
        // ...but nothing they cannot already see via payroll.
        ->and($csv)->not->toContain('Basic Salary')
        ->and($csv)->not->toContain('1234567890')
        ->and($csv)->not->toContain('111111/11/1');
});

it('forbids export for a role without staff.view', function () {
    $this->actingAs(actingAsRole('class-teacher'))
        ->get(route('staff.export'))
        ->assertForbidden();
});

it('escapes leading formula characters so spreadsheets do not evaluate cells', function () {
    $attacker = User::factory()->create([
        'school_id' => $this->school->id,
        'name'      => '=cmd|calc',
    ]);

    Staff::factory()->create([
        'school_id'   => $this->school->id,
        'user_id'     => $attacker->id,
        'employee_no' => 'EMP-002',
        'status'      => 'active',
    ]);

    $csv = $this->actingAs(actingAsRole('headteacher'))
        ->get(route('staff.export'))
        ->streamedContent();

    expect($csv)->toContain("'=cmd|calc")
        ->and($csv)->not->toMatch('/^=cmd/m');
});
