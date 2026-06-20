<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AllocationController;
use App\Http\Controllers\ApproveLeaveController;
use App\Http\Controllers\ApprovePayrollController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentScoreController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ComputeCAMarksController;
use App\Http\Controllers\DormitoryController;
use App\Http\Controllers\EczCandidateController;
use App\Http\Controllers\EczPassRateController;
use App\Http\Controllers\EczResultEntryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeedingReportController;
use App\Http\Controllers\FeedingSessionController;
use App\Http\Controllers\FeeInvoiceController;
use App\Http\Controllers\FeePaymentController;
use App\Http\Controllers\FeeReportController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\GeneratePayrollController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GuardianAccountController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LibraryBookController;
use App\Http\Controllers\LinkSubjectController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\OverdueReportController;
use App\Http\Controllers\ParentMessageController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PortalNotificationController;
use App\Http\Controllers\PublishNoticeController;
use App\Http\Controllers\PublishReportCardsController;
use App\Http\Controllers\PublishResultsController;
use App\Http\Controllers\PupilController;
use App\Http\Controllers\PupilPromotionController;
use App\Http\Controllers\PupilStatisticsController;
use App\Http\Controllers\PupilTransferController;
use App\Http\Controllers\PupilTransportController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\RouteManifestController;
use App\Http\Controllers\SchoolAttendanceSummaryController;
use App\Http\Controllers\SchoolMessageController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\TermResultController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TransportRouteController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WaiveInvoiceController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

// Module 1 — School & Class Structure
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('grades', GradeController::class)->except(['show', 'create']);
    Route::resource('streams', StreamController::class)->except(['create']);
    Route::resource('subjects', SubjectController::class)->except(['show', 'create']);
    Route::post('grades/{grade}/subjects', LinkSubjectController::class)
        ->name('grades.subjects.link');
    Route::resource('timetable', TimetableController::class)
        ->only(['index', 'store', 'destroy']);
});

// Module 2 — Academic Calendar
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('academic-years', AcademicYearController::class)
        ->except(['show', 'create', 'edit']);
    Route::resource('terms', TermController::class)
        ->except(['show', 'create', 'edit']);
    Route::post('holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('holidays/{schoolHoliday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
});

// Module 3 — Pupil Management & Admissions
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('pupils', PupilController::class);
    Route::post('pupils/{pupil}/guardians', [GuardianController::class, 'store'])
        ->name('pupils.guardians.store');
    Route::delete('pupils/{pupil}/guardians/{guardian}', [GuardianController::class, 'destroy'])
        ->name('pupils.guardians.destroy');
    Route::post('pupils/{pupil}/transfer', PupilTransferController::class)
        ->name('pupils.transfer');
    Route::post('streams/{stream}/promote', PupilPromotionController::class)
        ->name('streams.promote')
        ->middleware('role:headteacher|school-admin');
    Route::get('school/statistics', PupilStatisticsController::class)
        ->name('school.statistics');
});

// Module 5 — Assessments & Results
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('assessments', AssessmentController::class)->except(['create', 'edit', 'update']);
    Route::post('assessments/{assessment}/scores', AssessmentScoreController::class)
        ->name('assessments.scores.enter');
    Route::post('term-results/compute-ca', ComputeCAMarksController::class)->name('term-results.compute-ca');
    Route::post('term-results/publish', PublishResultsController::class)->name('term-results.publish');
    Route::get('term-results', [TermResultController::class, 'index'])->name('term-results.index');
    Route::post('term-results', [TermResultController::class, 'store'])->name('term-results.store');
    Route::post('report-cards/publish', PublishReportCardsController::class)->name('report-cards.publish');
    Route::resource('report-cards', ReportCardController::class)->only(['index', 'show', 'store', 'update']);
});

// Module 6 — ECZ Exam Management
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::post('ecz/results', EczResultEntryController::class)->name('ecz.results.enter');
    Route::get('ecz/analytics', EczPassRateController::class)->name('ecz.analytics');
    Route::resource('ecz-candidates', EczCandidateController::class)->except(['create', 'edit']);
});

// Module 8 — Staff & HR
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('staff', StaffController::class)->except(['create', 'edit']);
    Route::get('leaves/apply', [LeaveController::class, 'create'])->name('leaves.apply');
    Route::get('leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::post('leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('leaves/{leave}/approve', ApproveLeaveController::class)->name('leaves.approve');
    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('payroll/generate', GeneratePayrollController::class)->name('payroll.generate');
    Route::post('payroll/{payroll}/approve', ApprovePayrollController::class)->name('payroll.approve');
});

// Module 7 — Parent / Guardian Portal
Route::middleware(['auth', 'verified', 'role:parent'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('children/{pupil}', [PortalController::class, 'childDetail'])->name('child.detail');
    Route::get('messages', [ParentMessageController::class, 'index'])->name('messages.index');
    Route::post('messages', ParentMessageController::class)->name('messages.send');
    Route::get('notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{id}/read', [PortalNotificationController::class, 'markRead'])->name('notifications.read');
});

// Module 7 — Guardian Account Management (admin/staff)
Route::middleware(['auth', 'verified', 'school.context', 'role:headteacher|school-admin'])->group(function () {
    Route::post('guardians/portal-account', [GuardianAccountController::class, 'store'])->name('guardians.portal-account.store');
    Route::post('guardians/{guardian}/resend-credentials', [GuardianAccountController::class, 'resendCredentials'])->name('guardians.portal-account.resend');
});

// Module 13 — Boarding / Hostel
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('dormitories', DormitoryController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('dormitories/{dormitory}/beds', [BedController::class, 'store'])->name('dormitories.beds.store');
    Route::patch('beds/{bed}', [BedController::class, 'update'])->name('beds.update');
    Route::delete('beds/{bed}', [BedController::class, 'destroy'])->name('beds.destroy');
    Route::get('boarding/roster', AllocationController::class)->name('boarding.roster');
    Route::post('boarding/allocations', [AllocationController::class, 'store'])->name('allocations.store');
    Route::post('boarding/allocations/{allocation}/vacate', [AllocationController::class, 'vacate'])->name('allocations.vacate');
});

// Module 12 — School Feeding Programme
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::get('feeding', [FeedingSessionController::class, 'index'])->name('feeding-sessions.index');
    Route::post('feeding', [FeedingSessionController::class, 'store'])->name('feeding-sessions.store');
    Route::get('feeding/{feedingSession}', [FeedingSessionController::class, 'show'])->name('feeding-sessions.show');
    Route::patch('feeding/{feedingSession}', [FeedingSessionController::class, 'update'])->name('feeding-sessions.update');
    Route::post('feeding/{feedingSession}/finalize', [FeedingSessionController::class, 'destroy'])->name('feeding-sessions.finalize');
    Route::get('feeding-stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('feeding-stock', [StockController::class, 'store'])->name('stock.store');
    Route::patch('feeding-stock/{feedingStock}', [StockController::class, 'update'])->name('stock.update');
    Route::get('feeding-reports', FeedingReportController::class)->name('feeding.reports');
});

// Module 11 — Transport
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('transport-routes', TransportRouteController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('pupil-transport', [PupilTransportController::class, 'store'])->name('pupil-transport.store');
    Route::delete('pupil-transport/{pupilTransport}', [PupilTransportController::class, 'destroy'])->name('pupil-transport.destroy');
    Route::get('transport-routes/{transportRoute}/manifest', RouteManifestController::class)->name('route-manifest.show');
});

// Module 10 — Library
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('library-books', LibraryBookController::class)->except(['create', 'edit', 'update']);
    Route::get('borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('borrowings/create', [BorrowingController::class, 'create'])->name('borrowings.create');
    Route::post('borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');
    Route::patch('borrowings/{borrowing}', [BorrowingController::class, 'update'])->name('borrowings.update');
    Route::get('library/overdue', OverdueReportController::class)->name('library.overdue');
});

// Module 9 — School Finance
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('fee-structures', FeeStructureController::class)->only(['index', 'store', 'destroy']);
    Route::get('fee-invoices', [FeeInvoiceController::class, 'index'])->name('fee-invoices.index');
    Route::post('fee-invoices', [FeeInvoiceController::class, 'store'])->name('fee-invoices.store');
    Route::get('fee-invoices/{feeInvoice}', [FeeInvoiceController::class, 'show'])->name('fee-invoices.show');
    Route::post('fee-invoices/bulk-raise', [FeeInvoiceController::class, 'bulkRaise'])->name('fee-invoices.bulk-raise');
    Route::post('fee-invoices/{feeInvoice}/waive', WaiveInvoiceController::class)->name('fee-invoices.waive');
    Route::post('fee-payments', FeePaymentController::class)->name('fee-payments.store');
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);
    Route::resource('budgets', BudgetController::class)->only(['index', 'store', 'destroy']);
    Route::get('finance/reports', FeeReportController::class)->name('finance.reports');
});

// Module 14 — Communication
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::resource('notices', NoticeController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::post('notices/{notice}/publish', PublishNoticeController::class)->name('notices.publish');
    Route::get('sms/compose', [SmsController::class, 'compose'])->name('sms.compose');
    Route::post('sms/send', [SmsController::class, 'send'])->name('sms.send');
    Route::get('sms/log', [SmsController::class, 'log'])->name('sms.log');
    Route::get('messages', [SchoolMessageController::class, 'index'])->name('messages.index');
    Route::post('messages', [SchoolMessageController::class, 'store'])->name('messages.store');
    Route::get('messages/{user}/thread', [SchoolMessageController::class, 'thread'])->name('messages.thread');
});

// Role Management + Admin
Route::middleware(['auth', 'verified', 'can:settings.manage'])->group(function () {
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

Route::middleware(['auth', 'verified', 'school.context', 'can:school.view'])->group(function () {
    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::put('schools', [SchoolController::class, 'update'])->name('schools.update');
});

// Module 4 — Attendance
Route::middleware(['auth', 'verified', 'school.context'])->group(function () {
    Route::get('attendance/report', AttendanceReportController::class)->name('attendance.report');
    Route::get('attendance/school-summary', SchoolAttendanceSummaryController::class)->name('attendance.school-summary');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::put('attendance/{attendanceSession}', [AttendanceController::class, 'update'])->name('attendance.update');
});
