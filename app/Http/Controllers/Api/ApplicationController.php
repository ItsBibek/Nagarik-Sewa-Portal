<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResubmitApplicationDocumentsRequest;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationCollection;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\ServiceType;
use App\Services\ApplicationStatusService;
use App\Services\ApplicationSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request): ApplicationCollection
    {
        Gate::authorize('viewAny', Application::class);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ApplicationStatus::class)],
        ]);
        $user = $request->user();

        $query = match ($user->role) {
            'citizen' => $user->applications()->getQuery(),
            'officer' => Application::query()
                ->whereHas('serviceType', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('department_id', $user->department_id)),
            'admin' => Application::query(),
            default => Application::query()->whereRaw('1 = 0'),
        };

        $applications = $query
            ->with([
                'serviceType.department',
                'documents',
                'statusHistories' => fn ($query) => $query
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('status', $validated['status']),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return (new ApplicationCollection($applications))
            ->additional(['message' => 'Applications retrieved.']);
    }

    public function store(
        StoreApplicationRequest $request,
        ApplicationSubmissionService $submissionService,
    ): JsonResponse {
        $validated = $request->validated();
        $serviceType = ServiceType::query()
            ->where('is_active', true)
            ->with('department')
            ->findOrFail($validated['service_type_id']);

        $application = $submissionService->submit(
            $request->user(),
            $serviceType,
            $request->file('documents', []),
        )->load([
            'serviceType.department',
            'documents',
            'statusHistories',
        ]);

        return (new ApplicationResource($application))
            ->additional(['message' => 'Application submitted.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Application $application): ApplicationResource
    {
        Gate::authorize('view', $application);

        return (new ApplicationResource($application->load([
            'serviceType.department',
            'documents',
            'statusHistories' => fn ($query) => $query
                ->orderBy('created_at')
                ->orderBy('id'),
        ])))->additional(['message' => 'Application retrieved.']);
    }

    public function updateStatus(
        UpdateApplicationStatusRequest $request,
        Application $application,
        ApplicationStatusService $statusService,
    ): ApplicationResource {
        $updated = $statusService->transition(
            $request->user(),
            $application,
            ApplicationStatus::from($request->validated('status')),
            $request->validated('remark'),
        );

        return (new ApplicationResource($updated->load([
            'serviceType.department',
            'documents',
            'statusHistories' => fn ($query) => $query
                ->orderBy('created_at')
                ->orderBy('id'),
        ])))->additional(['message' => 'Application status updated.']);
    }

    public function resubmit(
        ResubmitApplicationDocumentsRequest $request,
        Application $application,
        ApplicationStatusService $statusService,
    ): ApplicationResource {
        $updated = $statusService->resubmit(
            $request->user(),
            $application,
            $request->file('documents', []),
        );

        return (new ApplicationResource($updated->load([
            'serviceType.department',
            'documents',
            'statusHistories' => fn ($query) => $query
                ->orderBy('created_at')
                ->orderBy('id'),
        ])))->additional(['message' => 'Replacement documents submitted.']);
    }
}
