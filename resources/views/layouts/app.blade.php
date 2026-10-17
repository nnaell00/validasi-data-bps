<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Validasi & Perbaikan Kualitas Data') - BPS Kabupaten Purwakarta</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-bps-blue: #005088;
            --color-bps-dark: #003660;
            --color-bps-light: #e6f0fa;
            --color-bps-orange: #f26522;
            --color-bps-green: #28a745;
        }
    </style>
</head>
<body class="bg-slate-50 font-['Inter',sans-serif] text-slate-800 min-h-screen flex flex-col">
    <!-- Navbar Atas -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Sisi Kiri: Brand & Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-700 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                            <span class="tracking-tight">BPS</span>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-slate-900 leading-tight">VALIDASI DATA BPS</span>
                            <span class="block text-xs text-slate-500 font-medium">BPS Kabupaten Purwakarta</span>
                        </div>
                    </a>
                </div>

                <!-- Navigasi Menu Utama -->
                <nav class="hidden md:flex items-center space-x-2">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Dashboard
                    </a>

                    <a href="{{ route('batches.create') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('batches.create') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Unggah Excel
                    </a>
                    <a href="{{ route('batches.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('batches.index') || request()->routeIs('batches.preview') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Riwayat Validasi
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('profiles.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('profiles.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Profil & Aturan Validasi
                        </a>
                        <a href="{{ route('users.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Kelola Pengguna
                        </a>
                    @endif
                </nav>

                <!-- Sisi Kanan: Profil & Logout -->
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                        <div class="flex items-center justify-end space-x-1.5">
                            @if(auth()->user()->isAdmin())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">
                                    Administrator
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                    Petugas BPS
                                </span>
                            @endif
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')" class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Keluar">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Strip -->
        <div class="md:hidden border-t border-slate-200 px-4 py-2 flex space-x-2 bg-slate-50">
            <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded text-xs font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-slate-600' }}">Dashboard</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('profiles.index') }}" class="px-2.5 py-1.5 rounded text-xs font-medium {{ request()->routeIs('profiles.*') ? 'bg-blue-600 text-white' : 'text-slate-600' }}">Profil Validasi</a>
                <a href="{{ route('users.index') }}" class="px-2.5 py-1.5 rounded text-xs font-medium {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white' : 'text-slate-600' }}">Pengguna</a>
            @endif
        </div>
    </header>

    <!-- Notifikasi Alert Flash Message -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if(session('success'))
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('info') }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Konten Utama Halaman -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-1">
        @yield('content')
    </main>

    <!-- Footer Sistem -->
    <footer class="bg-white border-t border-slate-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500">
            <div>
                © {{ date('Y') }} <strong>Badan Pusat Statistik (BPS) Kabupaten Purwakarta</strong>. Seluruh hak cipta dilindungi.
            </div>
            <div class="mt-2 sm:mt-0 font-medium">
                Tugas Akhir Sistem Informasi &bull; Validasi & Perbaikan Kualitas Data
            </div>
        </div>
    </footer>
</body>
</html>
