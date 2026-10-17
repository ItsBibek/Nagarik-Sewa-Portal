<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    public function show(string $trackingId): JsonResponse
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

        return response()->json([
            'data' => [
                'tracking_id' => $application->tracking_id,
                'status' => $application->status,
                'service' => $application->serviceType->name,
                'ward_office' => $application->serviceType->department->name,
                'submitted_at' => $application->created_at?->toIso8601String(),
                'status_history' => $application->statusHistories
                    ->map(fn ($history): array => [
                        'status' => $history->new_status,
                        'changed_at' => $history->created_at?->toIso8601String(),
                    ])
                    ->values(),
            ],
            'message' => 'Tracking information retrieved.',
        ]);
    }
}
