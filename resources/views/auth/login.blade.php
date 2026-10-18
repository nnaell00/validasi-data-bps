<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Sistem Validasi Data | BPS Kabupaten Purwakarta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
    <main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
        <section class="grid w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5 lg:min-h-[640px] lg:grid-cols-2">
            <div class="relative flex flex-col justify-between overflow-hidden bg-bps-navy px-7 py-8 text-white sm:px-10 sm:py-10 lg:px-12 lg:py-12">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-20">
                    <div class="absolute inset-0 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:22px_22px]"></div>
                    <div class="absolute -bottom-24 -right-20 h-72 w-72 rounded-full border border-white/20"></div>
                    <div class="absolute -bottom-12 -right-8 h-52 w-52 rounded-full border border-white/20"></div>
                </div>

                <div class="relative z-10 space-y-8">
                    <x-bps-brand inverse />

                    <div class="max-w-md space-y-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">Portal Pengolahan Data</p>
                        <h1 class="text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">Sistem Validasi Data</h1>
                        <p class="max-w-sm text-sm leading-6 text-blue-100 sm:text-base">
                            Sistem pengelolaan, validasi, dan perbaikan kualitas data dalam satu alur kerja yang terjaga.
                        </p>
                    </div>
                </div>

                <div class="relative z-10 mt-12 max-w-md rounded-xl border border-white/15 bg-white/5 p-5 backdrop-blur-sm">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-white">Alur pengolahan terstruktur</p>
                            <p class="mt-1 text-xs text-blue-200">Dari data masuk hingga siap ditinjau</p>
                        </div>
                        <span class="rounded-lg bg-white/10 p-2 text-orange-300" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <path d="M4 19V5m0 14h17M8 15v-3m4 3V8m4 7v-5m4 5V6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </div>
                    <div class="flex h-20 items-end gap-2" aria-hidden="true">
                        <div class="h-7 flex-1 rounded-t bg-blue-300/70"></div>
                        <div class="h-12 flex-1 rounded-t bg-blue-200/80"></div>
                        <div class="h-9 flex-1 rounded-t bg-orange-300/90"></div>
                        <div class="h-16 flex-1 rounded-t bg-blue-100"></div>
                        <div class="h-11 flex-1 rounded-t bg-emerald-300/90"></div>
                        <div class="h-[4.5rem] flex-1 rounded-t bg-blue-200/80"></div>
                        <div class="h-14 flex-1 rounded-t bg-blue-300/70"></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-[10px] font-medium uppercase tracking-wider text-blue-200">
                        <span>Kelola</span>
                        <span>Validasi</span>
                        <span>Perbaiki</span>
                        <span>Ekspor</span>
                    </div>
                </div>

                <div class="relative z-10 mt-8 flex items-center gap-2 text-xs text-blue-200">
                    <span class="h-2 w-2 rounded-full bg-bps-orange"></span>
                    <span>Data terstruktur untuk mendukung pengambilan keputusan.</span>
                </div>
            </div>

            <div class="flex items-center justify-center px-6 py-10 sm:px-10 lg:px-12">
                <div class="w-full max-w-md">
                    <div class="mb-8 space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bps-blue">Selamat datang</p>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Masuk ke Sistem</h2>
                        <p class="text-sm leading-6 text-slate-500">Gunakan akun resmi Anda untuk melanjutkan ke ruang kerja.</p>
                    </div>

                    @if(session('info'))
                        <div role="status" class="mb-5 flex gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3.5 text-sm text-blue-900">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0v3a1 1 0 012 0V6zm0 7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ session('info') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <p class="font-semibold">Tidak dapat masuk</p>
                            <ul class="mt-2 list-inside list-disc space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('login.process') }}" method="POST" class="space-y-5">
                        @csrf
                        <div class="space-y-2">
                            <label for="email" class="block text-sm font-medium text-slate-700">Alamat email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                                autofocus
                                placeholder="nama@instansi.go.id"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10"
                            >
                        </div>

                        <div class="space-y-2">
                            <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi</label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="Masukkan kata sandi"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 pr-24 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-bps-blue focus:ring-4 focus:ring-bps-blue/10"
                                >
                                <button
                                    type="button"
                                    data-password-toggle
                                    aria-controls="password"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 rounded-r-lg px-3.5 text-xs font-semibold text-bps-blue hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-bps-blue"
                                >Tampilkan</button>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                @checked(old('remember'))
                                class="h-4 w-4 rounded border-slate-300 text-bps-blue focus:ring-bps-blue"
                            >
                            <span>Ingat saya di perangkat ini</span>
                        </label>

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-bps-blue px-4 py-3 text-sm font-semibold text-white transition hover:bg-bps-navy focus:outline-none focus:ring-4 focus:ring-bps-blue/20"
                        >
                            <span>Masuk ke Sistem</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>

                    <p class="mt-8 border-t border-slate-200 pt-5 text-xs leading-5 text-slate-500">
                        Akses sistem hanya untuk pengguna yang memiliki akun dan kewenangan resmi.
                    </p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
