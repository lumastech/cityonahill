<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Build from NavService config — each group + its items
        foreach ($this->menuConfig() as $position => $group) {
            $parent = Menu::firstOrCreate(
                ['name' => $group['name'], 'parent_id' => null],
                ['icon' => $group['icon'], 'position' => $position, 'is_active' => true]
            );

            foreach ($group['items'] as $itemPos => $item) {
                Menu::firstOrCreate(
                    ['route' => $item['route'], 'parent_id' => $parent->id],
                    ['name' => $item['name'], 'icon' => 'circle', 'position' => $itemPos, 'is_active' => true]
                );
            }
        }

        $this->assignToRoles();

        app(\App\Services\MenuService::class)->clearAllCache();
    }

    private function assignToRoles(): void
    {
        $allMenuIds = Menu::pluck('id')->all();

        // Super-admin sees everything
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->menus()->sync($allMenuIds);
        }

        // Role → menu group names they should see.
        // Role names must match those in RolesAndPermissionsSeeder (kebab-case).
        $roleMenuGroups = [
            'headteacher' => [
                'Academic', 'Exams & Results', 'Staff & HR', 'Finance',
                'Campus', 'Communication', 'Admin',
            ],
            'school-admin' => [
                'Academic', 'Exams & Results', 'Staff & HR', 'Finance',
                'Campus', 'Communication', 'Admin',
            ],
            'deputy-headteacher' => [
                'Academic', 'Exams & Results', 'Staff & HR', 'Finance',
                'Campus', 'Communication',
            ],
            'class-teacher' => ['Academic', 'Exams & Results', 'Communication'],
            'subject-teacher' => ['Academic', 'Exams & Results'],
            'finance-officer' => ['Finance', 'Academic'],
            'librarian' => ['Campus'],
            'boarding-master' => ['Campus'],
            'transport-coordinator' => ['Campus'],
            'feeding-coordinator' => ['Campus'],
        ];

        foreach ($roleMenuGroups as $roleName => $groupNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $menuIds = Menu::whereIn('name', $groupNames)
                ->whereNull('parent_id')
                ->with('children')
                ->get()
                ->flatMap(fn ($g) => [$g->id, ...$g->children->pluck('id')->all()])
                ->all();

            $role->menus()->sync($menuIds);
        }
    }

    /** @return array<int, array{name: string, icon: string, items: array<int, array{name: string, route: string}>}> */
    private function menuConfig(): array
    {
        return [
            [
                'name'  => 'Academic',
                'icon'  => 'book-open',
                'items' => [
                    ['name' => 'Dashboard',       'route' => 'dashboard'],
                    ['name' => 'Academic Years',  'route' => 'academic-years.index'],
                    ['name' => 'Terms & Calendar', 'route' => 'terms.index'],
                    ['name' => 'Pupils',     'route' => 'pupils.index'],
                    ['name' => 'Guardians',  'route' => 'guardians.index'],
                    ['name' => 'Classes',    'route' => 'grades.index'],
                    ['name' => 'Streams',    'route' => 'streams.index'],
                    ['name' => 'Subjects',   'route' => 'subjects.index'],
                    ['name' => 'Timetable',  'route' => 'timetable.index'],
                    ['name' => 'Attendance', 'route' => 'attendance.index'],
                ],
            ],
            [
                'name'  => 'Exams & Results',
                'icon'  => 'clipboard',
                'items' => [
                    ['name' => 'Assessments',    'route' => 'assessments.index'],
                    ['name' => 'Lesson Plans',   'route' => 'lesson-plans.index'],
                    ['name' => 'Term Results',   'route' => 'term-results.index'],
                    ['name' => 'Report Cards',   'route' => 'report-cards.index'],
                    ['name' => 'ECZ Candidates', 'route' => 'ecz-candidates.index'],
                ],
            ],
            [
                'name'  => 'Staff & HR',
                'icon'  => 'users',
                'items' => [
                    ['name' => 'Staff Directory', 'route' => 'staff.index'],
                    ['name' => 'Leave',            'route' => 'leaves.index'],
                    ['name' => 'Payroll',          'route' => 'payroll.index'],
                ],
            ],
            [
                'name'  => 'Finance',
                'icon'  => 'banknotes',
                'items' => [
                    ['name' => 'Dashboard',       'route' => 'finance.dashboard'],
                    ['name' => 'Fee Structures',  'route' => 'fee-structures.index'],
                    ['name' => 'Invoices',        'route' => 'fee-invoices.index'],
                    ['name' => 'Receivables',     'route' => 'finance.receivables'],
                    ['name' => 'Expenses',        'route' => 'expenses.index'],
                    ['name' => 'Other Income',    'route' => 'other-income.index'],
                    ['name' => 'Budget',          'route' => 'budgets.index'],
                    ['name' => 'Profit & Loss',   'route' => 'finance.profit-loss'],
                ],
            ],
            [
                'name'  => 'Campus',
                'icon'  => 'building-office',
                'items' => [
                    ['name' => 'Library',    'route' => 'library-books.index'],
                    ['name' => 'Borrowings', 'route' => 'borrowings.index'],
                    ['name' => 'Transport',  'route' => 'transport-routes.index'],
                    ['name' => 'Feeding',    'route' => 'feeding-sessions.index'],
                    ['name' => 'Boarding',   'route' => 'dormitories.index'],
                ],
            ],
            [
                'name'  => 'Communication',
                'icon'  => 'chat-bubble',
                'items' => [
                    ['name' => 'Notices',  'route' => 'notices.index'],
                    ['name' => 'SMS',      'route' => 'sms.compose'],
                    ['name' => 'Messages', 'route' => 'messages.index'],
                ],
            ],
            [
                'name'  => 'Admin',
                'icon'  => 'cog',
                'items' => [
                    ['name' => 'Branches',     'route' => 'schools.index'],
                    ['name' => 'Menus',        'route' => 'admin.menus.index'],
                    ['name' => 'Roles',        'route' => 'roles.index'],
                    ['name' => 'Settings',     'route' => 'settings.index'],
                    ['name' => 'Audit Log',    'route' => 'audit-logs.index'],
                ],
            ],
        ];
    }
}
