<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nagarik Sewa Portal')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Main navigation">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight text-blue-900 sm:text-xl">
                Nagarik Sewa Portal
            </a>
            <div class="flex items-center gap-3 text-sm font-medium sm:gap-5">
                @auth
                    <a href="{{ route(auth()->user()->role === 'admin' ? 'admin.dashboard' : 'dashboard') }}"
                       class="text-slate-600 hover:text-blue-800">Dashboard</a>
                    @if (auth()->user()->role === 'citizen')
                        <a href="{{ route('services.index') }}" class="text-slate-600 hover:text-blue-800">Service</a>
                        <a href="{{ route('applications.index') }}" class="text-slate-600 hover:text-blue-800">My requests</a>
                    @endif
                    @if (auth()->user()->role === 'officer')
                        <a href="{{ route('officer.applications.index') }}" class="text-slate-600 hover:text-blue-800">Review queue</a>
                    @endif
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-blue-800">Admin home</a>
                        <a href="{{ route('admin.service.edit') }}" class="text-slate-600 hover:text-blue-800">Service setup</a>
                        <a href="{{ route('admin.applications.index') }}" class="text-slate-600 hover:text-blue-800">All requests</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50" type="submit">
                            Sign out
                        </button>
                    </form>
                @else
                    <a href="{{ route('services.index') }}" class="text-slate-600 hover:text-blue-800">Service</a>
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-blue-800">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-blue-800 px-3 py-2 text-white hover:bg-blue-900">
                        Register
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-slate-500 sm:px-6 lg:px-8">
            Nagarik Sewa Portal · Digital public services
        </div>
    </footer>
</body>
</html>
