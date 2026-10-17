<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\OfficerApplicationController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/track/{tracking_id}', [ApplicationController::class, 'track'])
    ->where('tracking_id', 'NSP-[A-Z0-9]{12}')
    ->name('applications.track');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/applications/{application}/documents/{document}', [
        ApplicationController::class,
        'downloadDocument',
    ])->name('applications.documents.download');

    Route::middleware('role:citizen')->group(function (): void {
        Route::get('/applications', [ApplicationController::class, 'index'])
            ->name('applications.index');
        Route::get('/applications/create', [ApplicationController::class, 'create'])
            ->name('applications.create');
        Route::post('/applications', [ApplicationController::class, 'store'])
            ->name('applications.store');
        Route::get('/applications/{application}/corrections', [
            ApplicationController::class,
            'editCorrections',
        ])->name('applications.corrections.edit');
        Route::post('/applications/{application}/corrections', [
            ApplicationController::class,
            'resubmit',
        ])->name('applications.corrections.store');
    });

    Route::middleware('role:officer')->prefix('officer')->name('officer.')->group(function (): void {
        Route::get('/applications', [OfficerApplicationController::class, 'index'])
            ->name('applications.index');
        Route::get('/applications/{application}', [OfficerApplicationController::class, 'show'])
            ->name('applications.show');
        Route::patch('/applications/{application}/status', [
            OfficerApplicationController::class,
            'update',
        ])->name('applications.status.update');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/applications', [AdminController::class, 'applications'])->name('applications.index');
        Route::get('/service', [AdminController::class, 'editService'])->name('service.edit');
        Route::put('/service/{serviceType}', [AdminController::class, 'updateService'])
            ->name('service.update');
    });
});
