@extends('layouts.app')

@section('title', 'Admin dashboard | Nagarik Sewa Portal')

@section('content')
    <section class="grid gap-7">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-800">Administrator</p>
                <h1 class="mt-2 text-3xl font-bold">Portal overview</h1>
                <p class="mt-2 text-slate-600">Manage the demo service and see incoming requests.</p>
            </div>
            <a href="{{ route('admin.service.edit') }}" class="rounded-lg bg-blue-800 px-4 py-3 font-semibold text-white hover:bg-blue-900">
                Edit service details
            </a>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="Application counts by status">
            @foreach ([
                'submitted' => 'Submitted',
                'under_review' => 'Under review',
                'correction_required' => 'Correction needed',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ] as $status => $label)
                <a href="{{ route('admin.applications.index', ['status' => $status]) }}"
                   class="rounded-xl border border-slate-200 bg-white p-4 hover:border-blue-400">
                    <p class="text-sm text-slate-600">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ $applicationCounts[$status] ?? 0 }}</p>
                </a>
            @endforeach
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-blue-800">ACTIVE DEMO SERVICE</p>
                    <h2 class="mt-2 text-xl font-bold">{{ $serviceType->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $serviceType->department->name }}</p>
                </div>
                <a href="{{ route('admin.service.edit') }}" class="font-semibold text-blue-800 hover:underline">Change details</a>
            </div>
            <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-600">Example fee (not an official KMC fee)</dt>
                    <dd class="mt-1 text-lg font-semibold">NPR {{ number_format((float) $serviceType->fee, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-600">Sample processing time</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $serviceType->processing_days }} days</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold">Latest requests</h2>
                <a href="{{ route('admin.applications.index') }}" class="font-semibold text-blue-800 hover:underline">See all requests</a>
            </div>
            @if ($recentApplications->isEmpty())
                <p class="mt-4 text-sm text-slate-600">No citizen requests have been submitted yet.</p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($recentApplications as $application)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div>
                                <p class="font-semibold">{{ $application->user->name }}</p>
                                <p class="mt-1 font-mono text-sm text-slate-600">{{ $application->tracking_id }}</p>
                            </div>
                            <x-status-badge :status="$application->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </section>
@endsection
