# Zambian School Management System (ZSSMS) — Coding Agent Prompts
## Laravel 13.8 · Jetstream · Inertia.js · Vue 3 · TypeScript · Tailwind CSS
## Target: Primary & Secondary Schools in Zambia (Grades 1–12)

---

## Stack conventions (identical to SMS Project 1)

| Concern | Choice |
|---|---|
| Auth scaffold | Laravel Jetstream (Inertia + Vue + TypeScript) |
| Frontend bridge | Inertia.js v2 — pure server-renders-props |
| CSS | Tailwind CSS only |
| RBAC | Spatie Laravel Permission |
| Validation + DTOs | Spatie Laravel Data |
| File uploads | Spatie Media Library |
| Database | MySQL 8+ |
| Tests | Pest — feature tests only |
| Controllers | Mixed: resource for CRUD, invokable for special ops |
| Client state | Vue 3 composables only |
| Vue SFCs | `<script setup lang="ts">` always |

---

## System design decisions

### Multi-tenancy strategy
Single database, `school_id` on every domain table.
A `SetSchoolContext` middleware injects the current school into every request.
Super-admin can switch between schools. Staff see only their school. Parents see only their child's school.

### Zambian academic context
- **Calendar**: 3 terms per year (Term 1 ≈ Jan–Apr, Term 2 ≈ May–Aug, Term 3 ≈ Sep–Nov)
- **Levels**: Basic school = Grades 1–9; Secondary = Grades 10–12
- **ECZ exams**: Grade 7 (Primary Leaving), Grade 9 (Junior Certificate), Grade 12 (School Certificate)
- **Grading scale** (internal):
  - 75–100 → A (Distinction)
  - 65–74  → B (Merit)
  - 50–64  → C (Credit)
  - 40–49  → D (Pass)
  - 0–39   → F (Fail)
- **ECZ points**: 1 (best) → 9 (worst) per subject. Division based on aggregate of best subjects.

### Roles
`super-admin`, `school-admin`, `headteacher`, `deputy-headteacher`,
`class-teacher`, `subject-teacher`, `finance-officer`, `librarian`,
`boarding-master`, `transport-coordinator`, `feeding-coordinator`, `parent`

---

## Module map (15 modules + foundation)

| # | Module | Key tables |
|---|--------|-----------|
| 0 | Foundation | schools, school_settings, users, audit_logs, settings |
| 1 | School & Class Structure | grades, streams, subjects, grade_subjects, timetable_slots |
| 2 | Academic Calendar | academic_years, terms, school_holidays |
| 3 | Pupil Management & Admissions | pupils, guardians, pupil_guardians |
| 4 | Attendance | attendance_sessions, attendance_records |
| 5 | Assessments & Results | assessments, assessment_scores, term_results, annual_results, report_cards |
| 6 | ECZ Exam Management | ecz_candidates, ecz_subject_entries, ecz_results |
| 7 | Parent/Guardian Portal | portal_access, parent_messages, portal_notifications |
| 8 | Staff & HR | staff, positions, leaves, leave_types, payroll |
| 9 | School Finance | fee_structures, fee_invoices, fee_payments, expenses, budgets |
| 10 | Library | library_books, book_borrowings |
| 11 | Transport | transport_routes, pupil_transport |
| 12 | School Feeding | feeding_sessions, feeding_records, feeding_stock |
| 13 | Boarding / Hostel | dormitories, beds, boarding_allocations |
| 14 | Communication | notices, sms_logs, parent_messages |

---

# FOUNDATION — Prompt 0

## Prompt 0.1 — Project setup & packages

```
You are a senior Laravel/Vue developer. Scaffold the Zambian School Management System (ZSSMS).
This is a separate project from any previous work — start fresh.

STACK: Laravel 13.8, Jetstream (Inertia + Vue + TypeScript), Tailwind CSS,
Spatie Laravel Permission, Spatie Laravel Data, Spatie Media Library, MySQL 8+

TASK 1 — Install Jetstream
  composer create-project laravel/laravel zssms
  cd zssms
  composer require laravel/jetstream
  php artisan jetstream:install inertia --typescript
  npm install && npm run build

TASK 2 — Spatie packages
  composer require spatie/laravel-permission
  php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

  composer require spatie/laravel-data

  composer require spatie/laravel-medialibrary
  php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" \
    --tag="medialibrary-migrations"

TASK 3 — User model
  Add HasRoles (Spatie Permission) to User.
  Add HasMedia + InteractsWithMedia (Spatie Media Library) to User.
  registerMediaCollections(): profile-photo (singleFile), documents.

  Disable Jetstream's built-in profile photo feature in config/jetstream.php
  (remove Features::profilePhotos() — replaced by Media Library).
  Disable Features::teams() — this is not a teams-based system.
  Keep: Features::api(), Features::securePasswords(), Features::emailVerification().

TASK 4 — vite.config.ts
  Aliases: '@' → resources/js, ziggy-js resolved.

TASK 5 — .env.example
  DB_CONNECTION=mysql, DB_PORT=3306, MEDIA_DISK=public
  SCHOOL_ID=  (comment: set for single-school deployments)
  Add commented blocks: AIRTEL_MONEY_API_URL, AIRTEL_MONEY_CLIENT_ID, AIRTEL_MONEY_CLIENT_SECRET
                        MTN_MOMO_API_URL, MTN_MOMO_SUBSCRIPTION_KEY, MTN_MOMO_API_USER

TASK 6 — config/zssms.php (new config file)
  Create config/zssms.php returning:
  [
    'multi_school'   => env('MULTI_SCHOOL', true),  // false = single-school mode
    'default_school' => env('SCHOOL_ID', null),
    'zambia_provinces' => ['Central','Copperbelt','Eastern','Luapula','Lusaka',
                           'Muchinga','Northern','North-Western','Southern','Western'],
    'grade_levels' => [
        'primary'        => range(1, 7),
        'junior_secondary'  => [8, 9],
        'senior_secondary'  => [10, 11, 12],
    ],
    'ecz_grades' => [7, 9, 12],
    'terms_per_year' => 3,
    'grading_scale' => [
        ['min' => 75, 'max' => 100, 'letter' => 'A', 'label' => 'Distinction'],
        ['min' => 65, 'max' => 74,  'letter' => 'B', 'label' => 'Merit'],
        ['min' => 50, 'max' => 64,  'letter' => 'C', 'label' => 'Credit'],
        ['min' => 40, 'max' => 49,  'letter' => 'D', 'label' => 'Pass'],
        ['min' => 0,  'max' => 39,  'letter' => 'F', 'label' => 'Fail'],
    ],
  ]

TASK 7 — Initial commit
  git init && git add . && git commit -m "chore: ZSSMS scaffold with Jetstream + Spatie stack"

DELIVERABLES CHECKLIST
- [ ] Jetstream installed (Inertia + Vue + TS)
- [ ] Spatie Permission, Data, Media Library installed
- [ ] HasRoles + HasMedia on User model
- [ ] config/zssms.php with Zambian constants
- [ ] .env.example with Mobile Money placeholders
- [ ] php artisan serve returns 200
```

---

## Prompt 0.2 — Foundation migrations

```
Packages installed (Prompt 0.1 complete).

TASK 1 — Run Jetstream + Spatie base migrations
  php artisan migrate

TASK 2 — schools table
  id, name varchar(255), code varchar(20) unique,
  type enum('government','private','mission','grant-aided'),
  level enum('primary','secondary','basic','combined') comment 'combined = grades 1-12',
  province varchar(50), district varchar(50), address text,
  phone varchar(25), email varchar(128) nullable, website varchar(255) nullable,
  moe_registration_no varchar(50) nullable unique,
  headteacher_id unsignedBigInt nullable FK → users.id,
  established_year year nullable,
  status enum('active','inactive','suspended') default 'active',
  timestamps

TASK 3 — school_settings table
  id, school_id FK → schools.id, key varchar(64), value text, timestamps
  UNIQUE(school_id, key)

TASK 4 — Extend users table
  Add migration add_profile_fields_to_users_table:
  other_name varchar(35) nullable, sex enum('male','female','other') default 'other',
  phone varchar(25) nullable, nrc varchar(25) unique nullable,
  dob date nullable, nationality varchar(64) default 'Zambian',
  address text nullable, status enum('active','inactive','suspended') default 'active',
  school_id unsignedBigInt nullable FK → schools.id,
  is_parent tinyint default 0

TASK 5 — settings table (global)
  id varchar(32) PK, name varchar(255), value text, timestamps

TASK 6 — audit_logs table
  id bigint PK, school_id nullable FK → schools.id, user_id nullable FK → users.id,
  action varchar(128), auditable_type varchar(255), auditable_id unsignedBigInt,
  old_values json nullable, new_values json nullable,
  ip_address varchar(45) nullable, timestamps

TASK 7 — Add level + group to roles (Spatie)
  Create migration add_level_group_school_to_roles:
  level int default 1, group int default 1,
  school_id unsignedBigInt nullable FK → schools.id
  (nullable — super-admin roles are school-agnostic)

TASK 8 — Run all migrations
  php artisan migrate — confirm zero errors.

DELIVERABLES CHECKLIST
- [ ] schools, school_settings, extended users, settings, audit_logs tables
- [ ] level + group + school_id on roles
- [ ] php artisan migrate clean
```

---

## Prompt 0.3 — Base models, school context middleware & seeder

```
Foundation migrations applied.

TASK 1 — School model (app/Models/School.php)
  $fillable all columns.
  HasMedia: registerMediaCollections() → 'logo' (singleFile), 'documents'
  HasAudit trait (create this trait — same pattern as SMS project).
  Relationships (stubs): hasMany(Grade), hasMany(Stream), hasMany(Pupil),
    hasMany(Staff), hasMany(AcademicYear), hasOne(setting via school_settings),
    belongsTo(User, 'headteacher_id')
  scopeActive($q): where status = 'active'

TASK 2 — SchoolSetting model
  $primaryKey compound — no auto-increment id needed beyond default.
  belongsTo(School)
  Static helper: SchoolSetting::get(int $schoolId, string $key, mixed $default = null): mixed

TASK 3 — Update User model
  Add $fillable for new profile columns. $casts for enums.
  belongsTo(School, 'school_id')
  hasOne(Staff), hasOne(Guardian via guardian user FK — add in Module 3)

TASK 4 — Setting model (global)
  $primaryKey = 'id'; $keyType = 'string'; $incrementing = false;
  Static helper: Setting::get(string $key, mixed $default = null): mixed

TASK 5 — AuditLog model
  belongsTo(School), belongsTo(User)

TASK 6 — HasAudit trait (app/Models/Concerns/HasAudit.php)
  Fires on created/updated/deleted model events.
  Writes AuditLog with school_id, user_id (Auth::id()), action, auditable_type/id,
  old_values, new_values, ip from request().

TASK 7 — SetSchoolContext middleware (app/Http/Middleware/SetSchoolContext.php)
  Logic:
    1. If MULTI_SCHOOL=false: use config('zssms.default_school') — inject as app singleton.
    2. If user is super-admin: allow school switching via ?school_id= query param or session.
    3. If user has staff record: use staff->school_id.
    4. If user is_parent: use first child's school_id.
    5. Store resolved school in app()->instance('current_school', $school).
  Register in bootstrap/app.php as 'school.context' alias.
  Apply globally after 'auth' middleware.

TASK 8 — BaseRepository + BaseService abstract classes
  Identical pattern to SMS Project 1 — create if not already present.

TASK 9 — HandleInertiaRequests middleware (extend Jetstream default)
  Share:
    'auth.user'    => fn() => $request->user()?->load(['roles','permissions','school']),
    'flash'        => fn() => ['success','error','info'] from session,
    'current_school' => fn() => app('current_school'),
    'terms'        => fn() => current school's active terms,
    'settings'     => fn() => SchoolSetting + global Setting merged,

TASK 10 — Roles & Permissions seeder
  Create database/seeders/RolesAndPermissionsSeeder.php

  Roles:
    super-admin, school-admin, headteacher, deputy-headteacher,
    class-teacher, subject-teacher, finance-officer,
    librarian, boarding-master, transport-coordinator,
    feeding-coordinator, parent

  Permissions (dot-notation):
    school.view|create|update|delete
    pupil.view|create|update|delete|transfer
    guardian.view|create|update
    grade.view|create|update|delete
    stream.view|create|update|delete
    subject.view|create|update|delete
    attendance.view|record|edit
    assessment.view|create|update|delete
    grade.enter|publish
    report-card.view|generate|publish
    ecz.view|register|enter-results
    fee.view|create|collect|waive
    payroll.view|generate|approve
    expense.view|create|approve
    library.view|manage|borrow|return
    transport.view|manage|assign
    feeding.view|record|manage-stock
    boarding.view|allocate|manage
    notice.view|create|publish
    sms.send
    staff.view|create|update|delete
    leave.apply|approve
    settings.manage
    audit.view

  Assign ALL to super-admin. Assign logical subsets per role.

DELIVERABLES CHECKLIST
- [ ] School, SchoolSetting, User, Setting, AuditLog models
- [ ] HasAudit trait
- [ ] SetSchoolContext middleware
- [ ] BaseRepository + BaseService
- [ ] HandleInertiaRequests sharing school context
- [ ] Seeder with 12 roles + full permission set
```

---

## Prompt 0.4 — Shared Vue layout, composables & TypeScript types

```
Backend foundation complete.

TASK 1 — TypeScript types (resources/js/types/shared.ts)

  export interface School {
    id: number
    name: string
    code: string
    type: 'government' | 'private' | 'mission' | 'grant-aided'
    level: 'primary' | 'secondary' | 'basic' | 'combined'
    province: string
    district: string
    phone: string
    moe_registration_no: string | null
    logo_url: string | null
    status: 'active' | 'inactive' | 'suspended'
  }

  export interface AuthUser {
    id: number
    first_name: string
    last_name: string
    email: string
    phone: string | null
    roles: string[]
    permissions: string[]
    school: School | null
    is_parent: boolean
    status: 'active' | 'inactive' | 'suspended'
  }

  export interface Term {
    id: number
    name: string
    academic_year_id: number
    start_date: string
    end_date: string
    is_current: boolean
  }

  export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
  }

  export interface FlashMessages {
    success: string | null
    error: string | null
    info: string | null
  }

  export interface SharedProps {
    auth: { user: AuthUser }
    flash: FlashMessages
    current_school: School | null
    terms: Term[]
    settings: Record<string, string>
  }

  export type ZambianProvince =
    'Central' | 'Copperbelt' | 'Eastern' | 'Luapula' | 'Lusaka' |
    'Muchinga' | 'Northern' | 'North-Western' | 'Southern' | 'Western'

  export type GradeLetter = 'A' | 'B' | 'C' | 'D' | 'F'

TASK 2 — Inertia PageProps augmentation
  resources/js/types/inertia.d.ts:
    declare module '@inertiajs/core' {
      interface PageProps extends SharedProps {}
    }

TASK 3 — Composables
  useFlash.ts — reads page.props.flash, auto-clear 4s
  useConfirm.ts — Promise<boolean> backed by modal
  usePagination.ts — wraps router.get with page/search/filter params
  usePermissions.ts — can(permission), hasRole(role)
  useSchool.ts — returns current_school from page props; schoolName computed

TASK 4 — Zambian grading composable
  useGrading.ts:
    getGradeLetter(marks: number): GradeLetter
    getGradeLabel(marks: number): string  // 'Distinction', 'Merit', etc.
    getEczPoints(letter: GradeLetter): number  // A=1, B=2, C=3, D=4, F=9
    getDivision(totalPoints: number, subjectCount: number): string
      // ECZ Grade 9/12 division calculation

TASK 5 — AppLayout.vue
  Full sidebar layout. Nav groups (Tailwind collapsible <details>):
    Academic: Dashboard, Pupils, Classes, Subjects, Timetable
    Exams: Assessments, Results, Report Cards, ECZ
    Staff: Staff Directory, Leave, Payroll
    Finance: Fees, Payments, Expenses, Budget
    Campus: Library, Transport, Feeding, Boarding
    Communication: Notices, SMS, Messages
    Admin: Schools, Roles, Settings, Audit Log
  Conditionally renders nav items via usePermissions().can().
  School badge in header showing current_school.name.
  For super-admin: school switcher dropdown in header.

TASK 6 — ParentLayout.vue
  Separate simplified layout for parents:
    My Children (tab per child), Fees, Results, Attendance, Notices, Messages
  No sidebar — top navigation tabs only.
  Branded with school logo from current_school.logo_url.

TASK 7 — Shared components
  FlashToast.vue, ConfirmDialog.vue, Pagination.vue (reuse SMS pattern)
  GradeBadge.vue — props: { letter: GradeLetter } → Tailwind colour-coded span
    A=green, B=teal, C=blue, D=yellow, F=red
  TermSelector.vue — props: { modelValue: number } → dropdown from shared terms

DELIVERABLES CHECKLIST
- [ ] shared.ts with 7 interfaces + Zambian types
- [ ] Inertia PageProps augmentation
- [ ] 6 composables incl. useGrading
- [ ] AppLayout.vue (admin/staff)
- [ ] ParentLayout.vue (guardian portal)
- [ ] 5 shared components incl. GradeBadge + TermSelector
```

---

# MODULE 1 — School & Class Structure

## Prompt 1.1 — Migrations

```
Foundation complete. Create school and class structure tables.

TASK 1 — grades table
  id, school_id FK → schools.id,
  name varchar(50)  e.g. "Grade 1", "Grade 8",
  grade_number tinyint  (1–12),
  level enum('primary','junior_secondary','senior_secondary'),
  is_ecz_year tinyint default 0  (1 for grades 7, 9, 12),
  order_index tinyint,
  timestamps
  UNIQUE(school_id, grade_number)

TASK 2 — streams table (classes within a grade, e.g. 8A, 8B)
  id, school_id FK → schools.id, grade_id FK → grades.id,
  name varchar(20)  e.g. "8A" or "Grade 3 Blue",
  class_teacher_id nullable FK → users.id,
  academic_year_id FK → academic_years.id
    (add nullable FK — academic_years created in Module 2; use nullable + no constraint for now),
  capacity int default 45, timestamps
  UNIQUE(school_id, grade_id, name, academic_year_id)

TASK 3 — subjects table
  id, school_id FK → schools.id,
  name varchar(100), code varchar(20),
  category enum('core','elective','language','vocational','religious','physical'),
  is_zambian_language tinyint default 0,
  is_ecz_subject tinyint default 0,
  description text nullable, timestamps
  UNIQUE(school_id, code)

TASK 4 — grade_subjects table (which subjects taught in which grade)
  id, school_id FK → schools.id, grade_id FK → grades.id,
  subject_id FK → subjects.id, is_core tinyint default 1,
  ca_weight int default 40  (percentage of CA in final mark),
  exam_weight int default 60, timestamps
  UNIQUE(school_id, grade_id, subject_id)

TASK 5 — timetable_slots table
  id, school_id FK → schools.id, stream_id FK → streams.id,
  subject_id FK → subjects.id, teacher_id FK → users.id,
  day_of_week tinyint  (1=Mon … 5=Fri),
  period_number tinyint, start_time time, end_time time,
  room varchar(50) nullable, term_id nullable, timestamps

Run: php artisan migrate

DELIVERABLES CHECKLIST
- [ ] 5 migrations, php artisan migrate clean
```

---

## Prompt 1.2 — Models + Data DTOs

```
TASK 1 — Grade model
  $fillable all. belongsTo(School), hasMany(Stream), hasMany(GradeSubject), hasMany(Pupil)
  scopeForSchool($q, int $schoolId)
  scopeEczYear($q): where is_ecz_year = 1
  getLevelLabelAttribute(): 'Primary' | 'Junior Secondary' | 'Senior Secondary'

TASK 2 — Stream model
  $fillable all. belongsTo(School), belongsTo(Grade), belongsTo(User, 'class_teacher_id')
  hasMany(Pupil), hasMany(AttendanceSession), hasMany(Assessment), hasMany(TimetableSlot)
  scopeForGrade($q, int $gradeId)

TASK 3 — Subject model
  $fillable all. belongsTo(School), hasMany(GradeSubject), hasMany(Assessment),
  hasMany(TermResult), hasMany(EczSubjectEntry)
  scopeEcz($q): where is_ecz_subject = 1
  scopeZambianLanguage($q): where is_zambian_language = 1

TASK 4 — GradeSubject model
  $fillable all. belongsTo(Grade), belongsTo(Subject), belongsTo(School)
  scopeCore($q): where is_core = 1

TASK 5 — TimetableSlot model
  $fillable all. belongsTo(School), belongsTo(Stream), belongsTo(Subject), belongsTo(User,'teacher_id')
  scopeForStream($q, int $streamId), scopeForDay($q, int $day)
  detectConflict(int $streamId, int $day, int $period, ?int $excludeId = null): bool
    → checks duplicate slot for same stream + day + period

TASK 6 — Data DTOs
  StoreGradeData: name required, grade_number int 1–12,
    level required in:primary,junior_secondary,senior_secondary,
    is_ecz_year bool, order_index int

  StoreStreamData: grade_id exists:grades, name required max:20,
    class_teacher_id nullable exists:users, capacity int min:1 max:100,
    academic_year_id nullable exists:academic_years

  StoreSubjectData: name required, code required, category required in:core,elective,language,vocational,religious,physical,
    is_zambian_language bool default false, is_ecz_subject bool default false

  LinkSubjectToGradeData: subject_id exists:subjects, grade_id exists:grades,
    is_core bool, ca_weight int min:0 max:100, exam_weight int min:0 max:100
    (validate ca_weight + exam_weight = 100 in rules())

  StoreTimetableSlotData: stream_id, subject_id, teacher_id, day_of_week int 1–5,
    period_number int, start_time, end_time, room nullable

DELIVERABLES CHECKLIST
- [ ] 5 models with relationships and scopes
- [ ] 5 Data DTOs (LinkSubjectToGrade validates weights sum to 100)
```

---

## Prompt 1.3 — Services + Controllers + Routes

```
TASK 1 — ClassStructureService
  createGrade(int $schoolId, StoreGradeData $data): Grade
  createStream(int $schoolId, StoreStreamData $data): Stream
  createSubject(int $schoolId, StoreSubjectData $data): Subject
  linkSubjectToGrade(int $schoolId, LinkSubjectToGradeData $data): GradeSubject
  unlinkSubjectFromGrade(int $gradeSubjectId): void
  assignClassTeacher(int $streamId, int $teacherId): Stream
  createTimetableSlot(int $schoolId, StoreTimetableSlotData $data): TimetableSlot
    → call TimetableSlot::detectConflict; throw ConflictException if conflict found
  getStreamTimetable(int $streamId): Collection  // grouped by day
  getTeacherTimetable(int $teacherId): Collection

TASK 2 — GradeController (resource, gate: school-admin|headteacher)
  index()  → all grades for current school, ordered by order_index
  store(StoreGradeData) → ClassStructureService::createGrade
  edit, update, destroy — standard

TASK 3 — StreamController (resource)
  index()  → streams filtered by grade_id (query param), with class teacher + pupil count
  store(StoreStreamData) → ClassStructureService::createStream
  show(Stream) → stream detail with timetable + pupil list stub

TASK 4 — SubjectController (resource)
  index()  → all subjects; filter by category, is_ecz_subject
  store(StoreSubjectData)
  Additional invokable: LinkSubjectController (POST grades/{grade}/subjects)

TASK 5 — TimetableController (resource)
  index()  → stream or teacher timetable (query param: stream_id or teacher_id)
  store(StoreTimetableSlotData) → conflict check → create
  destroy → delete slot

TASK 6 — Routes
  Route::middleware(['auth','verified','school.context'])->group(function () {
      Route::resource('grades', GradeController::class)->except(['show','create']);
      Route::resource('streams', StreamController::class)->except(['create']);
      Route::resource('subjects', SubjectController::class)->except(['show','create']);
      Route::post('grades/{grade}/subjects', LinkSubjectController::class)
           ->name('grades.subjects.link');
      Route::resource('timetable', TimetableController::class)
           ->only(['index','store','destroy']);
  });

DELIVERABLES CHECKLIST
- [ ] ClassStructureService (8 methods)
- [ ] 4 controllers + 1 invokable
- [ ] Routes with school.context middleware
```

---

## Prompt 1.4 — Vue pages + TypeScript types

```
TASK 1 — TypeScript types (resources/js/types/school.ts)
  Grade, Stream, Subject, GradeSubject, TimetableSlot, TeacherTimetable

TASK 2 — Composable: useClassStructure
  createGrade, createStream, createSubject, linkSubject, assignClassTeacher,
  createTimetableSlot(data), fetchStreamTimetable(streamId)

TASK 3 — Page: School/Grades/Index.vue
  Grouped by level (Primary | Junior Secondary | Senior Secondary) using section headers.
  Each grade row: name, ECZ badge (if is_ecz_year), stream count, subject count, actions.
  "Add Grade" inline form in a slide-over panel.

TASK 4 — Page: School/Streams/Index.vue
  Filter by grade (tabs at top: Grade 1 | Grade 2 … ).
  Card grid per stream: stream name, class teacher avatar + name, pupil count / capacity bar,
  "View Class" button. "Add Stream" button per grade.

TASK 5 — Page: School/Subjects/Index.vue
  Category tabs (Core | Elective | Language | Vocational | Religious | PE).
  Table per tab: name, code, ECZ badge, linked grades count, edit/delete.
  "Link to Grade" button → modal with grade multi-select + core toggle + weight inputs.

TASK 6 — Page: School/Timetable/View.vue
  Week grid: 5 columns (Mon–Fri) × N period rows.
  Colour-coded blocks per subject (hash subject_id to Tailwind bg class).
  Toggle between stream view (select stream dropdown) and teacher view (select teacher).
  Conflict highlighted in red.
  Print-friendly: window.print() button.

TASK 7 — Pest feature tests
  it('headteacher can create a grade')
  it('stream cannot be created without a grade')
  it('timetable slot conflict is detected and rejected')
  it('subject weights must sum to 100')
  it('subject teacher cannot create grades')
```

---

# MODULE 2 — Academic Calendar

## Prompt 2.1–2.4 (condensed)

```
MIGRATIONS (2.1):
  academic_years: id, school_id FK → schools.id, name varchar(10) e.g. "2025",
    start_date date, end_date date,
    is_current tinyint default 0, timestamps
    UNIQUE(school_id, name)

  terms: id, school_id FK → schools.id, academic_year_id FK → academic_years.id,
    name varchar(20) e.g. "Term 1", number tinyint (1/2/3),
    start_date date, end_date date, is_current tinyint default 0,
    ca_deadline date nullable, exam_start date nullable, exam_end date nullable,
    timestamps
    UNIQUE(school_id, academic_year_id, number)

  school_holidays: id, school_id FK → schools.id, term_id nullable FK → terms.id,
    name varchar(100), start_date date, end_date date,
    type enum('public_holiday','school_holiday','event'), timestamps

MODELS + DTOs (2.2):
  AcademicYear: belongsTo(School), hasMany(Term)
    scopeCurrent($q): where is_current = 1
    static currentForSchool(int $schoolId): ?AcademicYear

  Term: belongsTo(School), belongsTo(AcademicYear), hasMany(SchoolHoliday)
    scopeCurrent($q): where is_current = 1
    getWeeksAttribute(): int — calculated from start to end date minus holidays
    isRegistrationOpen(): bool — today between start_date and ca_deadline

  SchoolHoliday: belongsTo(Term), belongsTo(School)

  Data DTOs:
    CreateAcademicYearData: name required, start_date, end_date, is_current bool
    CreateTermData: academic_year_id required, name, number int 1–3,
      start_date, end_date, ca_deadline nullable, exam_start nullable, exam_end nullable
    CreateHolidayData: name, start_date, end_date, type, term_id nullable

SERVICES + CONTROLLERS (2.3):
  CalendarService:
    createAcademicYear(int $schoolId, CreateAcademicYearData): AcademicYear
      If is_current=true: set all others to is_current=false for this school first.
    createTerm(int $schoolId, CreateTermData): Term
      Validates terms per year ≤ 3. Sets is_current if term contains today.
    setCurrentTerm(int $termId): Term
    addHoliday(int $schoolId, CreateHolidayData): SchoolHoliday
    getCurrentAcademicContext(int $schoolId): array
      Returns { academic_year, current_term, terms, weeks_remaining }

  AcademicYearController (resource, headteacher/admin)
  TermController (resource)
  HolidayController (invokable store + destroy)

PAGES + TYPES (2.4):
  types/calendar.ts: AcademicYear, Term, SchoolHoliday, AcademicContext
  composable: useCalendar

  Pages:
    Calendar/Index.vue — year overview: 3 term cards side by side.
      Each term card: name, date range, weeks count, exam period, holiday list,
      "current" badge, holiday add button.
    Calendar/AcademicYears/Index.vue — year list with current badge, term count.

  Tests:
    it('can create academic year and set as current')
    it('only 3 terms allowed per academic year')
    it('setting a term current deactivates others')
```

---

# MODULE 3 — Pupil Management & Admissions

## Prompt 3.1 — Migrations

```
TASK 1 — pupils table
  id, school_id FK → schools.id,
  admission_no varchar(30)  UNIQUE(school_id, admission_no),
  first_name varchar(50), last_name varchar(50), other_name varchar(50) nullable,
  sex enum('male','female'), dob date,
  place_of_birth varchar(100) nullable, nationality varchar(64) default 'Zambian',
  religion varchar(50) nullable, tribe varchar(50) nullable,
  disability enum('none','visual','hearing','physical','intellectual','other') default 'none',
  disability_details text nullable, blood_group varchar(5) nullable,
  previous_school varchar(150) nullable, date_of_admission date,
  grade_id FK → grades.id, stream_id nullable FK → streams.id,
  academic_year_id FK → academic_years.id,
  status enum('active','transferred','withdrawn','completed','suspended') default 'active',
  transfer_school varchar(150) nullable, transfer_date date nullable,
  timestamps

TASK 2 — guardians table
  id, school_id FK → schools.id,
  user_id nullable FK → users.id  (for guardian portal access),
  first_name varchar(50), last_name varchar(50),
  relationship enum('father','mother','guardian','grandparent','sibling','other'),
  phone varchar(25), phone2 varchar(25) nullable,
  email varchar(128) nullable unique,
  nrc varchar(25) nullable, occupation varchar(100) nullable,
  employer varchar(100) nullable, address text nullable, timestamps

TASK 3 — pupil_guardians pivot
  id, pupil_id FK → pupils.id, guardian_id FK → guardians.id,
  is_primary tinyint default 0,
  is_emergency tinyint default 0,
  can_pickup tinyint default 1, timestamps
  UNIQUE(pupil_id, guardian_id)

TASK 4 — pupil_transfers table (audit trail for transfers)
  id, school_id FK → schools.id, pupil_id FK → pupils.id,
  from_school varchar(150), to_school varchar(150),
  transfer_date date, reason text nullable, approved_by FK → users.id, timestamps

Run: php artisan migrate

DELIVERABLES CHECKLIST
- [ ] 4 migrations, php artisan migrate clean
```

---

## Prompt 3.2 — Models + Data DTOs

```
TASK 1 — Pupil model
  $fillable all.
  HasMedia: registerMediaCollections() → 'profile-photo' (singleFile), 'documents'
  HasAudit trait.
  belongsTo(School), belongsTo(Grade), belongsTo(Stream), belongsTo(AcademicYear)
  belongsToMany(Guardian, 'pupil_guardians')->withPivot('is_primary','is_emergency','can_pickup')
  hasMany(AttendanceRecord), hasMany(TermResult), hasMany(FeeInvoice), hasMany(PupilTransfer)
  hasOne(EczCandidate)

  Scopes: scopeActive($q), scopeForGrade($q, int $gradeId),
    scopeForStream($q, int $streamId), scopeForSchool($q, int $schoolId)

  getPrimaryGuardianAttribute(): ?Guardian
    → first guardian where pivot is_primary = 1
  getFullNameAttribute(): string
  getAgeAttribute(): int — from dob

  static generateAdmissionNo(int $schoolId, int $year): string
    → format: {school_code}/{year}/{padded_sequence} e.g. "MPS/2025/0042"

TASK 2 — Guardian model
  $fillable all. belongsTo(School), belongsTo(User)
  belongsToMany(Pupil, 'pupil_guardians')->withPivot(...)
  getFullNameAttribute()
  hasPupilsAtSchool(int $schoolId): bool

TASK 3 — PupilGuardian model (pivot)
  $table = 'pupil_guardians', $timestamps = true
  belongsTo(Pupil), belongsTo(Guardian)

TASK 4 — PupilTransfer model
  belongsTo(School), belongsTo(Pupil), belongsTo(User, 'approved_by')

TASK 5 — Data DTOs
  AdmitPupilData: first_name, last_name, other_name nullable, sex required enum,
    dob required date, nationality default Zambian, religion nullable, tribe nullable,
    disability enum, disability_details nullable, blood_group nullable,
    previous_school nullable, date_of_admission required date,
    grade_id required exists:grades, stream_id nullable exists:streams,
    academic_year_id required exists:academic_years

  UpdatePupilData: same fields all nullable (partial update)

  StoreGuardianData: first_name, last_name, relationship, phone required,
    phone2 nullable, email nullable unique:guardians, nrc nullable,
    occupation nullable, employer nullable, address nullable,
    is_primary bool, is_emergency bool, can_pickup bool

  TransferPupilData: to_school required string, transfer_date required date,
    reason nullable, stream_id nullable (for intra-school stream change)
    type: 'external' | 'internal'  (external = leaves school, internal = change stream)

DELIVERABLES CHECKLIST
- [ ] 4 models with full relationships, scopes, computed attributes
- [ ] 4 Data DTOs
- [ ] Pupil::generateAdmissionNo static method
```

---

## Prompt 3.3 — Services + Controllers + Routes

```
TASK 1 — PupilService
  admit(int $schoolId, AdmitPupilData $data): Pupil
    → generate admission_no; create pupil; optionally upload photo.

  addGuardian(int $pupilId, StoreGuardianData $data): Guardian
    → create or find guardian; attach to pupil via pivot; set is_primary on one only.

  transfer(int $pupilId, TransferPupilData $data, int $approvedBy): Pupil
    If external: set pupil status='transferred', record PupilTransfer.
    If internal: update stream_id only.

  promoteGrade(int $pupilId, int $newGradeId, ?int $newStreamId): Pupil
    → update grade_id, stream_id, academic_year_id; log in audit.

  bulkPromote(int $streamId, int $toGradeId, ?int $toStreamId): int
    → promote all active pupils in stream; returns count promoted.

  withdraw(int $pupilId, string $reason): Pupil
    → set status='withdrawn'; log reason in audit.

  findByAdmissionNo(int $schoolId, string $admissionNo): ?Pupil

  getSchoolStatistics(int $schoolId): array
    → { total_pupils, by_grade: [{grade, count}], by_gender: {male, female},
        by_status: {active, transferred, withdrawn} }

TASK 2 — PupilController (resource)
  index()  → paginated list; filters: grade_id, stream_id, sex, status, search (name/admission_no)
  create() → render 'Pupils/Create' with grades, streams, current academic year
  store(AdmitPupilData) → PupilService::admit; redirect to pupil show
  show(Pupil) → render 'Pupils/Show' with guardians, term results stub, attendance summary
  edit(Pupil) → render 'Pupils/Edit'
  update(UpdatePupilData, Pupil) → update fields; redirect
  destroy(Pupil) → soft check: cannot delete if has term results; withdraw instead

TASK 3 — GuardianController (invokable store + destroy per pupil)
  store: add guardian to pupil
  destroy: detach guardian from pupil (not delete guardian record)

TASK 4 — PupilTransferController (invokable)
  __invoke(TransferPupilData $data, Pupil $pupil)
  Calls PupilService::transfer. Gate: headteacher|registrar.

TASK 5 — PupilPromotionController (invokable)
  __invoke(Request, Stream $stream) — bulk promote
  Validates: to_grade_id, to_stream_id nullable.
  Gate: headteacher|school-admin.

TASK 6 — PupilStatisticsController (invokable GET)
  Returns school statistics for dashboard widget.

TASK 7 — Routes
  Route::middleware(['auth','verified','school.context'])->group(function () {
      Route::resource('pupils', PupilController::class);
      Route::post('pupils/{pupil}/guardians', [GuardianController::class,'store'])
           ->name('pupils.guardians.store');
      Route::delete('pupils/{pupil}/guardians/{guardian}', [GuardianController::class,'destroy'])
           ->name('pupils.guardians.destroy');
      Route::post('pupils/{pupil}/transfer', PupilTransferController::class)
           ->name('pupils.transfer');
      Route::post('streams/{stream}/promote', PupilPromotionController::class)
           ->name('streams.promote')->middleware('role:headteacher|school-admin');
      Route::get('school/statistics', PupilStatisticsController::class)
           ->name('school.statistics');
  });

DELIVERABLES CHECKLIST
- [ ] PupilService (8 methods)
- [ ] PupilController (7 methods)
- [ ] 4 supporting controllers
- [ ] Routes
```

---

## Prompt 3.4 — Vue pages + TypeScript types

```
TASK 1 — TypeScript types (resources/js/types/pupils.ts)
  Pupil (with age computed, primary_guardian), Guardian, PupilGuardian,
  PupilTransfer, SchoolStatistics, AdmissionFormData

TASK 2 — Composables: usePupils, useGuardians

TASK 3 — Page: Pupils/Index.vue
  Filters bar: grade tab selector, stream dropdown, sex toggle, status badge filter, search input.
  Table: photo thumbnail, admission_no, full name, grade+stream, sex badge, status badge, age.
  "Admit Pupil" button (gated by pupil.create).
  Bulk actions: promote selected, export to CSV.
  Stats bar at top: total / male / female / active count chips.

TASK 4 — Page: Pupils/Create.vue (Admit Pupil)
  Multi-step form:
    Step 1 — Personal Details: name, sex, dob, nationality, religion, tribe, disability
    Step 2 — Academic Placement: grade, stream, academic year, previous school, date of admission
    Step 3 — Guardian Details: add primary guardian inline (StoreGuardianData fields)
    Step 4 — Photo Upload: profile photo via Spatie Media
  Step indicator at top. Back/Next/Submit.
  Auto-generates admission_no preview (computed from school code + year + sequence).

TASK 5 — Page: Pupils/Show.vue
  Tabs: Profile | Guardians | Academic | Fees | Attendance
  Profile tab: all personal details in a read-only grid. Edit button.
  Guardians tab: guardian cards (name, relationship, phone, primary/emergency badges).
    "Add Guardian" inline form. Remove button per card.
  Academic tab: current grade/stream, academic year, transfer history.
    "Transfer" button → TransferModal.vue
  Fees tab: fee invoices stub (links to Module 9).
  Attendance tab: monthly attendance summary (links to Module 4).

TASK 6 — Page: Pupils/Promotion.vue (headteacher)
  Select source stream → pupil list shown.
  Select target grade + target stream.
  "Promote All Active Pupils" with confirm modal showing count.

TASK 7 — Dashboard widget: SchoolStatisticsSummary.vue
  Used in the admin dashboard. Shows:
    Total pupils donut (by gender), By-grade bar (horizontal), Status breakdown pills.
  Data from PupilStatisticsController.

TASK 8 — Pest feature tests
  it('headteacher can admit a pupil and generates admission_no')
  it('can add a primary guardian to a pupil')
  it('external transfer sets pupil status to transferred')
  it('bulk promote updates all active pupils in stream')
  it('parent cannot admit pupils')
```

---

# MODULE 4 — Attendance

## Prompt 4.1–4.4 (condensed)

```
MIGRATIONS (4.1):
  attendance_sessions: id, school_id FK → schools.id, stream_id FK → streams.id,
    term_id FK → terms.id, date date, session_type enum('morning','afternoon','full_day') default 'full_day',
    recorded_by FK → users.id, finalized tinyint default 0, timestamps
    UNIQUE(school_id, stream_id, date, session_type)

  attendance_records: id, session_id FK → attendance_sessions.id,
    pupil_id FK → pupils.id,
    status enum('present','absent','late','excused','sick') default 'present',
    remarks varchar(255) nullable, timestamps
    UNIQUE(session_id, pupil_id)

MODELS + DTOs (4.2):
  AttendanceSession: belongsTo(School), belongsTo(Stream), belongsTo(Term), belongsTo(User,'recorded_by')
    hasMany(AttendanceRecord)
    scopeForDate($q, string $date), scopeForStream($q, int $streamId)
    isFinalized(): bool

  AttendanceRecord: belongsTo(AttendanceSession), belongsTo(Pupil)
    scopeAbsent($q), scopePresent($q)

  Data DTOs:
    OpenAttendanceSessionData: stream_id, term_id, date, session_type
    RecordAttendanceData: /** @var array<{pupil_id: int, status: string, remarks?: string}> */ records array
    BulkAttendanceData: session_id, records array of RecordAttendanceData items

SERVICES + CONTROLLERS (4.3):
  AttendanceService:
    openSession(int $schoolId, OpenAttendanceSessionData): AttendanceSession
      → check not already opened for same stream+date+session; auto-populate with all active pupils as 'absent'
    recordAttendance(BulkAttendanceData, int $recordedBy): Collection
      → upsert attendance_records; mark session finalized when all pupils accounted for
    finalizeSession(int $sessionId): AttendanceSession
    getClassRegister(int $streamId, string $date): array
      → { session, records with pupil names }
    getTermSummary(int $pupilId, int $termId): array
      → { total_days, present, absent, late, excused, percentage }
    getSchoolDailySummary(int $schoolId, string $date): array
      → per-stream breakdown

  AttendanceController: index (class register view), store (open session), update (record/finalize)
  AttendanceReportController (invokable): pupil term summary
  SchoolAttendanceSummaryController (invokable): school daily summary

PAGES + TYPES (4.4):
  types/attendance.ts: AttendanceSession, AttendanceRecord, ClassRegister, TermAttendanceSummary

  Pages:
    Attendance/ClassRegister.vue — class teacher view:
      Date picker (default today) + stream context from auth user's class_teacher relationship.
      Pupil list: name, photo, status radio (P/A/L/E/S), remarks input.
      "Open Register" → "Save" → "Finalise" workflow.
      If already finalized: read-only view with edit unlock button.
    Attendance/Report.vue — per-pupil term summary:
      Donut chart (present/absent/late breakdown), daily calendar heatmap.
    Attendance/SchoolSummary.vue — admin: stream-by-stream daily summary table.

  Tests:
    it('class teacher can open and record attendance')
    it('duplicate session for same stream and date is rejected')
    it('term attendance percentage calculated correctly')
    it('absent pupils auto-populated when session opened')
```

---

# MODULE 5 — Assessments & Results

## Prompt 5.1 — Migrations

```
TASK 1 — assessments
  id, school_id FK → schools.id, stream_id FK → streams.id,
  subject_id FK → subjects.id, term_id FK → terms.id,
  name varchar(100), type enum('ca_test','assignment','practical','mid_term','end_of_term'),
  max_marks int default 100, weight_percent int default 100
    comment 'proportion this assessment contributes to CA total',
  date date, instructions text nullable, created_by FK → users.id, timestamps

TASK 2 — assessment_scores
  id, assessment_id FK → assessments.id, pupil_id FK → pupils.id,
  marks_obtained decimal(5,2), grade_letter varchar(2) nullable,
  remarks text nullable, entered_by FK → users.id, entered_at datetime, timestamps
  UNIQUE(assessment_id, pupil_id)

TASK 3 — term_results
  id, school_id FK → schools.id, pupil_id FK → pupils.id,
  subject_id FK → subjects.id, term_id FK → terms.id,
  academic_year_id FK → academic_years.id,
  stream_id FK → streams.id,
  ca_marks decimal(5,2) nullable, exam_marks decimal(5,2) nullable,
  total_marks decimal(5,2) nullable, grade_letter varchar(2) nullable,
  points tinyint nullable comment 'ECZ-style: 1=best, 9=worst',
  position_in_stream smallint nullable,
  teacher_comment text nullable, published tinyint default 0, timestamps
  UNIQUE(school_id, pupil_id, subject_id, term_id)

TASK 4 — annual_results
  id, school_id FK → schools.id, pupil_id FK → pupils.id,
  academic_year_id FK → academic_years.id,
  total_marks decimal(6,2), average_marks decimal(5,2),
  position_in_stream smallint nullable,
  grade_stream_id FK → streams.id,
  promoted tinyint default 0,
  headteacher_comment text nullable, timestamps
  UNIQUE(school_id, pupil_id, academic_year_id)

TASK 5 — report_cards
  id, school_id FK → schools.id, pupil_id FK → pupils.id,
  term_id FK → terms.id, academic_year_id FK → academic_years.id,
  stream_id FK → streams.id,
  class_teacher_comment text nullable,
  headteacher_comment text nullable,
  attendance_days int nullable, attendance_present int nullable,
  generated_at datetime nullable, published_at datetime nullable,
  generated_by FK → users.id nullable, timestamps
  UNIQUE(school_id, pupil_id, term_id)

Run: php artisan migrate

DELIVERABLES CHECKLIST
- [ ] 5 migrations, php artisan migrate clean
```

---

## Prompt 5.2 — Models + Data DTOs

```
TASK 1 — Assessment model
  $fillable all. HasAudit.
  belongsTo(School), belongsTo(Stream), belongsTo(Subject), belongsTo(Term),
  belongsTo(User,'created_by'), hasMany(AssessmentScore)
  scopeForStream, scopeForSubject, scopeForTerm

TASK 2 — AssessmentScore model
  $fillable all.
  belongsTo(Assessment), belongsTo(Pupil)

TASK 3 — TermResult model
  $fillable all.
  belongsTo(School), belongsTo(Pupil), belongsTo(Subject),
  belongsTo(Term), belongsTo(AcademicYear), belongsTo(Stream)
  scopePublished($q), scopeForPupil($q, int $pupilId)
  Static: computeGradeLetter(float $total): string
    → use config('zssms.grading_scale') to return letter
  Static: computePoints(string $letter): int
    → A=1, B=2, C=3, D=4, F=9

TASK 4 — AnnualResult + ReportCard models
  Standard models with relationships.

TASK 5 — Data DTOs
  CreateAssessmentData: stream_id, subject_id, term_id, name, type, max_marks, weight_percent, date, instructions nullable
  EnterScoresData: assessment_id exists, /** @var array<{pupil_id: int, marks: float, remarks?: string}> */ scores array
  EnterTermResultData: pupil_id, subject_id, term_id, ca_marks nullable, exam_marks nullable, teacher_comment nullable
  BulkEnterTermResultsData: stream_id, subject_id, term_id, /** @var EnterTermResultData[] */ results array
  PublishResultsData: stream_id, term_id (publish all results for stream+term)
  GenerateReportCardData: stream_id, term_id (generate cards for entire stream)
  AddReportCommentData: pupil_id, term_id, class_teacher_comment nullable, headteacher_comment nullable, attendance_days int, attendance_present int

DELIVERABLES CHECKLIST
- [ ] 5 models, TermResult static compute methods
- [ ] 6 Data DTOs
```

---

## Prompt 5.3 — Services + Controllers + Routes

```
TASK 1 — ResultsService
  createAssessment(int $schoolId, CreateAssessmentData): Assessment

  enterScores(EnterScoresData, int $enteredBy): Collection
    → upsert assessment_scores; auto-compute grade_letter per score

  computeCAMarks(int $pupilId, int $subjectId, int $termId): float
    → aggregate assessment_scores weighted by weight_percent for CA-type assessments

  enterTermResults(BulkEnterTermResultsData, int $enteredBy): Collection
    → for each result: compute total = (ca_marks * ca_weight + exam_marks * exam_weight) / 100
    → assign grade_letter via TermResult::computeGradeLetter
    → assign points via TermResult::computePoints
    → upsert on UNIQUE key

  computeStreamPositions(int $streamId, int $termId): void
    → rank pupils by total_marks per subject; update position_in_stream

  publishResults(PublishResultsData, int $publishedBy): int
    → set published=1 for all term_results for stream+term; returns count

  generateReportCards(GenerateReportCardData, int $generatedBy): Collection
    → for each pupil in stream: create/update report_card record
    → pull all published term_results for pupil+term
    → compute overall_average, overall_position
    → set generated_at=now()

  publishReportCards(int $streamId, int $termId): int
    → set published_at=now() on report_cards

  getPupilTermReport(int $pupilId, int $termId): array
    → { report_card, results by subject, position, attendance }

TASK 2 — AssessmentController (resource)
  index: filter by stream+subject+term+type. show: with scores table.
  store: gate subject-teacher|class-teacher.

TASK 3 — AssessmentScoreController (invokable)
  __invoke(EnterScoresData) → ResultsService::enterScores

TASK 4 — TermResultController
  index: stream+term selector → results grid. store: BulkEnterTermResultsData.
  Additional invokable: ComputeCAMarksController (POST — auto-fill CA from assessments).
  Additional invokable: PublishResultsController (POST — gate: headteacher).

TASK 5 — ReportCardController
  index: list report cards for stream+term (headteacher view).
  show: single pupil report card (parent can view own child).
  store: GenerateReportCardData — headteacher generates.
  update: AddReportCommentData — class teacher adds comment.
  Additional invokable: PublishReportCardsController.

TASK 6 — Routes
  Route::middleware(['auth','verified','school.context'])->group(function () {
      Route::resource('assessments', AssessmentController::class);
      Route::post('assessments/{assessment}/scores', AssessmentScoreController::class)
           ->name('assessments.scores.enter');
      Route::get('term-results',    [TermResultController::class,'index'])->name('term-results.index');
      Route::post('term-results',   [TermResultController::class,'store'])->name('term-results.store');
      Route::post('term-results/compute-ca',  ComputeCAMarksController::class)->name('term-results.compute-ca');
      Route::post('term-results/publish',     PublishResultsController::class)->name('term-results.publish');
      Route::resource('report-cards', ReportCardController::class)->only(['index','show','store','update']);
      Route::post('report-cards/publish', PublishReportCardsController::class)->name('report-cards.publish');
  });

DELIVERABLES CHECKLIST
- [ ] ResultsService (8 methods)
- [ ] 4 controllers + 3 invokable
- [ ] Routes
```

---

## Prompt 5.4 — Vue pages + TypeScript types

```
TASK 1 — TypeScript types (resources/js/types/results.ts)
  Assessment, AssessmentScore, TermResult, AnnualResult, ReportCard,
  PupilTermReport, GradeScale (from config)

TASK 2 — Composables: useAssessments, useResults, useReportCards
  useResults: enterResults(data), computeCA(streamId, subjectId, termId),
    publishResults(streamId, termId), fetchStreamResults(streamId, termId)

TASK 3 — Page: Assessments/Index.vue
  Stream + Subject + Term selectors at top.
  Cards per assessment: name, type badge, date, max marks, scores entered count.
  "Enter Scores" button → AssessmentScoreEntry.vue modal or page.

TASK 4 — Page: Assessments/ScoreEntry.vue
  Table: pupil name, marks input (0–max), auto-computed grade letter badge.
  "Save All" button. Unsaved changes warning.
  Marks outside range highlighted red.

TASK 5 — Page: Results/Entry.vue (teacher bulk mark entry)
  Stream + Subject + Term selectors.
  Spreadsheet table: pupil name, CA marks (input or "Auto-compute from assessments" button),
  exam marks input, total (computed), grade letter badge (live from useGrading()),
  points (computed), teacher comment input per row.
  Column headers show ca_weight% / exam_weight% from grade_subjects.
  "Save Draft" and "Publish" (headteacher only) actions.

TASK 6 — Page: Results/StreamResults.vue
  Full stream result grid: rows = pupils, columns = subjects.
  Each cell: total marks + grade badge. Footer row: averages.
  Position column (computed after publish). Export to PDF / Excel buttons.

TASK 7 — Page: ReportCards/Show.vue
  Styled to match Zambian MoE report card format:
    School header (name, logo, MoE no.), pupil details,
    Results table (subject, CA, exam, total, grade, points),
    Overall summary (total marks, average, position, division),
    Attendance record (days present / total),
    Class teacher comment, headteacher comment,
    Next term dates.
  Print button (window.print()).
  For parents: this page is accessible via ParentLayout.

TASK 8 — Pest feature tests
  it('teacher can enter term results')
  it('total marks computed from ca and exam weights correctly')
  it('positions computed correctly for stream')
  it('results not visible to parent before published')
  it('report card generated for entire stream')
  it('headteacher comment added to report card')
```

---

# MODULE 6 — ECZ Exam Management

## Prompt 6.1–6.4 (condensed)

```
MIGRATIONS (6.1):
  ecz_candidates: id, school_id FK → schools.id, pupil_id FK → pupils.id,
    exam_year year, grade_level tinyint  (7, 9, or 12),
    index_number varchar(30) nullable unique,
    centre_number varchar(20) nullable,
    registration_status enum('pending','submitted','confirmed','withdrawn') default 'pending',
    division varchar(10) nullable comment 'Calculated after results e.g. Div 1',
    total_points int nullable, timestamps
    UNIQUE(school_id, pupil_id, exam_year, grade_level)

  ecz_subject_entries: id, candidate_id FK → ecz_candidates.id,
    subject_id FK → subjects.id, entered_by FK → users.id,
    predicted_grade varchar(2) nullable,
    actual_grade varchar(2) nullable comment 'A-F or U',
    actual_points tinyint nullable, timestamps
    UNIQUE(candidate_id, subject_id)

  ecz_results: id, school_id FK → schools.id, candidate_id FK → ecz_candidates.id,
    published_at datetime nullable, raw_result_file varchar(255) nullable,
    entry_method enum('manual','bulk_upload') default 'manual', timestamps

MODELS + DTOs (6.2):
  EczCandidate: belongsTo(School), belongsTo(Pupil), hasMany(EczSubjectEntry), hasOne(EczResult)
    scopeForExamYear($q, int $year), scopeForGradeLevel($q, int $level)
    computeDivision(): string — aggregate best 6/7/8 subjects by ECZ rules per grade level
    getPredictedDivisionAttribute(): string — based on predicted_grades using useGrading logic

  EczSubjectEntry: belongsTo(EczCandidate), belongsTo(Subject)
  EczResult: belongsTo(EczCandidate), belongsTo(School)

  Data DTOs:
    RegisterCandidateData: pupil_id, grade_level in:7,9,12, exam_year year
    AddSubjectEntryData: subject_id, predicted_grade nullable in:A,B,C,D,F
    EnterActualResultData: /** @var array<{candidate_id, subject_id, actual_grade, actual_points}> */ results array
    UpdateIndexNumberData: index_number required, centre_number required

SERVICES + CONTROLLERS (6.3):
  EczService:
    registerCandidate(int $schoolId, RegisterCandidateData): EczCandidate
    addSubjectEntries(int $candidateId, array $subjectIds, int $enteredBy): Collection
    setPredictedGrade(int $entryId, string $grade): EczSubjectEntry
    updateIndexNumber(int $candidateId, UpdateIndexNumberData): EczCandidate
    enterActualResults(EnterActualResultData): Collection
      → update actual_grade, compute points, compute division, update candidate
    getCandidateList(int $schoolId, int $gradeLevel, int $examYear): Collection
    getSchoolPassRate(int $schoolId, int $gradeLevel, int $examYear): array
      → { registered, passed, failed, div1, div2, div3, div4, pass_rate_pct }

  EczCandidateController (resource: index, store, show, update, destroy)
  EczResultEntryController (invokable — bulk result entry)
  EczPassRateController (invokable — analytics)

PAGES + TYPES (6.4):
  types/ecz.ts: EczCandidate, EczSubjectEntry, EczResult, PassRateReport

  Pages:
    ECZ/Candidates/Index.vue — filter by grade_level (7/9/12) + exam_year.
      Table: index_number, pupil name, registration_status badge, subjects count, predicted division badge.
      "Register Candidate" button + bulk-register from grade (register all Grade 7 pupils, etc.).
    ECZ/Candidates/Show.vue — candidate detail: subject entries table (subject, predicted, actual),
      Predicted Division card, Index Number input, update form.
    ECZ/Results/Entry.vue — grid for entering actual ECZ results:
      Candidate rows × subject columns, grade letter inputs (A/B/C/D/E/F/U/X).
      Auto-compute points + division per row.
    ECZ/Analytics.vue — pass rate dashboard:
      Pie chart (div1/div2/div3/div4/fail), year-over-year comparison bar chart,
      subject performance table (% A-C per subject).

  Tests:
    it('only grade 7, 9, 12 pupils can be registered as ECZ candidates')
    it('division computed correctly for grade 12')
    it('predicted grade saved per subject entry')
    it('actual results entered and division updated')
```

---

# MODULE 7 — Parent / Guardian Portal

## Prompt 7.1–7.4 (condensed)

```
MIGRATIONS (7.1):
  guardian_portal_access: id, guardian_id FK → guardians.id,
    user_id FK → users.id unique comment 'Jetstream user account for guardian',
    activated_at datetime nullable, last_login_at datetime nullable, timestamps

  parent_messages: id, school_id FK → schools.id,
    sender_id FK → users.id, recipient_id FK → users.id,
    pupil_id nullable FK → pupils.id,
    message text, read_at datetime nullable, timestamps

  portal_notifications: id, user_id FK → users.id, school_id FK → schools.id,
    title varchar(150), message text, type varchar(50),
    related_type varchar(100) nullable comment 'e.g. App\\Models\\TermResult',
    related_id unsignedBigInt nullable,
    read_at datetime nullable, timestamps

MODELS + DTOs (7.2):
  GuardianPortalAccess: belongsTo(Guardian), belongsTo(User)
  ParentMessage: belongsTo(User,'sender_id'), belongsTo(User,'recipient_id'), belongsTo(Pupil)
  PortalNotification: belongsTo(User), belongsTo(School)
    scopeUnread($q): whereNull('read_at')

  Data DTOs:
    CreatePortalAccountData: guardian_id exists:guardians, email required unique:users
    SendParentMessageData: recipient_id exists:users, pupil_id nullable, message required

SERVICES + CONTROLLERS (7.3):
  ParentPortalService:
    createPortalAccount(CreatePortalAccountData, string $temporaryPassword): User
      → Create Jetstream User, assign 'parent' role, link GuardianPortalAccess.
      → Send welcome email with temp password.
    getChildrenForParent(int $userId): Collection
      → Returns pupils linked to this guardian.
    getChildSummary(int $userId, int $pupilId): array
      → Verify parent-child link. Returns { pupil, current_term, attendance_summary,
         latest_results (published only), fee_balance, notices }
    sendMessage(SendParentMessageData, int $senderId): ParentMessage
    getMessages(int $userId): Collection — thread view grouped by other party
    markNotificationRead(int $notifId, int $userId): void
    notifyParent(int $guardianUserId, string $title, string $message, string $type, ?Model $related): void
      → Creates PortalNotification, optionally queues SMS via Module 14

  PortalController (auth:parent — all routes below use role:parent middleware):
    dashboard() → Inertia 'Parent/Dashboard' with getChildrenForParent
    childDetail(Pupil $pupil) → getChildSummary
  ParentMessageController (invokable send + index)
  PortalNotificationController (index, markRead)

  GuardianAccountController (admin/headteacher):
    store(CreatePortalAccountData) → create portal account for guardian
    resendCredentials(Guardian) → reset temp password + resend email

PAGES + TYPES (7.4):
  types/portal.ts: ChildSummary, ParentMessage, PortalNotification

  Pages (use ParentLayout.vue):
    Parent/Dashboard.vue — child selector tabs (if multiple children).
      Per-child summary cards: attendance %, latest result average, fee balance chip,
      upcoming events from notices.
    Parent/Child/Results.vue — published term results per child.
      Term selector. Subject table with grade badges. Report card view/print button.
    Parent/Child/Attendance.vue — monthly attendance summary for child.
    Parent/Child/Fees.vue — fee invoices and payment status for child.
    Parent/Messages.vue — messaging thread with class teacher / school admin.
    Parent/Notifications.vue — notification feed, mark read.

  Tests:
    it('parent can only view own child data')
    it('parent cannot view another guardians child')
    it('portal account creation sends welcome email')
    it('parent sees only published results')
```

---

# MODULE 8 — Staff & HR

## Prompt 8.1–8.4 (condensed)

```
MIGRATIONS (8.1):
  staff: id, user_id unique FK → users.id, school_id FK → schools.id,
    employee_no varchar(30) UNIQUE(school_id, employee_no),
    position enum('headteacher','deputy_headteacher','class_teacher','subject_teacher',
                  'bursar','librarian','boarding_master','transport_coordinator',
                  'feeding_coordinator','admin','support','counsellor'),
    department varchar(100) nullable,
    subjects_taught json nullable comment 'array of subject_ids',
    employment_type enum('permanent','contract','temporary','volunteer'),
    employment_date date, end_date date nullable,
    basic_salary decimal(10,2) default 0,
    bank varchar(100) nullable, bank_account varchar(30) nullable,
    bank_branch varchar(100) nullable, nrc varchar(25) nullable,
    tax_id varchar(25) nullable, tpin varchar(15) nullable comment 'Zambia TPIN',
    napsa_no varchar(25) nullable comment 'Zambia NAPSA number',
    status enum('active','terminated','suspended','on_leave') default 'active', timestamps

  leave_types: id, school_id FK → schools.id,
    name varchar(50), days_per_year int, accrues bool default true, timestamps

  leaves: id, school_id FK → schools.id, staff_id FK → staff.id,
    leave_type_id FK → leave_types.id,
    start_date date, end_date date, total_days int,
    reason text, status enum('pending','approved','rejected','cancelled') default 'pending',
    approved_by nullable FK → users.id, comment text nullable, timestamps

  payroll: id, school_id FK → schools.id, staff_id FK → staff.id,
    month tinyint, year smallint, basic_salary decimal(10,2),
    allowances decimal(10,2) default 0, deductions decimal(10,2) default 0,
    napsa_employee decimal(10,2) default 0 comment '5% employee NAPSA',
    napsa_employer decimal(10,2) default 0 comment '5% employer NAPSA',
    paye decimal(10,2) default 0 comment 'Zambia PAYE',
    net_pay decimal(10,2), paid_at datetime nullable,
    approved_by nullable FK → users.id, timestamps
    UNIQUE(school_id, staff_id, month, year)

MODELS + DTOs (8.2):
  Staff: belongsTo(User), belongsTo(School)
    hasMany(Leave), hasMany(Payroll)
    scopeTeachers($q): where position IN ('class_teacher','subject_teacher','headteacher','deputy')
    getFullNameAttribute() via user relationship
    getSubjectsTaughtAttribute(): array — cast json

  LeaveType, Leave, Payroll — standard models with relationships.
  Leave: scopePending($q), scopeForStaff($q, int $staffId)

  Data DTOs:
    CreateStaffData: user_id, employee_no, position, employment_type, employment_date,
      basic_salary, department nullable, subjects_taught array nullable, napsa_no nullable, tpin nullable
    ApplyLeaveData: leave_type_id, start_date, end_date, reason required
    ApproveLeaveData: status in:approved,rejected, comment nullable
    GeneratePayrollData: month int 1–12, year int, include_all_staff bool default true

SERVICES + CONTROLLERS (8.3):
  HRService:
    createStaff(int $schoolId, CreateStaffData): Staff
    applyLeave(int $staffId, ApplyLeaveData): Leave
    approveLeave(int $leaveId, ApproveLeaveData, int $approvedBy): Leave
    calculateLeaveBalance(int $staffId, int $leaveTypeId): int
    generatePayroll(int $schoolId, GeneratePayrollData): Collection
      → For each active staff: compute NAPSA (5% each), PAYE (Zambia tax bands), net_pay
    approvePayroll(int $payrollId, int $approvedBy): Payroll
    getStaffDirectory(int $schoolId): Collection  // with user, position, department

  StaffController (resource), LeaveController (resource + approve invokable)
  PayrollController (resource + generate invokable + approve invokable)

PAGES + TYPES (8.4):
  types/hr.ts: Staff, LeaveType, Leave, Payroll, PayrollSummary
  composable: useHR

  Pages:
    HR/Staff/Index.vue — directory: photo, name, position badge, department, status
    HR/Staff/Show.vue — profile tabs: Employment | Subjects | Leave History | Payroll
    HR/Leave/Apply.vue — type selector (days remaining shown), date range, reason
    HR/Leave/Approvals.vue — pending leaves table with approve/reject
    HR/Payroll/Index.vue — monthly payroll: generate, staff list with net pay, approve button

  Tests:
    it('staff member can apply for leave')
    it('leave balance deducted on approval')
    it('NAPSA and PAYE calculated correctly')
    it('payroll cannot be generated twice for same month/year')
```

---

# MODULE 9 — School Finance

## Prompt 9.1–9.4 (condensed)

```
MIGRATIONS (9.1):
  fee_structures: id, school_id FK → schools.id, grade_id nullable FK → grades.id,
    term_id FK → terms.id, academic_year_id FK → academic_years.id,
    name varchar(100), description text nullable, amount decimal(10,2),
    applies_to enum('all','day_scholars','boarders','new_pupils') default 'all',
    is_mandatory tinyint default 1, timestamps

  fee_invoices: id, school_id FK → schools.id, pupil_id FK → pupils.id,
    fee_structure_id FK → fee_structures.id, term_id FK → terms.id,
    academic_year_id FK → academic_years.id,
    amount decimal(10,2), discount decimal(10,2) default 0,
    balance_due decimal(10,2), due_date date nullable,
    status enum('unpaid','partial','paid','waived') default 'unpaid', timestamps
    UNIQUE(school_id, pupil_id, fee_structure_id, term_id)

  fee_payments: id, school_id FK → schools.id, pupil_id FK → pupils.id,
    invoice_id FK → fee_invoices.id, amount decimal(10,2),
    payment_method enum('cash','airtel_money','mtn_momo','bank_transfer','cheque'),
    reference varchar(100) nullable, transaction_id varchar(150) nullable,
    mobile_money_provider varchar(20) nullable,
    received_by FK → users.id, payment_date date, timestamps

  expenses: id, school_id FK → schools.id,
    category enum('salaries','utilities','maintenance','supplies','transport',
                  'feeding','library','other'),
    description text, amount decimal(10,2), expense_date date,
    approved_by nullable FK → users.id,
    receipt_no varchar(50) nullable, timestamps
    (add HasMedia on model for receipt scans)

  budgets: id, school_id FK → schools.id, academic_year_id FK → academic_years.id,
    term_id nullable FK → terms.id,
    category varchar(100), amount decimal(10,2), timestamps
    UNIQUE(school_id, academic_year_id, term_id, category)

MODELS + DTOs (9.2):
  FeeStructure: belongsTo(School), belongsTo(Grade), belongsTo(Term), hasMany(FeeInvoice)
  FeeInvoice: belongsTo(School), belongsTo(Pupil), belongsTo(FeeStructure), belongsTo(Term)
    hasMany(FeePayment)
    getAmountPaidAttribute(): float — sum of payments
    getOutstandingAttribute(): float — balance_due - amount_paid
    scopeUnpaid, scopePartial, scopePaid, scopeOverdue
  FeePayment: belongsTo(School), belongsTo(Pupil), belongsTo(FeeInvoice)
  Expense: HasMedia ('receipts' collection), belongsTo(School)
  Budget: belongsTo(School), belongsTo(AcademicYear), belongsTo(Term)

  Data DTOs:
    CreateFeeStructureData: grade_id nullable, term_id, name, amount, applies_to, is_mandatory
    RaiseInvoiceData: pupil_id, fee_structure_id, term_id, discount decimal default 0, due_date nullable
    BulkRaiseInvoicesData: fee_structure_id, term_id, grade_id nullable (raise for all pupils in grade/school)
    RecordPaymentData: invoice_id, amount, payment_method, reference nullable,
      transaction_id nullable, payment_date, mobile_money_provider nullable
    CreateExpenseData: category, description, amount, expense_date, receipt_no nullable
    CreateBudgetData: academic_year_id, term_id nullable, category, amount

SERVICES + CONTROLLERS (9.3):
  FinanceService:
    createFeeStructure(int $schoolId, CreateFeeStructureData): FeeStructure
    raiseInvoice(int $schoolId, RaiseInvoiceData): FeeInvoice
    bulkRaiseInvoices(int $schoolId, BulkRaiseInvoicesData): int  // returns count
    recordPayment(RecordPaymentData, int $receivedBy): FeePayment
      → update invoice status (partial/paid) based on outstanding balance
    waiveInvoice(int $invoiceId, string $reason, int $waivedBy): FeeInvoice
    getPupilFeeStatement(int $pupilId, int $termId): array
      → { invoices, payments, total_due, total_paid, outstanding }
    getSchoolFeeReport(int $schoolId, int $termId): array
      → { total_invoiced, total_collected, outstanding, collection_rate_pct, by_grade }
    recordExpense(int $schoolId, CreateExpenseData, ?UploadedFile $receipt): Expense
    getBudgetVsActual(int $schoolId, int $academicYearId): array
      → { by_category: [{budget, actual, variance}] }

  FeeStructureController (resource), FeeInvoiceController (resource + waive invokable)
  FeePaymentController (invokable store), ExpenseController (resource), BudgetController (resource)
  FeeReportController (invokable GET — school finance summary)

PAGES + TYPES (9.4):
  types/finance.ts: FeeStructure, FeeInvoice, FeePayment, Expense, Budget, FeeStatement

  Pages:
    Finance/FeeStructures/Index.vue — fee schedule by term/grade: amount, applies_to badge, mandatory badge
    Finance/Invoices/Index.vue — all invoices: filter by grade, term, status. Bulk raise button.
    Finance/Invoices/Show.vue — pupil fee statement: invoices table, payments history, outstanding balance chip, record payment form
    Finance/Payments/Create.vue — record payment form: pupil search (admission_no or name), invoice select, amount, method, mobile money details
    Finance/Expenses/Index.vue — expense log with category filter, receipt thumbnail
    Finance/Budget/Index.vue — budget vs actual per category (horizontal bar chart via Chart.js)
    Finance/Reports/Index.vue — term collection summary: collection rate gauge, by-grade breakdown table

  Parent view: Parent/Child/Fees.vue (already covered in Module 7) — reads from getPupilFeeStatement.

  Tests:
    it('invoice status updated to partial on part payment')
    it('invoice status updated to paid when fully settled')
    it('bulk invoices raised for all active pupils in grade')
    it('budget vs actual calculated correctly')
    it('parent can view own child fee statement only')
```

---

# MODULE 10 — Library

## Prompt 10.1–10.4 (condensed)

```
MIGRATIONS (10.1):
  library_books: id, school_id FK → schools.id,
    title varchar(255), author varchar(255), isbn varchar(20) nullable,
    publisher varchar(100) nullable, publish_year year nullable,
    category varchar(100), subject_id nullable FK → subjects.id,
    copies_total int default 1, copies_available int default 1,
    shelf_location varchar(50) nullable, description text nullable, timestamps

  book_borrowings: id, school_id FK → schools.id,
    book_id FK → library_books.id,
    borrower_type enum('pupil','staff'),
    borrower_id unsignedBigInt  (polymorphic FK — no hard FK constraint; app enforces),
    borrowed_date date, due_date date, returned_date date nullable,
    fine_amount decimal(8,2) default 0,
    status enum('borrowed','returned','overdue','lost') default 'borrowed',
    issued_by FK → users.id, returned_to nullable FK → users.id, timestamps

MODELS + DTOs (10.2):
  LibraryBook: HasMedia ('book-cover' singleFile), belongsTo(School), belongsTo(Subject)
    hasMany(BookBorrowing)
    scopeAvailable($q): where copies_available > 0
    scopeByCategory($q, string $cat)
    getIsAvailableAttribute(): bool

  BookBorrowing: belongsTo(School), belongsTo(LibraryBook)
    morphTo-like: borrower() — resolves to Pupil or Staff based on borrower_type
    scopeOverdue($q): where due_date < today AND status = 'borrowed'
    getFineDueAttribute(): float — days overdue × fine_rate from settings

  Data DTOs:
    AddBookData: title, author, isbn nullable, category, copies_total int,
      shelf_location nullable, subject_id nullable
    IssueBorrowingData: book_id exists:library_books, borrower_type enum, borrower_id required,
      due_date date after:today
    ReturnBookData: borrowing_id exists:book_borrowings, returned_date date, fine_paid decimal nullable

SERVICES + CONTROLLERS (10.3):
  LibraryService:
    addBook(int $schoolId, AddBookData): LibraryBook
    issueBook(int $schoolId, IssueBorrowingData, int $issuedBy): BookBorrowing
      → decrement copies_available; create borrowing record
    returnBook(ReturnBookData, int $returnedBy): BookBorrowing
      → set returned_date + status='returned'; increment copies_available; compute fine
    getOverdueBorrowings(int $schoolId): Collection
    getBorrowerHistory(string $borrowerType, int $borrowerId): Collection
    searchBooks(int $schoolId, string $query): Collection

  LibraryBookController (resource), BorrowingController (resource: index, store, update for return)
  OverdueReportController (invokable GET)

PAGES + TYPES (10.4):
  types/library.ts: LibraryBook, BookBorrowing, BorrowerSummary

  Pages:
    Library/Books/Index.vue — searchable book catalogue: cover image, title, author, category badge, copies available / total.
    Library/Books/Show.vue — book detail + borrowing history table.
    Library/Borrow/Create.vue — issue form: book search, borrower type toggle (pupil/staff), borrower search, due date.
    Library/Return/Create.vue — return form: scan/search borrowing by pupil name, book title. Fine display if overdue.
    Library/Overdue.vue — overdue borrowings table with days overdue, fine amount, send SMS reminder button.

  Tests:
    it('issuing a book decrements available copies')
    it('returning a book increments available copies')
    it('overdue fine calculated correctly')
    it('book cannot be issued if copies_available is 0')
```

---

# MODULE 11 — Transport

## Prompt 11.1–11.4 (condensed)

```
MIGRATIONS (11.1):
  transport_routes: id, school_id FK → schools.id,
    name varchar(100), description text nullable,
    pickup_points json comment 'array of stop names',
    vehicle_registration varchar(20) nullable, vehicle_type varchar(50) nullable,
    capacity int default 50, driver_name varchar(100) nullable,
    driver_phone varchar(25) nullable, driver_user_id nullable FK → users.id,
    status enum('active','inactive') default 'active', timestamps

  pupil_transport: id, school_id FK → schools.id,
    pupil_id FK → pupils.id, route_id FK → transport_routes.id,
    pickup_point varchar(100), direction enum('to_school','from_school','both') default 'both',
    term_id FK → terms.id, fee_amount decimal(8,2) default 0,
    status enum('active','suspended') default 'active', timestamps
    UNIQUE(school_id, pupil_id, route_id, term_id)

MODELS + DTOs (11.2):
  TransportRoute: belongsTo(School), belongsTo(User,'driver_user_id')
    hasMany(PupilTransport)
    getOccupancyAttribute(): int — count active pupil_transport records
    getPickupPointsAttribute(): array — cast json
  PupilTransport: belongsTo(School), belongsTo(Pupil), belongsTo(TransportRoute), belongsTo(Term)
    scopeActive($q), scopeForTerm($q, int $termId)

  Data DTOs:
    CreateRouteData: name, pickup_points array, capacity int, vehicle_registration nullable, driver_name nullable, driver_phone nullable
    AssignPupilTransportData: pupil_id, route_id, pickup_point, direction, term_id, fee_amount

SERVICES + CONTROLLERS (11.3):
  TransportService:
    createRoute(int $schoolId, CreateRouteData): TransportRoute
    assignPupil(int $schoolId, AssignPupilTransportData): PupilTransport
      → check route capacity not exceeded
    removeAssignment(int $assignmentId): void
    getRouteManifest(int $routeId, int $termId): Collection  // pupil list for route
    getRouteSummary(int $schoolId, int $termId): array  // { routes, total_pupils, by_route }

  TransportRouteController (resource), PupilTransportController (invokable store + destroy)
  RouteManifestController (invokable GET)

PAGES + TYPES (11.4):
  types/transport.ts: TransportRoute, PupilTransport, RouteManifest

  Pages:
    Transport/Routes/Index.vue — route cards: name, vehicle reg, driver, capacity bar (occupied/total), status.
    Transport/Routes/Show.vue — route detail: pickup points list, pupil manifest table (name, grade, pickup stop).
      "Assign Pupil" button → search pupil, select pickup point.
    Transport/Manifest/Print.vue — printable daily route manifest (driver uses this).

  Tests:
    it('pupil assigned to route and manifest updated')
    it('capacity exceeded assignment rejected')
```

---

# MODULE 12 — School Feeding Programme

## Prompt 12.1–12.4 (condensed)

```
MIGRATIONS (12.1):
  feeding_sessions: id, school_id FK → schools.id,
    date date, meal_type enum('breakfast','lunch','snack'),
    stream_id nullable FK → streams.id comment 'null = whole school',
    recorded_by FK → users.id, finalized tinyint default 0, timestamps
    UNIQUE(school_id, date, meal_type, stream_id)

  feeding_records: id, session_id FK → feeding_sessions.id,
    pupil_id FK → pupils.id, served tinyint default 1, timestamps
    UNIQUE(session_id, pupil_id)

  feeding_stock: id, school_id FK → schools.id,
    item_name varchar(100), unit varchar(20),
    quantity_on_hand decimal(10,2), reorder_level decimal(10,2),
    last_restocked_at date nullable, cost_per_unit decimal(8,2) nullable, timestamps

  feeding_stock_movements: id, school_id FK → schools.id,
    stock_id FK → feeding_stock.id,
    type enum('restock','consumption','wastage','adjustment'),
    quantity decimal(10,2), notes text nullable,
    recorded_by FK → users.id, timestamps

MODELS + DTOs (12.2):
  FeedingSession: belongsTo(School), belongsTo(User,'recorded_by'), belongsTo(Stream)
    hasMany(FeedingRecord)
    getServedCountAttribute(): int
  FeedingRecord: belongsTo(FeedingSession), belongsTo(Pupil)
  FeedingStock: belongsTo(School), hasMany(FeedingStockMovement)
    scopeLowStock($q): where quantity_on_hand <= reorder_level
  FeedingStockMovement: belongsTo(FeedingStock)

  Data DTOs: OpenFeedingSessionData, RecordFeedingData (session_id, pupil_ids array),
    AddStockData (item_name, unit, quantity, cost_per_unit), RecordStockMovementData

SERVICES + CONTROLLERS (12.3):
  FeedingService: openSession, recordFeeding, finalizeSession,
    getSchoolFeedingStats(int $schoolId, int $termId): array,
    addStock, recordMovement, getLowStockAlerts

  FeedingSessionController, StockController, FeedingReportController

PAGES + TYPES (12.4):
  Pages:
    Feeding/DailyRegister.vue — feeding coordinator: class list, served checkboxes, finalize.
    Feeding/Stock/Index.vue — stock list with low-stock warning badges, restock form.
    Feeding/Reports.vue — term summary: total meals served, per-day chart, stock consumption.

  Tests:
    it('feeding session records served pupils')
    it('low stock alert triggered at reorder level')
```

---

# MODULE 13 — Boarding / Hostel

## Prompt 13.1–13.4 (condensed)

```
MIGRATIONS (13.1):
  dormitories: id, school_id FK → schools.id,
    name varchar(100), gender enum('male','female'),
    capacity int, warden_id nullable FK → users.id,
    description text nullable, timestamps

  beds: id, dormitory_id FK → dormitories.id,
    bed_number varchar(20),
    status enum('available','occupied','maintenance') default 'available',
    timestamps
    UNIQUE(dormitory_id, bed_number)

  boarding_allocations: id, school_id FK → schools.id,
    pupil_id FK → pupils.id, bed_id FK → beds.id,
    term_id FK → terms.id, allocated_date date,
    vacated_date date nullable, fee_amount decimal(8,2),
    status enum('active','vacated','suspended') default 'active', timestamps
    UNIQUE(school_id, pupil_id, term_id)

MODELS + DTOs (13.2):
  Dormitory: belongsTo(School), belongsTo(User,'warden_id'), hasMany(Bed)
    getOccupancyAttribute(): int — count occupied beds
    getAvailableBedsAttribute(): int
  Bed: belongsTo(Dormitory), hasOne active BoardingAllocation
    scopeAvailable($q): where status = 'available'
  BoardingAllocation: belongsTo(School), belongsTo(Pupil), belongsTo(Bed), belongsTo(Term)
    scopeActive($q)

  Data DTOs: CreateDormitoryData, AllocateBedData (pupil_id, bed_id, term_id, fee_amount), VacateBedData (reason nullable)

SERVICES + CONTROLLERS (13.3):
  BoardingService: createDormitory, allocateBed (check capacity + gender match vs pupil sex),
    vacateBed, getDormitoryOccupancy, getTermRoster(int $termId)

  DormitoryController (resource), BedController (resource), AllocationController (invokable store + vacate)

PAGES + TYPES (13.4):
  Pages:
    Boarding/Dormitories/Index.vue — dorm cards: name, gender badge, occupancy bar.
    Boarding/Dormitories/Show.vue — bed grid (available=green, occupied=red, maintenance=gray).
      Click occupied bed → pupil info. Click available → allocate form.
    Boarding/Roster.vue — printable term boarding roster: pupil name, grade, dorm, bed, guardian phone.

  Tests:
    it('male pupil cannot be allocated to female dormitory')
    it('occupied bed cannot be allocated to another pupil')
```

---

# MODULE 14 — Communication

## Prompt 14.1–14.4 (condensed)

```
MIGRATIONS (14.1):
  notices: id, school_id FK → schools.id, title varchar(200), content longtext,
    target_audience enum('all','parents','staff','pupils','grade') default 'all',
    target_grade_id nullable FK → grades.id,
    published_at datetime nullable, expires_at datetime nullable,
    created_by FK → users.id, timestamps

  sms_logs: id, school_id FK → schools.id,
    recipient_phone varchar(25), recipient_name varchar(100) nullable,
    message text, status enum('pending','sent','failed','delivered') default 'pending',
    provider enum('airtel','mtn') nullable,
    external_message_id varchar(100) nullable,
    sent_at datetime nullable, timestamps

  school_messages: id, school_id FK → schools.id,
    sender_id FK → users.id, recipient_id FK → users.id,
    pupil_id nullable FK → pupils.id comment 'context pupil if parent-teacher conversation',
    message text, read_at datetime nullable, timestamps

MODELS + DTOs (14.2):
  Notice: belongsTo(School), belongsTo(User,'created_by'), belongsTo(Grade,'target_grade_id')
    scopePublished($q): whereNotNull('published_at')->where('published_at','<=',now())
    scopeActive($q): published + not expired
    scopeForAudience($q, string $audience)
  SmsLog: belongsTo(School)
    scopeForStatus($q, string $status)
  SchoolMessage: belongsTo(User,'sender_id'), belongsTo(User,'recipient_id'), belongsTo(Pupil)
    scopeThread($q, int $a, int $b)

  Data DTOs:
    CreateNoticeData: title, content, target_audience, target_grade_id nullable, published_at nullable, expires_at nullable
    SendSmsData: /** @var string[] */ phones array, message required, provider nullable in:airtel,mtn
    SendMessageData: recipient_id, pupil_id nullable, message required

SERVICES + CONTROLLERS (14.3):
  CommunicationService:
    createNotice(int $schoolId, CreateNoticeData, int $createdBy): Notice
    publishNotice(int $noticeId): Notice
    getNoticesForUser(int $userId, int $schoolId): Collection
      → filter by role: parent=parent notices, staff=staff notices + all
    sendSms(int $schoolId, SendSmsData): Collection
      → Queue SmsJob per phone number. Job calls Airtel or MTN API based on provider.
      → Create SmsLog record per send attempt.
    sendBulkSmsToGradeParents(int $schoolId, int $gradeId, string $message): int
      → Get primary guardians of all active pupils in grade; extract phones; call sendSms
    sendMessage(SendMessageData, int $senderId): SchoolMessage
    getThread(int $userA, int $userB): Collection

  Airtel Money SMS integration (app/Services/Sms/AirtelSmsService.php):
    send(string $phone, string $message): array  → calls AIRTEL_MONEY_API_URL
  MTN MoMo SMS integration (app/Services/Sms/MtnSmsService.php):
    send(string $phone, string $message): array  → calls MTN_MOMO_API_URL
  SmsServiceFactory: resolves provider → returns correct service

  Create app/Jobs/SendSmsJob.php (implements ShouldQueue):
    Receives phone, message, provider, school_id, sms_log_id.
    Calls SmsServiceFactory; updates SmsLog status on success/failure.

  NoticeController (resource + publish invokable)
  SmsController (invokable store — gate: headteacher|school-admin)
  SchoolMessageController (resource: index, store)

PAGES + TYPES (14.4):
  types/communication.ts: Notice, SmsLog, SchoolMessage

  Pages:
    Communication/Notices/Index.vue — notice board: filter by audience, active badge, expired badge.
    Communication/Notices/Create.vue — rich textarea (no editor library — plain textarea),
      audience selector (shows grade dropdown if 'grade' selected), publish toggle.
    Communication/SMS/Compose.vue — message composer: target selector (grade parents / all parents / custom phones),
      character counter (Zambian SMS = 160 chars), provider selector, send button.
    Communication/SMS/Log.vue — sent SMS log: status badges (sent/failed/delivered), retry button for failed.
    Communication/Messages/Index.vue — message threads list + thread view (parent-teacher).

    Staff view of Notices available in AppLayout notification bell.
    Parent view of Notices shown on Parent/Dashboard.vue.

  Tests:
    it('notice published and visible to correct audience')
    it('parent cannot see staff-only notices')
    it('SMS job queued on bulk send')
    it('SMS log status updated on delivery or failure')
    it('parent can message class teacher')
```

---

# Appendix A — Execution order

| Step | Prompt | Key output |
|------|--------|-----------|
| 1 | 0.1 | Project scaffold, packages, config/zssms.php |
| 2 | 0.2 | Foundation migrations |
| 3 | 0.3 | School model, SetSchoolContext middleware, seeder |
| 4 | 0.4 | AppLayout, ParentLayout, composables, shared types |
| 5 | 1.1–1.4 | Grades, streams, subjects, timetable |
| 6 | 2.1–2.4 | Academic calendar, terms, holidays |
| 7 | 3.1–3.4 | Pupils, guardians, admissions |
| 8 | 4.1–4.4 | Attendance register |
| 9 | 5.1–5.4 | Assessments, results, report cards |
| 10 | 6.1–6.4 | ECZ exam management |
| 11 | 7.1–7.4 | Parent/guardian portal |
| 12 | 8.1–8.4 | Staff & HR (NAPSA + PAYE) |
| 13 | 9.1–9.4 | School finance (Mobile Money) |
| 14 | 10.1–10.4 | Library |
| 15 | 11.1–11.4 | Transport |
| 16 | 12.1–12.4 | School feeding |
| 17 | 13.1–13.4 | Boarding / hostel |
| 18 | 14.1–14.4 | Communication + SMS |

---

# Appendix B — Zambia-specific implementation notes

## NAPSA (National Pension Scheme Authority)
  Employee contribution: 5% of basic salary (capped at statutory ceiling)
  Employer contribution: 5% of basic salary
  Compute in HRService::generatePayroll()
  Reference: NAPSA Act Cap 256 of the Laws of Zambia

## PAYE (Pay As You Earn) — Zambia ZMW tax bands (verify current rates)
  0 – 5,100        → 0%
  5,101 – 7,100    → 25%
  7,101 – 9,200    → 30%
  Above 9,200      → 37.5%
  Implement as PayeCalculator service class.
  Store bands in config/zssms.php so they can be updated without code changes.

## ECZ Division calculation
  Grade 7 (Primary Leaving):
    Add points for best 5 subjects. Distinction/Merit/Credit/Pass/Fail aggregate.
  Grade 9 (Junior Certificate — JCE):
    Points 1–9 per subject. Sum best 7 subjects.
    Div 1: 7–14 | Div 2: 15–21 | Div 3: 22–28 | Div 4: 29–40 | Fail: 41+
  Grade 12 (School Certificate):
    Points 1–9 per subject. Sum best 6 subjects.
    Div 1: 6–12 | Div 2: 13–24 | Div 3: 25–36 | Fail: 37+
  Implement in EczService::computeDivision() using grade_level to select algorithm.

## Mobile Money SMS / Payment providers
  Airtel Money Zambia API: https://openapiportal.airtel.africa
    Auth: OAuth2 client_credentials
    SMS endpoint: POST /standard/v1/users/transactions (adapt for SMS if available)
    Use for fee payment verification (transaction_id confirmation)
  MTN Mobile Money Zambia API: https://momodeveloper.mtn.com
    Subscription Key required, API User/Key for sandbox
  Both providers: implement in app/Services/MobileMoney/ with a contract interface
    MobileMoneyInterface: verifyTransaction(string $transactionId): bool
    AirtelMoneyService implements MobileMoneyInterface
    MtnMomoService implements MobileMoneyInterface

## Admission number format
  Suggested: {SCHOOL_CODE}/{YEAR}/{PADDED_SEQUENCE}
  Example: LSPS/2025/0042 (Lusaka Primary School, 2025, pupil 42)
  School code set in schools.code column.
  Sequence resets per academic year per school.
  Implement in Pupil::generateAdmissionNo(int $schoolId, int $year): string

## Multi-school context
  Single-school mode: set MULTI_SCHOOL=false + SCHOOL_ID=1 in .env
    SetSchoolContext always injects school 1. School switcher hidden.
  Multi-school mode: MULTI_SCHOOL=true
    Each staff user belongs to one school via staff.school_id
    Super-admin can switch via ?school_id= query param (persisted in session)
    All queries automatically scoped by app('current_school')->id

---

# Appendix C — Roles and what they can access

| Role | Key access |
|---|---|
| super-admin | Everything, all schools, school management |
| school-admin | All features within own school |
| headteacher | All academic features, approve leave/payroll, publish results |
| deputy-headteacher | Same as headteacher minus payroll approval |
| class-teacher | Own class register, attendance, CA entry, report comments |
| subject-teacher | Assessments + marks entry for assigned subjects |
| finance-officer | Fee management, expenses, payroll, budgets |
| librarian | Library books, borrowings, overdue reports |
| boarding-master | Dormitory allocations, boarding roster |
| transport-coordinator | Routes, pupil assignments, manifests |
| feeding-coordinator | Feeding sessions, stock management |
| parent | Own children's data only (results, fees, attendance, notices, messages) |
