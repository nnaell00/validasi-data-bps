@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Banner Sambutan -->
    <div class="relative overflow-hidden rounded-2xl bg-bps-navy p-6 text-white sm:p-8">
        <div class="pointer-events-none absolute inset-0 opacity-20" aria-hidden="true" style="background-image: radial-gradient(#ffffff 0.7px, transparent 0.7px); background-size: 18px 18px;"></div>
        <div class="relative z-10 max-w-3xl">
            <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-xs mb-3 border border-white/20">
                <span class="mr-2 h-1.5 w-1.5 rounded-full bg-bps-green"></span>
                <span>{{ auth()->user()->isAdmin() ? 'Administrator Sistem' : 'Petugas Validasi BPS' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Sistem Validasi Data
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-blue-100 sm:text-base">
                @if($user->isPetugas())
                    Unggah berkas Excel, jalankan validasi data kepegawaian, dan tindak lanjuti anomali pada berkas Anda.
                @else
                    Aplikasi ini membantu memvalidasi dan memperbaiki kualitas data kepegawaian melalui profil dan aturan yang tersedia pada sistem.
                @endif
            </p>
            <div class="mt-5 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-xs font-medium text-white/80">
                    <span class="h-1 w-8 rounded-full bg-bps-orange"></span>
                    Badan Pusat Statistik Kabupaten Purwakarta
                </div>
                <a href="{{ route('batches.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-bps-navy shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white/80 focus:ring-offset-2 focus:ring-offset-bps-navy">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15V3m0 0L7.5 7.5M12 3l4.5 4.5M5 14v4.5A2.5 2.5 0 0 0 7.5 21h9a2.5 2.5 0 0 0 2.5-2.5V14"/>
                    </svg>
                    Unggah Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan Metrik / Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        @if($user->isPetugas())
            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Berkas Diproses</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['processed_batches'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Sesi validasi milik Anda yang sudah dijalankan</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>

            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Baris Valid</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['valid_rows'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Tidak memiliki anomali pada hasil validasi terbaru</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 3a11.955 11.955 0 01-8.618 2.984A12.02 12.02 0 003 9c0 5.591 3.824 10.293 9 11.622C17.176 19.293 21 14.591 21 9c0-1.04-.133-2.049-.382-3.016z"/>
                    </svg>
                </div>
            </div>

            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Baris Anomali</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['anomalous_rows'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Memiliki satu atau lebih anomali pada hasil terbaru</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>

            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Perlu Ditindaklanjuti</p>
                    <p class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['follow_up_rows'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Baris dengan anomali yang belum diselesaikan</p>
                </div>
                <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        @else
            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Akun</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total_users'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Admin & Petugas terdaftar</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>

            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sesi Validasi</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total_batches'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Berkas Excel diunggah</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>

            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Baris Data Diperiksa</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total_records_checked'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Total data dipindai</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>

            <div class="app-card flex items-center justify-between p-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Anomali Terdeteksi</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['total_anomalies'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $stats['total_corrected'] }} berhasil diperbaiki</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        @endif
    </div>

    <!-- Menu Tindakan Cepat (Quick Actions) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom 1 & 2: Panduan Alur Kerja -->
        <div class="app-card p-6 lg:col-span-2">
            <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Alur Kerja Validasi & Perbaikan Kualitas Data
            </h2>

            <div class="space-y-4">
                <div class="flex items-start space-x-3.5 p-3 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">1</div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Unggah berkas Excel dan periksa pratinjau</div>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih berkas Excel, lalu periksa pratinjau data sebelum memulai validasi.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3.5 p-3 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">2</div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Jalankan validasi sesuai profil</div>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih profil yang tersedia dan jalankan pemeriksaan sesuai aturan yang dikonfigurasi pada sistem.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3.5 p-3 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">3</div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Periksa anomali dan perbaiki data</div>
                        <p class="text-xs text-slate-500 mt-0.5">Tinjau detail anomali, lalu perbaiki baris data melalui aplikasi.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3.5 p-3 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">4</div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Jalankan validasi ulang dan periksa hasilnya</div>
                        <p class="text-xs text-slate-500 mt-0.5">Periksa kembali hasil validasi; baris tetap ditandai anomali jika masalah masih ditemukan.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3.5 p-3 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">5</div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Ekspor hasil jika diperlukan</div>
                        <p class="text-xs text-slate-500 mt-0.5">Unduh berkas hasil perbaikan atau laporan dari halaman hasil validasi.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom 3: Panel Aksi Cepat & Info Peran -->
        <div class="app-card flex flex-col justify-between p-6">
            <div>
                <h3 class="text-base font-bold text-slate-800 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $user->isAdmin() ? 'Akses & Menu' : 'Profil Validasi Aktif' }}
                </h3>

                <div class="space-y-2 mt-4">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('users.index') }}" class="w-full flex items-center justify-between rounded-lg border border-bps-blue/15 bg-bps-light p-3 text-sm font-semibold text-bps-navy transition-colors hover:bg-blue-100">
                            <span class="flex items-center">
                                <svg class="mr-2 h-4 w-4 text-bps-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Kelola Pengguna (Admin)
                            </span>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-bps-blue">Buka</span>
                        </a>
                    @endif

                    <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-600 space-y-1">
                        <div class="font-bold text-slate-700">Profil yang tersedia untuk validasi:</div>
                        @if($activeProfile)
                            <div class="flex items-center text-blue-700 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-blue-600 mr-2"></span>
                                {{ $activeProfile->name }}
                            </div>
                            @if($activeProfile->description)
                                <p class="text-[11px] text-slate-500">{{ $activeProfile->description }}</p>
                            @endif
                        @else
                            <p class="text-[11px] text-slate-500">Profil Data Kepegawaian belum aktif atau belum tersedia.</p>
                        @endif
                        <p class="text-[11px] text-slate-500 pt-1 border-t border-slate-200">
                            Aturan validasi mengikuti konfigurasi yang tersedia pada sistem.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-200 text-xs text-slate-500">
                Status Sistem: <span class="text-green-600 font-semibold">● MySQL & Framework Aktif</span>
            </div>
        </div>
    </div>
</div>
@endsection
