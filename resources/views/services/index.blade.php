@extends('layouts.app')

@section('title', 'Available services | Nagarik Sewa Portal')

@section('content')
    <section class="max-w-3xl">
        <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Find what you need</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Available services</h1>
        <p class="mt-3 text-slate-600">A focused demonstration of one ward-level municipal service in Kathmandu.</p>
        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
            This portfolio demonstration does not assert official Kathmandu Metropolitan City requirements, fees, or processing times. Confirm the current procedure with the selected ward office before relying on it.
        </p>
    </section>

    @if ($departments->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
            No services are available at the moment.
        </div>
    @else
        <div class="mt-8 grid gap-6">
            @foreach ($departments as $department)
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{{ $department->code }}</p>
                            <h2 class="mt-1 text-xl font-bold">{{ $department->name }}</h2>
                            @if ($department->description)
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $department->description }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-800">
                            {{ $department->serviceTypes->count() }}
                            {{ \Illuminate\Support\Str::plural('service', $department->serviceTypes->count()) }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @forelse ($department->serviceTypes as $serviceType)
                            <article class="flex flex-col justify-between gap-5 rounded-xl border border-slate-200 p-5">
                                <div>
                                    <h3 class="text-lg font-semibold">Residential recommendation letter</h3>
                                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <dt class="text-slate-500">Example fee (not official)</dt>
                                            <dd class="mt-1 font-medium">NPR {{ number_format((float) $serviceType->fee, 2) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-500">Sample processing time</dt>
                                            <dd class="mt-1 font-medium">{{ $serviceType->processing_days }} days</dd>
                                        </div>
                                    </dl>
                                    <p class="mt-4 text-sm text-slate-600">
                                        Example documents: {{ collect($serviceType->required_documents)->map(fn ($document) => str($document)->replace('_', ' ')->title())->join(' and ') }}.
                                    </p>
                                </div>
                                @if (! auth()->check() || auth()->user()->role === 'citizen')
                                    <a class="inline-flex w-fit items-center rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-900"
                                       href="{{ route('applications.create', ['service_type_id' => $serviceType->id]) }}">
                                        Start application
                                    </a>
                                @else
                                    <p class="text-sm text-slate-600">
                                        Applications are submitted by citizens.
                                        <a class="font-semibold text-blue-800 hover:underline" href="{{ route(auth()->user()->role === 'admin' ? 'admin.dashboard' : 'dashboard') }}">
                                            Go to your dashboard
                                        </a>
                                    </p>
                                @endif
                            </article>
                        @empty
                            <p class="text-sm text-slate-500">No services listed for this department yet.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
