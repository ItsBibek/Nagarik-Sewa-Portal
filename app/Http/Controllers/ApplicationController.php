<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResubmitApplicationDocumentsRequest;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Department;
use App\Models\ServiceType;
use App\Services\ApplicationStatusService;
use App\Services\ApplicationSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = $request->user()
            ->applications()
            ->with(['serviceType.department'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('applications.index', [
            'applications' => $applications,
        ]);
    }

    public function create(Request $request): View
    {
        $departments = Department::query()
            ->whereHas('serviceTypes', fn ($query) => $query->where('is_active', true))
            ->with([
                'serviceTypes' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        $selectedServiceTypeId = $request->query('service_type_id')
            ?? $departments->flatMap(fn (Department $department) => $department->serviceTypes)->first()?->id;

        return view('applications.create', [
            'departments' => $departments,
            'documentRequirements' => $departments
                ->flatMap(fn (Department $department) => $department->serviceTypes)
                ->mapWithKeys(fn (ServiceType $serviceType) => [
                    $serviceType->id => array_values($serviceType->required_documents ?? []),
                ])
                ->all(),
            'serviceDetails' => $departments
                ->flatMap(fn (Department $department) => $department->serviceTypes)
                ->mapWithKeys(fn (ServiceType $serviceType) => [
                    $serviceType->id => [
                        'fee' => (float) $serviceType->fee,
                        'processing_days' => $serviceType->processing_days,
                    ],
                ])
                ->all(),
            'selectedServiceTypeId' => is_numeric($selectedServiceTypeId)
                ? (int) $selectedServiceTypeId
                : null,
        ]);
    }

    public function store(
        StoreApplicationRequest $request,
        ApplicationSubmissionService $submissionService,
    ): RedirectResponse {
        $validated = $request->validated();
        $serviceType = ServiceType::query()
            ->where('is_active', true)
            ->findOrFail($validated['service_type_id']);

        $application = $submissionService->submit(
            $request->user(),
            $serviceType,
            $request->file('documents', []),
        );

        return redirect()->route('applications.track', [
            'tracking_id' => $application->tracking_id,
        ]);
    }

    public function track(string $trackingId): View
    {
        $application = Application::query()
            ->where('tracking_id', $trackingId)
            ->with([
                'serviceType.department',
                'statusHistories' => fn ($query) => $query
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->firstOrFail();

        return view('applications.track', [
            'application' => $application,
        ]);
    }

    public function editCorrections(Application $application): View
    {
        Gate::authorize('resubmit', $application);

        return view('applications.corrections', [
            'application' => $application->load('serviceType'),
            'requiredDocuments' => array_values(
                $application->serviceType->required_documents ?? [],
            ),
            'correctionRemark' => $application->statusHistories()
                ->where('new_status', 'correction_required')
                ->latest('id')
                ->value('remark'),
        ]);
    }

    public function resubmit(
        ResubmitApplicationDocumentsRequest $request,
        Application $application,
        ApplicationStatusService $statusService,
    ): RedirectResponse {
        $statusService->resubmit(
            $request->user(),
            $application,
            $request->file('documents', []),
        );

        return redirect()
            ->route('applications.index')
            ->with('status', 'Replacement documents submitted for review.');
    }

    public function downloadDocument(
        Application $application,
        ApplicationDocument $document,
    ): StreamedResponse {
        Gate::authorize('view', $application);

        abort_unless(
            $application->documents()->whereKey($document->getKey())->exists()
                && Storage::disk('local')->exists($document->file_path),
            404,
        );

        return Storage::disk('local')->download(
            $document->file_path,
            basename($document->file_path),
        );
    }
}
