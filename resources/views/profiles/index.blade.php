@extends('layouts.app')

@section('title', 'Profil & Aturan Validasi')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Profil & Aturan Validasi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola skema validasi dinamis untuk data kepegawaian dan dataset sektoral lainnya.</p>
        </div>
    </div>

    <!-- Alert Edukasi / Konteks Fleksibilitas TA -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-800 flex items-start space-x-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <div class="font-bold text-sm text-blue-900 mb-0.5">Arsitektur Profil Dinamis (Extensible Architecture)</div>
            <p class="leading-relaxed">
                Sistem dirancang modular. Fokus implementasi pertama adalah <strong>Data Kepegawaian BPS</strong>. Struktur ini memungkinkan penambahan profil data baru (seperti Sensus Pertanian, Susenas, atau Statistik Sosial) di masa depan tanpa mengubah arsitektur inti sistem.
            </p>
        </div>
    </div>

    <!-- Daftar Profil Data -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($profiles as $profile)
            <div class="app-card flex flex-col justify-between p-6">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $profile->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $profile->is_active ? 'bg-emerald-500' : 'bg-slate-400' }} mr-1.5"></span>
                            {{ $profile->is_active ? 'Profil Aktif' : 'Non-aktif' }}
                        </span>
                        <span class="text-xs text-slate-400 font-medium">{{ $profile->rules_count }} Aturan Kolom</span>
                    </div>

                    <h2 class="text-lg font-bold text-slate-900">{{ $profile->name }}</h2>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        {{ $profile->description ?: 'Tidak ada deskripsi profil.' }}
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-between">
                    <form action="{{ route('profiles.toggle-status', $profile) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-xs font-semibold {{ $profile->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                            {{ $profile->is_active ? 'Nonaktifkan Profil' : 'Aktifkan Profil' }}
                        </button>
                    </form>

                    <a href="{{ route('profiles.show', $profile) }}" class="inline-flex items-center px-3.5 py-1.5 bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors">
                        <span>Kelola Aturan ({{ $profile->rules_count }})</span>
                        <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
