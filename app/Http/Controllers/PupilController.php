<?php

namespace App\Http\Controllers;

use App\Data\AdmitPupilData;
use App\Data\UpdatePupilData;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Grade;
use App\Models\Pupil;
use App\Models\Stream;
use App\Services\PupilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PupilController extends Controller
{
    public function __construct(private readonly PupilService $pupilService) {}

    public function index(Request $request): Response
    {
        $school = app('current_school');

        $pupils = Pupil::where('school_id', $school->id)
            ->with(['grade:id,name', 'stream:id,name'])
            ->when($request->grade_id, fn ($q) => $q->where('grade_id', $request->grade_id))
            ->when($request->stream_id, fn ($q) => $q->where('stream_id', $request->stream_id))
            ->when($request->sex, fn ($q) => $q->where('sex', $request->sex))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('admission_no', 'like', "%{$request->search}%");
            }))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Pupils/Index', [
            'pupils'  => $pupils,
            'filters' => $request->only(['grade_id', 'stream_id', 'sex', 'status', 'search']),
            'grades'  => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name']),
            'streams' => Stream::where('school_id', $school->id)
                ->with('grade:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'grade_id']),
            'stats'   => Inertia::defer(fn () => $this->pupilService->getSchoolStatistics($school->id)),
        ]);
    }

    public function create(): Response
    {
        $school = app('current_school');

        return Inertia::render('Pupils/Create', [
            'grades' => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name', 'grade_number']),
            'streams' => Stream::where('school_id', $school->id)->get(['id', 'name', 'grade_id']),
            'academicYear' => AcademicYear::where('school_id', $school->id)->where('is_current', 1)->first(),
        ]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $school = app('current_school');
        $q = $request->string('q')->trim();

        $gender = $request->string('gender')->toString() ?: null;

        $pupils = Pupil::where('school_id', $school->id)
            ->where('status', 'active')
            ->when($gender, fn ($query) => $query->where('sex', $gender))
            ->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('admission_no', 'like', "%{$q}%");
            })
            ->orderBy('last_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        return response()->json($pupils);
    }

    public function store(AdmitPupilData $data): RedirectResponse
    {
        $this->authorize('pupil.create');

        $school = app('current_school');

        $pupil = $this->pupilService->admit($school->id, $data);

        return redirect()->route('pupils.show', $pupil)
            ->with('success', "Pupil {$pupil->full_name} admitted with number {$pupil->admission_no}.");
    }

    public function show(Pupil $pupil): Response
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $pupil->load([
            'grade',
            'stream',
            'academicYear',
            'guardians',
            'transfers',
        ]);

        $invoices = FeeInvoice::where('pupil_id', $pupil->id)
            ->with('term:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'term_id', 'notes', 'amount', 'discount', 'balance_due', 'status', 'due_date', 'created_at']);

        $recentPayments = FeePayment::where('pupil_id', $pupil->id)
            ->where(function ($q) {
                $q->whereNull('gateway_status')->orWhere('gateway_status', 'completed');
            })
            ->with('invoice:id,term_id', 'invoice.term:id,name')
            ->orderByDesc('payment_date')
            ->limit(10)
            ->get(['id', 'invoice_id', 'amount', 'payment_method', 'payment_date', 'received_by']);

        $attendanceRecords = AttendanceRecord::where('pupil_id', $pupil->id)
            ->with('attendanceSession:id,date')
            ->get(['id', 'session_id', 'status'])
            ->map(fn ($r) => [
                'date'   => $r->attendanceSession?->date?->format('Y-m-d'),
                'status' => $r->status,
            ])
            ->filter(fn ($r) => $r['date'] !== null)
            ->values();

        return Inertia::render('Pupils/Show', [
            'pupil'             => $pupil,
            'invoices'          => $invoices,
            'recentPayments'    => $recentPayments,
            'attendanceRecords' => $attendanceRecords,
            'termResults'       => [],
            'streams'           => Stream::where('school_id', $pupil->school_id)
                ->with('grade:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'grade_id']),
        ]);
    }

    public function edit(Pupil $pupil): Response
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $school = app('current_school');

        return Inertia::render('Pupils/Edit', [
            'pupil'          => $pupil,
            'grades'         => Grade::where('school_id', $school->id)->orderBy('grade_number')->get(['id', 'name', 'grade_number']),
            'streams'        => Stream::where('school_id', $school->id)->with('grade:id,name')->orderBy('name')->get(['id', 'name', 'grade_id']),
            'academic_years' => AcademicYear::where('school_id', $school->id)->orderByDesc('start_date')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePupilData $data, Pupil $pupil): RedirectResponse
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        $pupil->update(array_filter($data->toArray(), fn ($v) => $v !== null));

        return redirect()->route('pupils.show', $pupil)
            ->with('success', 'Pupil record updated.');
    }

    public function destroy(Pupil $pupil): RedirectResponse
    {
        abort_if($pupil->school_id !== app('current_school')?->id, 403);

        if ($pupil->termResults()->exists()) {
            $this->pupilService->withdraw($pupil->id, 'Record deletion requested — withdrawn instead.');

            return redirect()->route('pupils.index')
                ->with('info', 'Pupil has academic records and cannot be deleted. Status set to withdrawn.');
        }

        $pupil->delete();

        return redirect()->route('pupils.index')
            ->with('success', 'Pupil record deleted.');
    }
}
