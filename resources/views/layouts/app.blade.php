<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Validasi Data') - BPS Kabupaten Purwakarta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-800 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
        <div class="mx-auto flex min-h-18 max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 xl:px-8">
            <a href="{{ route('dashboard') }}" class="min-w-0 rounded-md focus:outline-none focus:ring-2 focus:ring-bps-blue focus:ring-offset-2" aria-label="Sistem Validasi Data BPS Kabupaten Purwakarta">
                <x-bps-brand compact />
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-bps-light text-bps-blue' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Dashboard
                </a>
                <a href="{{ route('batches.create') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('batches.create') ? 'bg-bps-light text-bps-blue' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Unggah Excel
                </a>
                <a href="{{ route('batches.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('batches.index', 'batches.preview', 'validation.*') ? 'bg-bps-light text-bps-blue' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Riwayat Validasi
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('profiles.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('profiles.*') ? 'bg-bps-light text-bps-blue' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Profil & Aturan
                    </a>
                    <a href="{{ route('users.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-bps-light text-bps-blue' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Pengguna
                    </a>
                @endif
            </nav>

            <div class="flex shrink-0 items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="max-w-40 truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="text-xs font-medium {{ auth()->user()->isAdmin() ? 'text-bps-orange' : 'text-bps-blue' }}">
                        {{ auth()->user()->isAdmin() ? 'Administrator' : 'Petugas BPS' }}
                    </p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
            </div>
        </div>

        <details class="border-t border-slate-100 lg:hidden">
            <summary class="mx-auto flex max-w-[1600px] cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-medium text-slate-700 sm:px-6">
                <span>Navigasi aplikasi</span>
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                </svg>
            </summary>
            <nav class="mx-auto grid max-w-[1600px] gap-1 px-4 pb-4 sm:px-6" aria-label="Navigasi seluler">
                <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-bps-light text-bps-blue' : 'text-slate-700 hover:bg-slate-100' }}">Dashboard</a>
                <a href="{{ route('batches.create') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('batches.create') ? 'bg-bps-light text-bps-blue' : 'text-slate-700 hover:bg-slate-100' }}">Unggah Excel</a>
                <a href="{{ route('batches.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('batches.index', 'batches.preview', 'validation.*') ? 'bg-bps-light text-bps-blue' : 'text-slate-700 hover:bg-slate-100' }}">Riwayat Validasi</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('profiles.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('profiles.*') ? 'bg-bps-light text-bps-blue' : 'text-slate-700 hover:bg-slate-100' }}">Profil & Aturan Validasi</a>
                    <a href="{{ route('users.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-bps-light text-bps-blue' : 'text-slate-700 hover:bg-slate-100' }}">Kelola Pengguna</a>
                @endif
            </nav>
        </details>
    </header>

    <div class="mx-auto mt-5 w-full max-w-[1600px] space-y-3 px-4 sm:px-6 xl:px-8">
        @if(session('success'))
            <div role="status" class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 12l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div role="alert" class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 011.414 0L10 7.172l1.293-1.293a1 1 0 111.414 1.414L11.414 8.586l1.293 1.293a1 1 0 01-1.414 1.414L10 10l-1.293 1.293a1 1 0 01-1.414-1.414l1.293-1.293-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div role="status" class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0v3a1 1 0 012 0V6zm0 7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif
    </div>

    <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 sm:px-6 sm:py-8 xl:px-8">
        @yield('content')
    </main>

    <footer class="mt-8 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-[1600px] flex-col gap-1 px-4 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 xl:px-8">
            <p>&copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Purwakarta</p>
            <p class="font-medium">Sistem Validasi Data</p>
        </div>
    </footer>
</body>
</html>
