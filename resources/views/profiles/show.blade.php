@extends('layouts.app')

@section('title', 'Detail Aturan - ' . $profile->name)

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('profiles.index') }}" class="text-xs text-blue-600 hover:underline">&larr; Kembali ke Daftar Profil</a>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight mt-1">
                Aturan Validasi: {{ $profile->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $profile->description }}</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $profile->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                <span class="w-2 h-2 rounded-full {{ $profile->is_active ? 'bg-emerald-500' : 'bg-slate-400' }} mr-1.5"></span>
                Status: {{ $profile->is_active ? 'Profil Aktif' : 'Non-aktif' }}
            </span>
        </div>
    </div>

    <!-- Banner Catatan Khusus & Integritas Data TA -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Kotak 1: Aturan No. WA & 3 Kolom Unknown -->
        <div class="bg-amber-50/80 border border-amber-200/90 rounded-xl p-4 text-xs text-amber-900 space-y-2">
            <div class="font-bold text-amber-950 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                Aturan Khusus Kepegawaian BPS
            </div>
            <ul class="list-disc list-inside space-y-1 text-amber-800">
                <li><strong>No. WhatsApp</strong> bersifat opsional. Nilai kosong <u>tidak pernah</u> dianggap anomali.</li>
                <li><strong>3 Kolom Sumber yang belum diketahui namanya</strong> diabaikan dari penalti/anomali dan wajib dipertahankan utuh pada file ekspor.</li>
                <li>Data sumber asli <strong>tidak diubah otomatis</strong> ketika anomali ditemukan.</li>
            </ul>
        </div>

        <!-- Kotak 2: Penanda Aturan Perlu Konfirmasi -->
        <div class="bg-blue-50/80 border border-blue-200/90 rounded-xl p-4 text-xs text-blue-900 space-y-2">
            <div class="font-bold text-blue-950 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                Status Konfirmasi Aturan
            </div>
            <p class="leading-relaxed text-blue-800">
                Kolom dengan label <span class="bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-semibold">Perlu Konfirmasi</span> menandakan formula/pola aturan perlu diselaraskan dengan standar resmi BPS Kabupaten Purwakarta sebelum sidang TA.
            </p>
        </div>
    </div>

    <!-- Tabel Daftar Aturan Kolom -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800">Daftar Aturan Validasi per Kolom ({{ $profile->rules->count() }} Aturan)</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Pos.</th>
                        <th class="px-4 py-3">Nama Kolom</th>
                        <th class="px-4 py-3">Tipe Data</th>
                        <th class="px-4 py-3">Kewajiban</th>
                        <th class="px-4 py-3">Pola / Tipe Validasi</th>
                        <th class="px-4 py-3">Pesan Kesalahan Anomali</th>
                        <th class="px-4 py-3">Konfirmasi BPS</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @foreach($profile->rules as $rule)
                        <tr class="hover:bg-slate-50/75 transition-colors {{ $rule->needs_confirmation ? 'bg-amber-50/20' : '' }}">
                            <td class="px-4 py-3 font-mono text-slate-400">
                                #{{ $rule->column_index }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-900">{{ $rule->column_name }}</span>
                                @if($rule->column_name === 'No. WA')
                                    <span class="block text-[10px] text-emerald-600 font-medium">Opsional (Aman jika kosong)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-600">
                                <span class="px-2 py-0.5 bg-slate-100 rounded text-[11px]">{{ $rule->data_type }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($rule->is_required)
                                    <span class="font-semibold text-rose-600">Wajib Terisi</span>
                                @else
                                    <span class="text-slate-500 font-medium">Opsional</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-600 max-w-xs truncate">
                                @if($rule->validation_type === 'regex')
                                    <span title="{{ $rule->parameters['pattern'] ?? '' }}">Regex: {{ Str::limit($rule->parameters['pattern'] ?? '', 22) }}</span>
                                @elseif($rule->validation_type === 'in_list')
                                    <span>Pilihan: {{ implode(', ', $rule->parameters['options'] ?? []) }}</span>
                                @elseif($rule->validation_type === 'date_format')
                                    <span>Tanggal ({{ $rule->parameters['format'] ?? 'Standar' }})</span>
                                @elseif($rule->validation_type === 'phone')
                                    <span>Format Telepon Indonesia</span>
                                @else
                                    <span class="text-slate-400">Cek Kelengkapan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 max-w-xs text-slate-800">
                                {{ $rule->error_message ?: '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($rule->needs_confirmation)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300" title="{{ $rule->confirmation_note }}">
                                        Perlu Konfirmasi
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[10px]">Terkonfirmasi</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($rule->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('profiles.rules.edit', [$profile, $rule]) }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                    Ubah
                                </a>

                                <form action="{{ route('profiles.rules.toggle-status', [$profile, $rule]) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="font-medium {{ $rule->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }} hover:underline">
                                        {{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
