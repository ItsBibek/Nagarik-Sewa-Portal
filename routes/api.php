<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\TrackingController;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:login')
        ->name('tokens.store');

    Route::get('/track/{tracking_id}', [TrackingController::class, 'show'])
        ->where('tracking_id', 'NSP-[A-Z0-9]{12}')
        ->middleware('throttle:20,1')
        ->name('tracking.show');

    Route::get('/user', [TokenController::class, 'user'])
        ->middleware('auth:sanctum')
        ->name('user.show');

    Route::get('/services', function () {
        $services = ServiceType::query()
            ->where('is_active', true)
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceType $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'department' => $service->department->name,
                'example_fee_npr' => (float) $service->fee,
                'sample_processing_days' => $service->processing_days,
                'example_required_documents' => $service->required_documents,
            ]);

        return response()->json([
            'data' => $services,
            'message' => 'Available services retrieved.',
        ]);
    })->name('services.index');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/tokens/current', [TokenController::class, 'destroy'])
            ->name('tokens.destroy');
        Route::get('/applications', [ApplicationController::class, 'index'])
            ->name('applications.index');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])
            ->name('applications.show');
        Route::post('/applications', [ApplicationController::class, 'store'])
            ->middleware('role:citizen')
            ->name('applications.store');
        Route::post('/applications/{application}/corrections', [ApplicationController::class, 'resubmit'])
            ->middleware('role:citizen')
            ->name('applications.corrections.store');
        Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus'])
            ->middleware('role:officer,admin')
            ->name('applications.status.update');
    });
});
