<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        foreach ($this->permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        foreach ($this->rolePermissions() as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissionNames);
        }

        // Super-admin gets everything
        Role::findByName('super-admin')->syncPermissions(Permission::all());
    }

    /** @return array<int, string> */
    private function permissions(): array
    {
        return [
            // School
            'school.view', 'school.create', 'school.update', 'school.delete',

            // Pupils
            'pupil.view', 'pupil.create', 'pupil.update', 'pupil.delete', 'pupil.transfer',

            // Guardians
            'guardian.view', 'guardian.create', 'guardian.update',

            // Grade & Stream
            'grade.view', 'grade.create', 'grade.update', 'grade.delete',
            'stream.view', 'stream.create', 'stream.update', 'stream.delete',

            // Subjects
            'subject.view', 'subject.create', 'subject.update', 'subject.delete',

            // Attendance
            'attendance.view', 'attendance.record', 'attendance.edit',

            // Assessments
            'assessment.view', 'assessment.create', 'assessment.update', 'assessment.delete',

            // Lesson plans
            'lesson-plan.view', 'lesson-plan.create', 'lesson-plan.update', 'lesson-plan.delete', 'lesson-plan.approve',

            // Grade entry
            'grade.enter', 'grade.publish',

            // Report cards
            'report-card.view', 'report-card.generate', 'report-card.publish',

            // ECZ
            'ecz.view', 'ecz.register', 'ecz.enter-results',

            // Finance
            'fee.view', 'fee.create', 'fee.collect', 'fee.waive',
            'payroll.view', 'payroll.generate', 'payroll.approve',
            'expense.view', 'expense.create', 'expense.approve',

            // Library
            'library.view', 'library.manage', 'library.borrow', 'library.return',

            // Transport
            'transport.view', 'transport.manage', 'transport.assign',

            // Feeding
            'feeding.view', 'feeding.record', 'feeding.manage-stock',

            // Boarding
            'boarding.view', 'boarding.allocate', 'boarding.manage',

            // Communication
            'notice.view', 'notice.create', 'notice.publish',
            'sms.send',

            // HR
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
            'leave.apply', 'leave.approve',

            // System
            'settings.manage',
            'audit.view',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissions(): array
    {
        return [
            'super-admin' => [], // synced separately to get ALL

            'school-admin' => [
                'school.view', 'school.update',
                'pupil.view', 'pupil.create', 'pupil.update', 'pupil.delete', 'pupil.transfer',
                'guardian.view', 'guardian.create', 'guardian.update',
                'grade.view', 'grade.create', 'grade.update', 'grade.delete',
                'grade.enter', 'grade.publish',
                'stream.view', 'stream.create', 'stream.update', 'stream.delete',
                'subject.view', 'subject.create', 'subject.update', 'subject.delete',
                'attendance.view', 'attendance.record', 'attendance.edit',
                'assessment.view', 'assessment.create', 'assessment.update', 'assessment.delete',
                'lesson-plan.view', 'lesson-plan.create', 'lesson-plan.update', 'lesson-plan.delete', 'lesson-plan.approve',
                'report-card.view', 'report-card.generate', 'report-card.publish',
                'ecz.view', 'ecz.register', 'ecz.enter-results',
                'fee.view', 'fee.create', 'fee.collect', 'fee.waive',
                'payroll.view', 'payroll.generate', 'payroll.approve',
                'expense.view', 'expense.create', 'expense.approve',
                'library.view', 'library.manage',
                'transport.view', 'transport.manage', 'transport.assign',
                'feeding.view', 'feeding.record', 'feeding.manage-stock',
                'boarding.view', 'boarding.allocate', 'boarding.manage',
                'notice.view', 'notice.create', 'notice.publish',
                'sms.send',
                'staff.view', 'staff.create', 'staff.update', 'staff.delete',
                'leave.apply', 'leave.approve',
                'settings.manage',
                'audit.view',
            ],

            'headteacher' => [
                'school.view',
                'pupil.view', 'pupil.create', 'pupil.update', 'pupil.delete', 'pupil.transfer',
                'guardian.view', 'guardian.create', 'guardian.update',
                'grade.view', 'grade.create', 'grade.update',
                'grade.enter', 'grade.publish',
                'stream.view', 'stream.create', 'stream.update',
                'subject.view', 'subject.create', 'subject.update', 'subject.delete',
                'attendance.view', 'attendance.record', 'attendance.edit',
                'assessment.view', 'assessment.create', 'assessment.update',
                'lesson-plan.view', 'lesson-plan.create', 'lesson-plan.update', 'lesson-plan.delete', 'lesson-plan.approve',
                'report-card.view', 'report-card.generate', 'report-card.publish',
                'ecz.view', 'ecz.register', 'ecz.enter-results',
                'fee.view',
                'payroll.view', 'payroll.generate', 'payroll.approve',
                'expense.view', 'expense.approve',
                'library.view',
                'transport.view',
                'feeding.view',
                'boarding.view',
                'notice.view', 'notice.create', 'notice.publish',
                'sms.send',
                'staff.view', 'staff.create', 'staff.update',
                'leave.apply', 'leave.approve',
                'audit.view',
            ],

            'deputy-headteacher' => [
                'school.view',
                'pupil.view', 'pupil.create', 'pupil.update', 'pupil.transfer',
                'guardian.view', 'guardian.create', 'guardian.update',
                'grade.view', 'grade.create', 'grade.update',
                'grade.enter', 'grade.publish',
                'stream.view', 'stream.create', 'stream.update',
                'subject.view', 'subject.create', 'subject.update',
                'attendance.view', 'attendance.record', 'attendance.edit',
                'assessment.view', 'assessment.create', 'assessment.update',
                'lesson-plan.view', 'lesson-plan.create', 'lesson-plan.update', 'lesson-plan.delete', 'lesson-plan.approve',
                'report-card.view', 'report-card.generate', 'report-card.publish',
                'ecz.view', 'ecz.register',
                'fee.view',
                'library.view',
                'transport.view',
                'feeding.view',
                'boarding.view',
                'notice.view', 'notice.create', 'notice.publish',
                'sms.send',
                'staff.view',
                'leave.apply', 'leave.approve',
            ],

            'class-teacher' => [
                'pupil.view',
                'guardian.view',
                'attendance.view', 'attendance.record', 'attendance.edit',
                'assessment.view', 'assessment.create', 'assessment.update',
                'lesson-plan.view', 'lesson-plan.create', 'lesson-plan.update', 'lesson-plan.delete',
                'grade.enter',
                'report-card.view', 'report-card.generate',
                'notice.view', 'notice.create',
                'leave.apply',
            ],

            'subject-teacher' => [
                'pupil.view',
                'attendance.view', 'attendance.record',
                'assessment.view', 'assessment.create', 'assessment.update',
                'lesson-plan.view', 'lesson-plan.create', 'lesson-plan.update', 'lesson-plan.delete',
                'grade.enter',
                'notice.view', 'notice.create',
                'leave.apply',
            ],

            'finance-officer' => [
                'pupil.view',
                'fee.view', 'fee.create', 'fee.collect', 'fee.waive',
                'payroll.view', 'payroll.generate',
                'expense.view', 'expense.create', 'expense.approve',
                'notice.view',
                'leave.apply',
            ],

            'librarian' => [
                'pupil.view',
                'library.view', 'library.manage', 'library.borrow', 'library.return',
                'notice.view',
                'leave.apply',
            ],

            'boarding-master' => [
                'pupil.view',
                'boarding.view', 'boarding.allocate', 'boarding.manage',
                'notice.view',
                'leave.apply',
            ],

            'transport-coordinator' => [
                'pupil.view',
                'transport.view', 'transport.manage', 'transport.assign',
                'notice.view',
                'leave.apply',
            ],

            'feeding-coordinator' => [
                'pupil.view',
                'feeding.view', 'feeding.record', 'feeding.manage-stock',
                'notice.view',
                'leave.apply',
            ],

            'parent' => [
                'pupil.view',
                'attendance.view',
                'report-card.view',
                'fee.view',
                'notice.view',
            ],
        ];
    }
}
