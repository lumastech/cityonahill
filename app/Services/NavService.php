<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavService
{
    public function forUser(User $user): array
    {
        $user->loadMissing(['roles', 'permissions']);

        return collect($this->config())
            ->map(function (array $group) use ($user) {
                $items = collect($group['items'])
                    ->filter(fn ($item) => $item['permission'] === null || $user->can($item['permission']))
                    ->map(fn ($item) => [
                        'label' => $item['label'],
                        'url'   => $this->resolveUrl($item['route']),
                    ])
                    ->values()
                    ->all();

                return ['label' => $group['label'], 'icon' => $group['icon'], 'items' => $items];
            })
            ->filter(fn ($group) => count($group['items']) > 0)
            ->values()
            ->all();
    }

    private function resolveUrl(?string $routeName): ?string
    {
        if (! $routeName || ! Route::has($routeName)) {
            return null;
        }

        return route($routeName);
    }

    private function config(): array
    {
        return [
            [
                'label' => 'Academic',
                'icon'  => 'book-open',
                'items' => [
                    ['label' => 'Dashboard',  'route' => 'dashboard',        'permission' => null],
                    ['label' => 'Pupils',     'route' => 'pupils.index',     'permission' => 'pupil.view'],
                    ['label' => 'Guardians',  'route' => 'guardians.index',  'permission' => 'pupil.view'],
                    ['label' => 'Classes',    'route' => 'grades.index',     'permission' => 'grade.view'],
                    ['label' => 'Streams',    'route' => 'streams.index',    'permission' => 'grade.view'],
                    ['label' => 'Subjects',   'route' => 'subjects.index',   'permission' => 'subject.view'],
                    ['label' => 'Timetable',  'route' => 'timetable.index',  'permission' => 'grade.view'],
                    ['label' => 'Attendance', 'route' => 'attendance.index', 'permission' => 'attendance.view'],
                ],
            ],
            [
                'label' => 'Exams & Results',
                'icon'  => 'clipboard',
                'items' => [
                    ['label' => 'Assessments',    'route' => 'assessments.index',    'permission' => 'assessment.view'],
                    ['label' => 'Term Results',   'route' => 'term-results.index',   'permission' => 'grade.view'],
                    ['label' => 'Report Cards',   'route' => 'report-cards.index',   'permission' => 'report-card.view'],
                    ['label' => 'ECZ Candidates', 'route' => 'ecz-candidates.index', 'permission' => 'ecz.view'],
                ],
            ],
            [
                'label' => 'Staff & HR',
                'icon'  => 'users',
                'items' => [
                    ['label' => 'Staff Directory', 'route' => 'staff.index',   'permission' => 'staff.view'],
                    ['label' => 'Leave',            'route' => 'leaves.index',  'permission' => 'leave.apply'],
                    ['label' => 'Payroll',          'route' => 'payroll.index', 'permission' => 'payroll.view'],
                ],
            ],
            [
                'label' => 'Finance',
                'icon'  => 'banknotes',
                'items' => [
                    ['label' => 'Fee Structures', 'route' => 'fee-structures.index', 'permission' => 'fee.view'],
                    ['label' => 'Invoices',        'route' => 'fee-invoices.index',   'permission' => 'fee.view'],
                    ['label' => 'Expenses',        'route' => 'expenses.index',       'permission' => 'expense.view'],
                    ['label' => 'Budget',          'route' => 'budgets.index',        'permission' => 'expense.view'],
                ],
            ],
            [
                'label' => 'Campus',
                'icon'  => 'building-office',
                'items' => [
                    ['label' => 'Library',    'route' => 'library-books.index',    'permission' => 'library.view'],
                    ['label' => 'Borrowings', 'route' => 'borrowings.index',       'permission' => 'library.view'],
                    ['label' => 'Transport',  'route' => 'transport-routes.index', 'permission' => 'transport.view'],
                    ['label' => 'Feeding',    'route' => 'feeding-sessions.index', 'permission' => 'feeding.view'],
                    ['label' => 'Boarding',   'route' => 'dormitories.index',      'permission' => 'boarding.view'],
                ],
            ],
            [
                'label' => 'Communication',
                'icon'  => 'chat-bubble',
                'items' => [
                    ['label' => 'Notices',  'route' => 'notices.index',  'permission' => 'notice.view'],
                    ['label' => 'SMS',      'route' => 'sms.compose',    'permission' => 'sms.send'],
                    ['label' => 'Messages', 'route' => 'messages.index', 'permission' => null],
                ],
            ],
            [
                'label' => 'Admin',
                'icon'  => 'cog',
                'items' => [
                    ['label' => 'Schools',      'route' => 'schools.index',           'permission' => 'school.view'],
                    ['label' => 'Applications', 'route' => 'admin.applications.index', 'permission' => 'settings.manage'],
                    ['label' => 'Menus',        'route' => 'admin.menus.index',       'permission' => 'settings.manage'],
                    ['label' => 'Roles',        'route' => 'roles.index',             'permission' => 'settings.manage'],
                    ['label' => 'Settings',     'route' => 'settings.index',          'permission' => 'settings.manage'],
                    ['label' => 'Audit Log',    'route' => 'audit-logs.index',        'permission' => 'settings.manage'],
                ],
            ],
        ];
    }
}
