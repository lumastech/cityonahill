<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Bed;
use App\Models\BookBorrowing;
use App\Models\FeeInvoice;
use App\Models\FeedingSession;
use App\Models\Notice;
use App\Models\Pupil;
use App\Models\PupilTransport;
use App\Models\School;
use App\Models\Staff;
use App\Models\Stream;
use App\Models\Term;
use App\Models\TransportRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user   = $request->user();
        $school = app('current_school');
        $role   = $user->getRoleNames()->first() ?? '';

        // Fresh install: no branches exist yet — send the admin to the setup wizard.
        if ($school === null && $user->hasRole('super-admin') && ! School::exists()) {
            return redirect()->route('setup.create');
        }

        $data = match (true) {
            // No school context (e.g. a user not yet assigned to a branch)
            // — every stats builder needs a school.
            $school === null => ['type' => 'default', 'stats' => []],
            in_array($role, ['super-admin', 'school-admin', 'headteacher', 'deputy-headteacher'])
                => $this->adminStats($school, $user),
            $role === 'class-teacher'          => $this->classTeacherStats($school, $user),
            $role === 'subject-teacher'        => $this->subjectTeacherStats($school, $user),
            $role === 'finance-officer'        => $this->financeStats($school),
            $role === 'librarian'              => $this->librarianStats($school),
            $role === 'boarding-master'        => $this->boardingStats($school),
            $role === 'transport-coordinator'  => $this->transportStats($school),
            $role === 'feeding-coordinator'    => $this->feedingStats($school),
            default                            => ['type' => 'default', 'stats' => []],
        };

        return Inertia::render('Dashboard', $data);
    }

    private function recentNotices(int $schoolId, array $audiences = ['all']): \Illuminate\Database\Eloquent\Collection
    {
        return Notice::where('school_id', $schoolId)
            ->active()
            ->whereIn('target_audience', $audiences)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get(['id', 'title', 'target_audience', 'published_at']);
    }

    private function adminStats(mixed $school, mixed $user): array
    {
        $schoolId = $school->id;

        $pupils = Pupil::where('school_id', $schoolId);
        $totalActive = (clone $pupils)->where('status', 'active')->count();
        $totalMale   = (clone $pupils)->where('status', 'active')->where('sex', 'male')->count();
        $totalFemale = (clone $pupils)->where('status', 'active')->where('sex', 'female')->count();

        $totalStaff = Staff::where('school_id', $schoolId)->where('status', 'active')->count();

        // Today's attendance
        $todayDate = now()->toDateString();
        $todaySessions = AttendanceSession::where('school_id', $schoolId)
            ->whereDate('date', $todayDate)
            ->withCount([
                'records as present_count' => fn ($q) => $q->where('status', 'present'),
                'records as absent_count'  => fn ($q) => $q->where('status', 'absent'),
            ])
            ->get();

        $presentTotal = $todaySessions->sum('present_count');
        $absentTotal  = $todaySessions->sum('absent_count');
        $attendanceTotal = $presentTotal + $absentTotal;
        $attendancePct = $attendanceTotal > 0
            ? round(($presentTotal / $attendanceTotal) * 100)
            : null;

        // Finance snapshot
        $outstandingAmount = FeeInvoice::where('school_id', $schoolId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('balance_due');

        $outstandingCount = FeeInvoice::where('school_id', $schoolId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->count();

        // Active assessments needing scores
        $pendingAssessments = Assessment::where('school_id', $schoolId)
            ->whereDate('date', '<=', $todayDate)
            ->whereDoesntHave('scores')
            ->count();

        // Recent notices
        $notices = $this->recentNotices($schoolId, ['all', 'staff', 'parents']);

        // Current term
        $currentTerm = Term::where('school_id', $schoolId)
            ->current()
            ->first(['id', 'name', 'start_date', 'end_date', 'number']);

        // Grade distribution
        $byGrade = Pupil::where('school_id', $schoolId)
            ->where('status', 'active')
            ->select('grade_id', DB::raw('count(*) as count'))
            ->with('grade:id,name,grade_number')
            ->groupBy('grade_id')
            ->get();

        return [
            'type'  => 'admin',
            'stats' => [
                'pupils' => [
                    'active' => $totalActive,
                    'male'   => $totalMale,
                    'female' => $totalFemale,
                ],
                'staff'             => $totalStaff,
                'attendance'        => [
                    'present'    => $presentTotal,
                    'absent'     => $absentTotal,
                    'total'      => $attendanceTotal,
                    'percentage' => $attendancePct,
                    'sessions'   => $todaySessions->count(),
                ],
                'finance'           => [
                    'outstanding_amount' => round($outstandingAmount, 2),
                    'outstanding_count'  => $outstandingCount,
                ],
                'pending_assessments' => $pendingAssessments,
                'notices'           => $notices,
                'current_term'      => $currentTerm,
                'by_grade'          => $byGrade,
            ],
        ];
    }

    private function classTeacherStats(mixed $school, mixed $user): array
    {
        $schoolId = $school->id;

        $stream = Stream::where('school_id', $schoolId)
            ->where('class_teacher_id', $user->id)
            ->with('grade:id,name,grade_number')
            ->first();

        $pupils = $stream
            ? Pupil::where('stream_id', $stream->id)->where('status', 'active')->count()
            : 0;

        // Today's attendance for their stream
        $todayDate = now()->toDateString();
        $todaySession = $stream
            ? AttendanceSession::where('stream_id', $stream->id)
                ->whereDate('date', $todayDate)
                ->withCount([
                    'records as present_count' => fn ($q) => $q->where('status', 'present'),
                    'records as absent_count'  => fn ($q) => $q->where('status', 'absent'),
                ])
                ->first()
            : null;

        // Upcoming assessments for their stream
        $assessments = $stream
            ? Assessment::where('stream_id', $stream->id)
                ->where('school_id', $schoolId)
                ->with('subject:id,name')
                ->orderByDesc('date')
                ->limit(5)
                ->get(['id', 'name', 'type', 'date', 'max_marks', 'subject_id'])
            : collect();

        // Notices
        $notices = $this->recentNotices($schoolId, ['all', 'staff']);

        return [
            'type'  => 'class_teacher',
            'stats' => [
                'stream'      => $stream,
                'pupil_count' => $pupils,
                'attendance'  => $todaySession ? [
                    'present'    => $todaySession->present_count,
                    'absent'     => $todaySession->absent_count,
                    'total'      => $todaySession->present_count + $todaySession->absent_count,
                    'recorded'   => true,
                ] : ['recorded' => false, 'present' => 0, 'absent' => 0, 'total' => 0],
                'assessments' => $assessments,
                'notices'     => $notices,
            ],
        ];
    }

    private function subjectTeacherStats(mixed $school, mixed $user): array
    {
        $schoolId = $school->id;

        $myAssessments = Assessment::where('school_id', $schoolId)
            ->where('created_by', $user->id)
            ->with('subject:id,name', 'stream:id,name')
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'name', 'type', 'date', 'max_marks', 'subject_id', 'stream_id']);

        $pendingScoring = Assessment::where('school_id', $schoolId)
            ->where('created_by', $user->id)
            ->whereDate('date', '<=', now()->toDateString())
            ->whereDoesntHave('scores')
            ->count();

        $streams = Stream::where('school_id', $schoolId)
            ->with('grade:id,name')
            ->get(['id', 'name', 'grade_id']);

        return [
            'type'  => 'subject_teacher',
            'stats' => [
                'my_assessments'  => $myAssessments,
                'pending_scoring' => $pendingScoring,
                'streams'         => $streams,
                'notices'         => $this->recentNotices($schoolId, ['all', 'staff']),
            ],
        ];
    }

    private function financeStats(mixed $school): array
    {
        $schoolId = $school->id;

        $outstanding = FeeInvoice::where('school_id', $schoolId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('count(*) as count, sum(balance_due) as total')
            ->first();

        $collectedThisMonth = DB::table('fee_payments')
            ->where('school_id', $schoolId)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->where(fn ($q) => $q->whereNull('gateway_status')->orWhere('gateway_status', 'completed'))
            ->sum('amount');

        $expensesThisMonth = DB::table('expenses')
            ->where('school_id', $schoolId)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $recentPayments = DB::table('fee_payments')
            ->join('fee_invoices', 'fee_payments.invoice_id', '=', 'fee_invoices.id')
            ->join('pupils', 'fee_invoices.pupil_id', '=', 'pupils.id')
            ->where('fee_payments.school_id', $schoolId)
            ->where(fn ($q) => $q->whereNull('fee_payments.gateway_status')->orWhere('fee_payments.gateway_status', 'completed'))
            ->orderByDesc('fee_payments.payment_date')
            ->limit(5)
            ->get(['fee_payments.amount', 'fee_payments.payment_date', 'fee_payments.payment_method',
                   DB::raw("concat_ws(' ', pupils.first_name, pupils.last_name) as pupil_name")]);

        return [
            'type'  => 'finance',
            'stats' => [
                'outstanding_count'       => $outstanding->count ?? 0,
                'outstanding_amount'      => round($outstanding->total ?? 0, 2),
                'collected_this_month'    => round($collectedThisMonth, 2),
                'expenses_this_month'     => round($expensesThisMonth, 2),
                'recent_payments'         => $recentPayments,
                'notices'                 => $this->recentNotices($schoolId, ['all', 'staff']),
            ],
        ];
    }

    private function librarianStats(mixed $school): array
    {
        $schoolId = $school->id;

        $totalBooks    = DB::table('library_books')->where('school_id', $schoolId)->count();
        $activeBorrow  = BookBorrowing::where('school_id', $schoolId)->where('status', 'borrowed')->count();
        $overdue       = BookBorrowing::where('school_id', $schoolId)->overdue()->count();

        $recentBorrowings = BookBorrowing::where('school_id', $schoolId)
            ->with('book:id,title', 'issuedBy:id,name')
            ->orderByDesc('borrowed_date')
            ->limit(5)
            ->get(['id', 'book_id', 'borrower_type', 'borrower_id', 'borrowed_date', 'due_date', 'status', 'issued_by']);

        return [
            'type'  => 'librarian',
            'stats' => [
                'total_books'      => $totalBooks,
                'active_borrowings'=> $activeBorrow,
                'overdue'          => $overdue,
                'recent_borrowings'=> $recentBorrowings,
                'notices'          => $this->recentNotices($schoolId, ['all', 'staff']),
            ],
        ];
    }

    private function boardingStats(mixed $school): array
    {
        $schoolId = $school->id;

        $bedQuery = fn () => Bed::whereHas('dormitory', fn ($q) => $q->where('school_id', $schoolId));

        $totalBeds     = $bedQuery()->count();
        $occupiedBeds  = $bedQuery()->where('status', 'occupied')->count();
        $availableBeds = $bedQuery()->where('status', 'available')->count();

        return [
            'type'  => 'boarding',
            'stats' => [
                'total_beds'    => $totalBeds,
                'occupied_beds' => $occupiedBeds,
                'available_beds'=> $availableBeds,
                'occupancy_pct' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0,
                'notices'       => $this->recentNotices($schoolId, ['all', 'staff']),
            ],
        ];
    }

    private function transportStats(mixed $school): array
    {
        $schoolId = $school->id;

        $routes         = TransportRoute::where('school_id', $schoolId)->count();
        $assignedPupils = PupilTransport::where('school_id', $schoolId)->where('status', 'active')->count();

        return [
            'type'  => 'transport',
            'stats' => [
                'routes'          => $routes,
                'assigned_pupils' => $assignedPupils,
                'notices'         => $this->recentNotices($schoolId, ['all', 'staff']),
            ],
        ];
    }

    private function feedingStats(mixed $school): array
    {
        $schoolId = $school->id;

        $todaySession = FeedingSession::where('school_id', $schoolId)
            ->whereDate('date', now()->toDateString())
            ->first();

        $lowStock = DB::table('feeding_stock')
            ->where('school_id', $schoolId)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->get(['id', 'item_name', 'quantity_on_hand', 'unit']);

        return [
            'type'  => 'feeding',
            'stats' => [
                'today_session' => $todaySession,
                'low_stock'     => $lowStock,
                'notices'       => $this->recentNotices($schoolId, ['all', 'staff']),
            ],
        ];
    }
}
