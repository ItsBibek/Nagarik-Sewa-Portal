<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateServiceTypeRequest;
use App\Models\Application;
use App\Models\ServiceType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'serviceType' => ServiceType::query()
                ->where('is_active', true)
                ->with('department')
                ->firstOrFail(),
            'applicationCounts' => Application::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'recentApplications' => Application::query()
                ->with(['user', 'serviceType'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function applications(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:submitted,under_review,correction_required,approved,rejected'],
        ]);

        return view('admin.applications', [
            'applications' => Application::query()
                ->with(['user', 'serviceType.department'])
                ->when(
                    isset($validated['status']),
                    fn ($query) => $query->where('status', $validated['status']),
                )
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'selectedStatus' => $validated['status'] ?? '',
            'statuses' => [
                'submitted' => 'Submitted',
                'under_review' => 'Under review',
                'correction_required' => 'Correction needed',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
        ]);
    }

    public function editService(): View
    {
        return view('admin.service', [
            'serviceType' => ServiceType::query()
                ->where('is_active', true)
                ->with('department')
                ->firstOrFail(),
        ]);
    }

    public function updateService(
        UpdateServiceTypeRequest $request,
        ServiceType $serviceType,
    ): RedirectResponse {
        abort_unless($serviceType->is_active, 404);

        $serviceType->update($request->validated());

        return redirect()
            ->route('admin.service.edit')
            ->with('status', 'Service details saved.');
    }
}
