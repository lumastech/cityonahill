<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeSubject;
use App\Models\Guardian;
use App\Models\Pupil;
use App\Models\PupilGuardian;
use App\Models\School;
use App\Models\Staff;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        // ── School ──────────────────────────────────────────────────────────
        $school = School::create([
            'name'     => 'Lusaka Day Secondary School',
            'code'     => 'LDSS',
            'type'     => 'government',
            'level'    => 'secondary',
            'province' => 'Lusaka',
            'district' => 'Lusaka',
            'address'  => 'Plot 1234, Great East Road, Lusaka',
            'phone'    => '+260 211 222333',
            'email'    => 'info@ldss.edu.zm',
            'status'   => 'active',
        ]);

        // ── Academic Year ────────────────────────────────────────────────────
        $year = AcademicYear::create([
            'school_id'  => $school->id,
            'name'       => '2026',
            'start_date' => '2026-01-05',
            'end_date'   => '2026-11-27',
            'is_current' => 1,
        ]);

        // ── Terms ────────────────────────────────────────────────────────────
        Term::create([
            'school_id'        => $school->id,
            'academic_year_id' => $year->id,
            'name'             => 'Term 1',
            'number'           => 1,
            'start_date'       => '2026-01-05',
            'end_date'         => '2026-04-10',
            'is_current'       => 0,
            'ca_deadline'      => '2026-03-27',
            'exam_start'       => '2026-04-01',
            'exam_end'         => '2026-04-10',
        ]);

        $term2 = Term::create([
            'school_id'        => $school->id,
            'academic_year_id' => $year->id,
            'name'             => 'Term 2',
            'number'           => 2,
            'start_date'       => '2026-05-04',
            'end_date'         => '2026-08-07',
            'is_current'       => 1,
            'ca_deadline'      => '2026-07-24',
            'exam_start'       => '2026-07-28',
            'exam_end'         => '2026-08-07',
        ]);

        Term::create([
            'school_id'        => $school->id,
            'academic_year_id' => $year->id,
            'name'             => 'Term 3',
            'number'           => 3,
            'start_date'       => '2026-09-07',
            'end_date'         => '2026-11-27',
            'is_current'       => 0,
        ]);

        // ── Grades ───────────────────────────────────────────────────────────
        $gradeDefs = [
            ['grade_number' => 8,  'name' => 'Grade 8',  'level' => 'junior_secondary',  'is_ecz_year' => 0, 'order_index' => 1],
            ['grade_number' => 9,  'name' => 'Grade 9',  'level' => 'junior_secondary',  'is_ecz_year' => 1, 'order_index' => 2],
            ['grade_number' => 10, 'name' => 'Grade 10', 'level' => 'senior_secondary',  'is_ecz_year' => 0, 'order_index' => 3],
            ['grade_number' => 11, 'name' => 'Grade 11', 'level' => 'senior_secondary',  'is_ecz_year' => 0, 'order_index' => 4],
            ['grade_number' => 12, 'name' => 'Grade 12', 'level' => 'senior_secondary',  'is_ecz_year' => 1, 'order_index' => 5],
        ];

        $grades = [];
        foreach ($gradeDefs as $def) {
            $grades[$def['grade_number']] = Grade::create(array_merge($def, ['school_id' => $school->id]));
        }

        // ── Streams (A & B per grade) ────────────────────────────────────────
        foreach ($grades as $num => $grade) {
            foreach (['A', 'B'] as $suffix) {
                Stream::create([
                    'school_id'        => $school->id,
                    'grade_id'         => $grade->id,
                    'name'             => $suffix,
                    'academic_year_id' => $year->id,
                    'capacity'         => 45,
                ]);
            }
        }

        // ── Subjects ─────────────────────────────────────────────────────────
        $subjectDefs = [
            ['code' => 'MATH', 'name' => 'Mathematics',         'category' => 'core',      'is_ecz_subject' => 1],
            ['code' => 'ENG',  'name' => 'English Language',    'category' => 'core',      'is_ecz_subject' => 1],
            ['code' => 'ISCI', 'name' => 'Integrated Science',  'category' => 'core',      'is_ecz_subject' => 0],
            ['code' => 'BIO',  'name' => 'Biology',             'category' => 'core',      'is_ecz_subject' => 1],
            ['code' => 'CHEM', 'name' => 'Chemistry',           'category' => 'core',      'is_ecz_subject' => 1],
            ['code' => 'PHY',  'name' => 'Physics',             'category' => 'elective',  'is_ecz_subject' => 1],
            ['code' => 'HIST', 'name' => 'History',             'category' => 'core',      'is_ecz_subject' => 1],
            ['code' => 'GEO',  'name' => 'Geography',           'category' => 'core',      'is_ecz_subject' => 1],
            ['code' => 'CIV',  'name' => 'Civic Education',     'category' => 'core',      'is_ecz_subject' => 1],
            ['code' => 'RE',   'name' => 'Religious Education', 'category' => 'religious', 'is_ecz_subject' => 1],
            ['code' => 'PE',   'name' => 'Physical Education',  'category' => 'physical',  'is_ecz_subject' => 0],
            ['code' => 'BEM',  'name' => 'Bemba Language',      'category' => 'language',  'is_ecz_subject' => 1, 'is_zambian_language' => 1],
        ];

        $subjects = [];
        foreach ($subjectDefs as $def) {
            $subjects[$def['code']] = Subject::create(array_merge([
                'school_id'          => $school->id,
                'is_zambian_language' => 0,
            ], $def));
        }

        // ── Grade–Subject links ───────────────────────────────────────────────
        // Junior secondary (8–9): Integrated Science instead of separate sciences
        $juniorSubjects = ['MATH', 'ENG', 'ISCI', 'HIST', 'GEO', 'CIV', 'RE', 'PE', 'BEM'];
        // Senior secondary (10–12): Biology, Chemistry, Physics; no Integrated Science
        $seniorSubjects = ['MATH', 'ENG', 'BIO', 'CHEM', 'PHY', 'HIST', 'GEO', 'CIV', 'RE', 'PE', 'BEM'];

        foreach ($grades as $num => $grade) {
            $codes = $num <= 9 ? $juniorSubjects : $seniorSubjects;
            foreach ($codes as $code) {
                GradeSubject::create([
                    'school_id'  => $school->id,
                    'grade_id'   => $grade->id,
                    'subject_id' => $subjects[$code]->id,
                    'is_core'    => in_array($code, ['MATH', 'ENG', 'CIV', 'RE']) ? 1 : 0,
                    'ca_weight'  => 40,
                    'exam_weight' => 60,
                ]);
            }
        }

        // ── Staff users ───────────────────────────────────────────────────────
        $staffUsers = $this->seedStaffUsers($school);

        // Update school with headteacher
        $school->update(['headteacher_id' => $staffUsers['headteacher']->id]);

        // Link streams to class teachers
        $streams = Stream::where('school_id', $school->id)->get();
        $classTeachers = collect($staffUsers['class_teachers']);
        $streams->each(function (Stream $stream, int $i) use ($classTeachers) {
            $teacher = $classTeachers->get($i % $classTeachers->count());
            if ($teacher) {
                $stream->update(['class_teacher_id' => $teacher->id]);
            }
        });

        // ── Pupils ────────────────────────────────────────────────────────────
        $this->seedPupils($school, $grades, $year);
    }

    /** @return array{headteacher: User, class_teachers: User[]} */
    private function seedStaffUsers(School $school): array
    {
        $schoolAdminRole = Role::findByName('school-admin');
        $headteacherRole = Role::findByName('headteacher');
        $classTeacherRole = Role::findByName('class-teacher');
        $financeRole      = Role::findByName('finance-officer');

        // School admin user
        $adminUser = User::where('email', 'admin@zssms.test')->first();
        if ($adminUser) {
            $adminUser->update(['school_id' => $school->id]);
        }

        // Headteacher
        $headteacher = User::create([
            'name'              => 'Margaret Phiri',
            'email'             => 'mphiri@ldss.edu.zm',
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'sex'               => 'female',
            'phone'             => '+260 977 100001',
            'school_id'         => $school->id,
            'status'            => 'active',
        ]);
        $headteacher->assignRole($headteacherRole);
        Staff::create([
            'user_id'          => $headteacher->id,
            'school_id'        => $school->id,
            'employee_no'      => 'LDSS-001',
            'position'         => 'headteacher',
            'employment_type'  => 'permanent',
            'employment_date'  => '2018-01-08',
            'basic_salary'     => 12000.00,
        ]);

        // Deputy headteacher
        $deputy = User::create([
            'name'              => 'Emmanuel Banda',
            'email'             => 'ebanda@ldss.edu.zm',
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'sex'               => 'male',
            'phone'             => '+260 977 100002',
            'school_id'         => $school->id,
            'status'            => 'active',
        ]);
        $deputy->assignRole(Role::findByName('deputy-headteacher'));
        Staff::create([
            'user_id'         => $deputy->id,
            'school_id'       => $school->id,
            'employee_no'     => 'LDSS-002',
            'position'        => 'deputy_headteacher',
            'employment_type' => 'permanent',
            'employment_date' => '2020-01-06',
            'basic_salary'    => 10000.00,
        ]);

        // Finance officer
        $finance = User::create([
            'name'              => 'Grace Mwale',
            'email'             => 'gmwale@ldss.edu.zm',
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'sex'               => 'female',
            'phone'             => '+260 977 100003',
            'school_id'         => $school->id,
            'status'            => 'active',
        ]);
        $finance->assignRole($financeRole);
        Staff::create([
            'user_id'         => $finance->id,
            'school_id'       => $school->id,
            'employee_no'     => 'LDSS-003',
            'position'        => 'bursar',
            'employment_type' => 'permanent',
            'employment_date' => '2021-01-04',
            'basic_salary'    => 8500.00,
        ]);

        // Class teachers (one per grade level)
        $classTeacherData = [
            ['name' => 'John Tembo',    'email' => 'jtembo@ldss.edu.zm',   'phone' => '+260 977 100004', 'sex' => 'male',   'no' => 'LDSS-004'],
            ['name' => 'Alice Mulenga', 'email' => 'amulenga@ldss.edu.zm', 'phone' => '+260 977 100005', 'sex' => 'female', 'no' => 'LDSS-005'],
            ['name' => 'Peter Nkosi',   'email' => 'pnkosi@ldss.edu.zm',   'phone' => '+260 977 100006', 'sex' => 'male',   'no' => 'LDSS-006'],
            ['name' => 'Mary Zulu',     'email' => 'mzulu@ldss.edu.zm',    'phone' => '+260 977 100007', 'sex' => 'female', 'no' => 'LDSS-007'],
            ['name' => 'David Lungu',   'email' => 'dlungu@ldss.edu.zm',   'phone' => '+260 977 100008', 'sex' => 'male',   'no' => 'LDSS-008'],
        ];

        $classTeachers = [];
        foreach ($classTeacherData as $data) {
            $teacher = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'sex'               => $data['sex'],
                'phone'             => $data['phone'],
                'school_id'         => $school->id,
                'status'            => 'active',
            ]);
            $teacher->assignRole($classTeacherRole);
            Staff::create([
                'user_id'         => $teacher->id,
                'school_id'       => $school->id,
                'employee_no'     => $data['no'],
                'position'        => 'class_teacher',
                'employment_type' => 'permanent',
                'employment_date' => '2023-01-09',
                'basic_salary'    => 7000.00,
            ]);
            $classTeachers[] = $teacher;
        }

        return ['headteacher' => $headteacher, 'class_teachers' => $classTeachers];
    }

    private function seedPupils(School $school, array $grades, AcademicYear $year): void
    {
        $firstNames = [
            'male'   => ['Chanda', 'Mwape', 'Bwalya', 'Mutale', 'Kasonde', 'Musonda', 'Chilufya', 'Kapembwa', 'Lombe', 'Kafula'],
            'female' => ['Natasha', 'Precious', 'Bridget', 'Charity', 'Memory', 'Lilian', 'Dalitso', 'Namukolo', 'Chisomo', 'Ngosa'],
        ];
        $lastNames = ['Banda', 'Phiri', 'Mulenga', 'Tembo', 'Zulu', 'Nkosi', 'Mwale', 'Lungu', 'Chanda', 'Mutale'];

        $streams = Stream::where('school_id', $school->id)->with('grade')->get();

        $counter = 1;

        foreach ($streams as $stream) {
            $grade = $grades[$stream->grade->grade_number] ?? null;
            if (! $grade) {
                continue;
            }

            // 6 pupils per stream = 60 total across 10 streams
            for ($i = 1; $i <= 6; $i++) {
                $sex = $i % 2 === 0 ? 'female' : 'male';
                $firstName = $firstNames[$sex][($i - 1) % count($firstNames[$sex])];
                $lastName  = $lastNames[($counter - 1) % count($lastNames)];

                $admissionNo = sprintf('LDSS/%04d/%d', $counter, $year->name);

                $pupil = Pupil::create([
                    'school_id'        => $school->id,
                    'admission_no'     => $admissionNo,
                    'first_name'       => $firstName,
                    'last_name'        => $lastName,
                    'sex'              => $sex,
                    'dob'              => now()->subYears(12 + ($stream->grade->grade_number - 8))->subDays(rand(0, 365))->toDateString(),
                    'nationality'      => 'Zambian',
                    'date_of_admission' => '2026-01-05',
                    'grade_id'         => $grade->id,
                    'stream_id'        => $stream->id,
                    'academic_year_id' => $year->id,
                    'status'           => 'active',
                ]);

                // Guardian for each pupil
                $guardianSex    = $sex === 'male' ? 'father' : 'mother';
                $guardianFirst  = $sex === 'male' ? 'Samuel' : 'Agnes';
                $guardianPhone  = sprintf('+260 97%d %06d', rand(5, 9), $counter * 7 + 100000);

                $guardian = Guardian::create([
                    'school_id'    => $school->id,
                    'first_name'   => $guardianFirst,
                    'last_name'    => $lastName,
                    'relationship' => $guardianSex,
                    'phone'        => $guardianPhone,
                ]);

                PupilGuardian::create([
                    'pupil_id'    => $pupil->id,
                    'guardian_id' => $guardian->id,
                    'is_primary'  => 1,
                    'is_emergency' => 1,
                    'can_pickup'  => 1,
                ]);

                $counter++;
            }
        }
    }
}
