<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(RolesAndPermissionsSeeder::class);
        // $this->call(LeaveTypesSeeder::class);
        $this->call(MenuSeeder::class);

        // No admin user or school is seeded: on a fresh install the first
        // registered user becomes super-admin and is guided through the
        // /setup wizard to create the first branch. For a local demo run:
        //   php artisan db:seed --class=DemoSchoolSeeder
    }
}
