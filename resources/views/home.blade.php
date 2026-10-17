@extends('layouts.app')

@section('title', 'Nagarik Sewa Portal')

@section('content')
    <section class="overflow-hidden rounded-2xl bg-blue-900 px-6 py-14 text-white sm:px-12 sm:py-20">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-200">One ward-level service</p>
            <h1 class="mt-5 text-4xl font-bold tracking-tight sm:text-6xl">Residential recommendations, made simpler.</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-blue-100">
                Submit a residential recommendation request to a selected Kathmandu ward office demo and follow its review with a tracking ID.
            </p>
            <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-100">
                This portfolio demonstration does not represent verified official fees, document requirements, or processing times.
            </p>
            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('services.index') }}" class="rounded-xl bg-white px-5 py-3 font-semibold text-blue-900 shadow hover:bg-blue-50">
                    View the service
                </a>
                @if (! auth()->check() || auth()->user()->role === 'citizen')
                    <a href="{{ route('applications.create') }}" class="rounded-xl border border-white/40 px-5 py-3 font-semibold text-white hover:bg-white/10">
                        Start an application
                    </a>
                @elseif (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-white/40 px-5 py-3 font-semibold text-white hover:bg-white/10">
                        Admin dashboard
                    </a>
                @else
                    <a href="{{ route('officer.applications.index') }}" class="rounded-xl border border-white/40 px-5 py-3 font-semibold text-white hover:bg-white/10">
                        Review requests
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="mt-12 grid gap-5 md:grid-cols-3">
        @foreach ([
            ['01', 'Choose the service', 'Start a residential recommendation request for the selected demo ward.'],
            ['02', 'Submit documents', 'Complete the online form and securely attach the required files.'],
            ['03', 'Track progress', 'Use your application tracking ID to see status updates.'],
        ] as [$number, $title, $description])
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-bold text-teal-700">{{ $number }}</p>
                <h2 class="mt-3 text-lg font-semibold">{{ $title }}</h2>
                <p class="mt-2 leading-6 text-slate-600">{{ $description }}</p>
            </article>
        @endforeach
    </section>
@endsection
