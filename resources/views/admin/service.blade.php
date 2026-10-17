@extends('layouts.app')

@section('title', 'Service setup | Nagarik Sewa Portal')

@section('content')
    <section class="mx-auto grid max-w-3xl gap-6">
        <header>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-800 hover:underline">← Admin dashboard</a>
            <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-blue-800">Single-service setup</p>
            <h1 class="mt-2 text-3xl font-bold">Edit service details</h1>
            <p class="mt-2 text-slate-600">Update the name, example fee, and sample processing time shown to residents.</p>
        </header>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.service.update', $serviceType) }}"
              class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:p-7">
            @csrf
            @method('PUT')
            <div class="grid gap-2">
                <label for="name" class="text-sm font-semibold">Service name</label>
                <input id="name" name="name" value="{{ old('name', $serviceType->name) }}" required maxlength="255"
                       class="rounded-lg border border-slate-300 px-3 py-2.5">
            </div>
            <div class="grid gap-2">
                <label for="fee" class="text-sm font-semibold">Example fee in NPR</label>
                <input id="fee" name="fee" type="number" min="0" max="1000000" step="0.01"
                       value="{{ old('fee', $serviceType->fee) }}" required
                       class="rounded-lg border border-slate-300 px-3 py-2.5">
                <p class="text-sm text-amber-800">This is a portfolio demo price, not a verified official ward fee. Check with the ward before publishing an official amount.</p>
            </div>
            <div class="grid gap-2">
                <label for="processing_days" class="text-sm font-semibold">Sample processing time (days)</label>
                <input id="processing_days" name="processing_days" type="number" min="1" max="365"
                       value="{{ old('processing_days', $serviceType->processing_days) }}" required
                       class="rounded-lg border border-slate-300 px-3 py-2.5">
            </div>
            <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold">Example documents currently requested</p>
                <p class="mt-2">{{ collect($serviceType->required_documents)->map(fn ($document) => str($document)->replace('_', ' ')->title())->join(' and ') }}</p>
                <p class="mt-2 text-xs text-slate-500">Verify the actual document list with the ward office before representing it as official.</p>
            </div>
            <button type="submit" class="w-fit rounded-lg bg-blue-800 px-5 py-3 font-semibold text-white hover:bg-blue-900">
                Save service details
            </button>
        </form>
    </section>
@endsection
