@extends('layouts.app')

@section('title', 'Track application | Nagarik Sewa Portal')

@section('content')
    <section class="mx-auto max-w-3xl">
        <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Application tracking</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Your application status</h1>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Tracking ID</p>
                    <p class="mt-1 break-all font-mono text-lg font-bold text-blue-950">{{ $application->tracking_id }}</p>
                </div>
                <x-status-badge :status="$application->status" class="px-4 py-2" />
            </div>

            <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Service</dt>
                    <dd class="mt-1 font-medium">{{ $application->serviceType->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Department</dt>
                    <dd class="mt-1 font-medium">{{ $application->serviceType->department->name }}</dd>
                </div>
            </dl>
        </div>

        <section class="mt-8">
            <h2 class="text-xl font-bold">Status history</h2>
            <ol class="mt-4 grid gap-3">
                @foreach ($application->statusHistories as $history)
                    <li class="flex gap-4 rounded-xl border border-slate-200 bg-white p-4">
                        <span class="mt-1 size-3 shrink-0 rounded-full bg-teal-600" aria-hidden="true"></span>
                        <div>
                            <p class="font-semibold">{{ str($history->new_status)->replace('_', ' ')->title() }}</p>
                            <time class="mt-1 block text-sm text-slate-500" datetime="{{ $history->created_at->toIso8601String() }}">
                                {{ $history->created_at->format('M j, Y · g:i A') }}
                            </time>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="mt-4 text-sm text-slate-500">For your privacy, this public tracking page does not display staff remarks or uploaded documents.</p>
        </section>

        <a class="mt-8 inline-flex font-semibold text-blue-800 hover:underline" href="{{ route('home') }}">← Back to home</a>
    </section>
@endsection
