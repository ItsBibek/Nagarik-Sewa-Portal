@extends('layouts.app')

@section('title', 'Sign in | Nagarik Sewa Portal')

@section('content')
    <section class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-semibold text-teal-700">Welcome back</p>
        <h1 class="mt-2 text-2xl font-bold">Sign in to your account</h1>

        @if ($errors->any())
            <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="mt-6 grid gap-5" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="grid gap-2">
                <label class="text-sm font-medium" for="email">Email</label>
                <input class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-medium" for="password">Password</label>
                <input class="rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700/20" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600" for="remember">
                <input class="size-4 rounded border-slate-300 text-blue-800 focus:ring-blue-700" id="remember" name="remember" type="checkbox" value="1">
                Remember me
            </label>

            <button class="rounded-lg bg-blue-800 px-4 py-3 font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2" type="submit">
                Sign in
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Need an account? <a class="font-semibold text-blue-800 hover:underline" href="{{ route('register') }}">Register as a citizen</a>
        </p>
    </section>
@endsection
