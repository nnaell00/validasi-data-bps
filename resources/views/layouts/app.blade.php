<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Validasi Data') - BPS Kabupaten Purwakarta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div id="app-shell" class="min-h-screen">
        <x-app-sidebar />

        <button id="sidebar-backdrop" type="button" class="fixed inset-0 z-40 bg-slate-950/55 lg:hidden" aria-label="Tutup navigasi"></button>

        <div class="app-main flex min-h-screen min-w-0 flex-col">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 shadow-xs backdrop-blur-sm">
                <div class="mx-auto flex min-h-16 w-full max-w-[1600px] items-center gap-3 px-4 sm:px-6 xl:px-8">
                    <button id="mobile-sidebar-open" type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-bps-blue focus:outline-none focus:ring-2 focus:ring-bps-blue/30 lg:hidden" aria-label="Buka navigasi" aria-controls="app-sidebar" aria-expanded="false">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">@yield('title', 'Sistem Validasi Data')</p>
                        <p class="hidden text-xs text-slate-500 sm:block">Badan Pusat Statistik Kabupaten Purwakarta</p>
                    </div>

                    <div class="min-w-0 text-right">
                        <p class="max-w-48 truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-xs font-medium {{ auth()->user()->isAdmin() ? 'text-bps-orange' : 'text-bps-blue' }}">
                            {{ auth()->user()->isAdmin() ? 'Administrator' : 'Petugas BPS' }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="mx-auto w-full max-w-[1600px] space-y-3 px-4 pt-4 sm:px-6 xl:px-8">
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
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0V6zm0 7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif
            </div>

            <main class="mx-auto w-full max-w-[1600px] min-w-0 flex-1 px-4 py-6 sm:px-6 sm:py-8 xl:px-8">
                @yield('content')
            </main>

            <footer class="mt-8 border-t border-slate-200 bg-white">
                <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-1 px-4 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 xl:px-8">
                    <p>&copy; {{ date('Y') }} Badan Pusat Statistik Kabupaten Purwakarta</p>
                    <p class="font-medium">Sistem Validasi Data</p>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
