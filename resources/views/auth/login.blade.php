<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Sistem Informasi Validasi Data BPS Kab. Purwakarta</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-bps-blue: #005088;
            --color-bps-dark: #003660;
            --color-bps-light: #e6f0fa;
            --color-bps-orange: #f26522;
        }
    </style>
</head>
<body class="bg-slate-100 font-['Inter',sans-serif] text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Card Login -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden">
            <!-- Header Kartu -->
            <div class="bg-gradient-to-r from-blue-800 to-blue-700 px-8 py-7 text-white text-center relative">
                <div class="w-14 h-14 bg-white/10 rounded-xl mx-auto flex items-center justify-center backdrop-blur-xs mb-3 border border-white/20">
                    <span class="text-2xl font-black tracking-tight text-white">BPS</span>
                </div>
                <h1 class="text-xl font-bold tracking-tight">SISTEM VALIDASI DATA</h1>
                <p class="text-xs text-blue-100 mt-1">Badan Pusat Statistik Kabupaten Purwakarta</p>
            </div>

            <!-- Body Form Login -->
            <div class="p-8">
                @if(session('info'))
                    <div class="p-3.5 mb-5 text-xs text-blue-800 rounded-lg bg-blue-50 border border-blue-200 flex items-center">
                        <svg class="w-4 h-4 mr-2 shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-3.5 mb-5 text-xs text-red-800 rounded-lg bg-red-50 border border-red-200">
                        <div class="font-semibold mb-1">Gagal masuk ke sistem:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login.process') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email</label>
                        <div class="relative">
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                placeholder="nama@bps.go.id">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-slate-700">Kata Sandi</label>
                        </div>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                            <span class="ml-2">Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full py-2.5 px-4 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm hover:shadow transition duration-150 flex items-center justify-center space-x-2">
                        <span>Masuk ke Sistem</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>

                <!-- Box Bantuan Pengujian TA -->
                <div class="mt-6 pt-5 border-t border-slate-200">
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Akun Pengujian (Demo TA):
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" onclick="fillCredentials('admin@bps.go.id', 'password')"
                            class="text-left p-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-blue-50 hover:border-blue-300 transition-colors cursor-pointer group">
                            <div class="font-bold text-purple-700 group-hover:text-blue-700">Administrator</div>
                            <div class="text-slate-500 text-[11px] truncate">admin@bps.go.id</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Sandi: password</div>
                        </button>
                        <button type="button" onclick="fillCredentials('petugas@bps.go.id', 'password')"
                            class="text-left p-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-blue-50 hover:border-blue-300 transition-colors cursor-pointer group">
                            <div class="font-bold text-blue-700 group-hover:text-blue-800">Petugas BPS</div>
                            <div class="text-slate-500 text-[11px] truncate">petugas@bps.go.id</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Sandi: password</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            Tugas Akhir Sistem Informasi &bull; &copy; {{ date('Y') }} BPS Kabupaten Purwakarta
        </p>
    </div>

    <script>
        function fillCredentials(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
