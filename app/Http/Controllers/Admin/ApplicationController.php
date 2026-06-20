<?php

namespace App\Http\Controllers\Admin;

use App\Data\ReviewApplicationData;
use App\Enums\ApplicationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\SchoolApplication;
use App\Services\SchoolApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function __construct(private readonly SchoolApplicationService $service) {}

    public function index(): Response
    {
        $applications = SchoolApplication::with('applicant:id,name,email')
            ->latest()
            ->paginate(20);

        $counts = [
            'pending'    => SchoolApplication::where('status', ApplicationStatusEnum::Pending)->count(),
            'needs_info' => SchoolApplication::where('status', ApplicationStatusEnum::NeedsInfo)->count(),
            'approved'   => SchoolApplication::where('status', ApplicationStatusEnum::Approved)->count(),
            'rejected'   => SchoolApplication::where('status', ApplicationStatusEnum::Rejected)->count(),
        ];

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
            'counts'       => $counts,
        ]);
    }

    public function show(SchoolApplication $application): Response
    {
        return Inertia::render('Admin/Applications/Show', [
            'application' => $application->load(['applicant:id,name,email,phone', 'logs.actor:id,name']),
        ]);
    }

    public function approve(SchoolApplication $application, Request $request): RedirectResponse
    {
        abort_unless($application->isPending(), 422, 'Only pending applications can be approved.');

        $this->service->approve($application, $request->user());

        return to_route('admin.applications.index')
            ->with('success', "School \"{$application->school_name}\" has been approved and created.");
    }

    public function needsInfo(ReviewApplicationData $data, SchoolApplication $application, Request $request): RedirectResponse
    {
        $this->service->needsInfo($application, $request->user(), $data->reviewer_notes);

        return to_route('admin.applications.show', $application)
            ->with('success', 'Applicant has been notified to provide more information.');
    }

    public function reject(ReviewApplicationData $data, SchoolApplication $application, Request $request): RedirectResponse
    {
        $this->service->reject($application, $request->user(), $data->reviewer_notes);

        return to_route('admin.applications.index')
            ->with('success', "Application for \"{$application->school_name}\" has been rejected.");
    }
}
