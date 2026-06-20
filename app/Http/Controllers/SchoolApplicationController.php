<?php

namespace App\Http\Controllers;

use App\Data\SubmitSchoolApplicationData;
use App\Enums\ApplicationStatusEnum;
use App\Models\SchoolApplication;
use App\Services\SchoolApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SchoolApplicationController extends Controller
{
    public function __construct(private readonly SchoolApplicationService $service) {}

    public function create(Request $request): Response|RedirectResponse
    {
        // If user already has a pending/needs_info application, redirect to it
        $existing = $request->user()
            ->schoolApplications()
            ->whereIn('status', [ApplicationStatusEnum::Pending, ApplicationStatusEnum::NeedsInfo])
            ->latest()
            ->first();

        if ($existing) {
            return to_route('onboarding.show', $existing);
        }

        return Inertia::render('Onboarding/Apply', [
            'provinces' => config('zssms.zambia_provinces'),
            'modules'   => $this->availableModules(),
        ]);
    }

    public function store(SubmitSchoolApplicationData $data, Request $request): RedirectResponse
    {
        $request->validate([
            'subdomain' => ['required', Rule::unique('school_applications', 'subdomain'), Rule::unique('schools', 'subdomain')],
        ]);

        $application = SchoolApplication::make($data->toArray());
        $application->applicant_id = $request->user()->id;

        $this->service->submit($application, $request->user());

        return to_route('onboarding.show', $application)
            ->with('success', 'Application submitted! We will review it and get back to you.');
    }

    public function show(SchoolApplication $application, Request $request): Response
    {
        abort_unless($application->applicant_id === $request->user()->id, 403);

        return Inertia::render('Onboarding/Status', [
            'application' => $application->load('logs.actor'),
        ]);
    }

    public function edit(SchoolApplication $application, Request $request): Response|RedirectResponse
    {
        abort_unless($application->applicant_id === $request->user()->id, 403);
        abort_unless($application->isNeedsInfo(), 403, 'This application cannot be edited.');

        return Inertia::render('Onboarding/Apply', [
            'application' => $application,
            'provinces'   => config('zssms.zambia_provinces'),
            'modules'     => $this->availableModules(),
        ]);
    }

    public function update(SubmitSchoolApplicationData $data, SchoolApplication $application, Request $request): RedirectResponse
    {
        abort_unless($application->applicant_id === $request->user()->id, 403);
        abort_unless($application->isNeedsInfo(), 403, 'This application cannot be edited.');

        $request->validate([
            'subdomain' => [
                'required',
                Rule::unique('school_applications', 'subdomain')->ignore($application->id),
                Rule::unique('schools', 'subdomain'),
            ],
        ]);

        $application->fill($data->toArray());

        $this->service->submit($application, $request->user());

        return to_route('onboarding.show', $application)
            ->with('success', 'Application re-submitted successfully.');
    }

    /** @return array<string, string> */
    private function availableModules(): array
    {
        return [
            'finance'       => 'Finance (Fees, Budgets, Expenses)',
            'hr'            => 'Human Resources (Staff, Payroll, Leave)',
            'library'       => 'Library Management',
            'transport'     => 'Transport Management',
            'feeding'       => 'Feeding Programme',
            'boarding'      => 'Boarding / Dormitories',
            'communication' => 'Communication (SMS, Notices)',
            'ecz'           => 'ECZ Examinations',
        ];
    }
}
