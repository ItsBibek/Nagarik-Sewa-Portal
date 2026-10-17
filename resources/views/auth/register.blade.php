@extends('layouts.app')

@section('title', 'Register | Nagarik Sewa Portal')

@section('content')
    <section class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-semibold text-teal-700">Citizen account</p>
        <h1 class="mt-2 text-2xl font-bold">Create your account</h1>
        <p class="mt-2 text-slate-600">Register to submit applications and keep track of their progress.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="mt-6 grid gap-5" method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="grid gap-2">
                <label class="text-sm font-medium" for="name">Full name</label>
                <input class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20" id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium" for="email">Email</label>
                <input class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium" for="phone">Phone <span class="font-normal text-slate-500">(optional)</span></label>
                <input class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20" id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel">
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium" for="password">Password</label>
                <input class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20" id="password" name="password" type="password" autocomplete="new-password" required>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium" for="password_confirmation">Confirm password</label>
                <input class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>

            <button class="rounded-lg bg-blue-800 px-4 py-3 font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2" type="submit">
                Create account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Already registered? <a class="font-semibold text-blue-800 hover:underline" href="{{ route('login') }}">Sign in</a>
        </p>
    </section>
@endsection
