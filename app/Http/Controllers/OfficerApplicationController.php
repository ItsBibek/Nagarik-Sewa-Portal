<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Models\Application;
use App\Services\ApplicationStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OfficerApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ApplicationStatus::class)],
        ]);

        $applications = Application::query()
            ->whereHas('serviceType', fn ($query) => $query
                ->where('is_active', true)
                ->where('department_id', $request->user()->department_id))
            ->with([
                'user',
                'serviceType.department',
                'currentOfficer',
            ])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('status', $validated['status']),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('officer.applications.index', [
            'applications' => $applications,
            'statuses' => ApplicationStatus::cases(),
            'selectedStatus' => $validated['status'] ?? null,
        ]);
    }

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);

        $application->load([
            'user',
            'serviceType.department',
            'documents',
            'statusHistories' => fn ($query) => $query
                ->with('changedBy')
                ->orderBy('created_at')
                ->orderBy('id'),
            'currentOfficer',
        ]);

        $currentStatus = ApplicationStatus::tryFrom($application->status);

        return view('officer.applications.show', [
            'application' => $application,
            'nextStatuses' => $currentStatus?->officerTransitions() ?? [],
        ]);
    }

    public function update(
        UpdateApplicationStatusRequest $request,
        Application $application,
        ApplicationStatusService $statusService,
    ): RedirectResponse {
        $statusService->transition(
            $request->user(),
            $application,
            ApplicationStatus::from($request->validated('status')),
            $request->validated('remark'),
        );

        return redirect()
            ->route('officer.applications.show', $application)
            ->with('status', 'Application status updated.');
    }
}
