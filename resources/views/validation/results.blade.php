@extends('layouts.app')

@section('title', 'Hasil Validasi - ' . $batch->file_name)

@section('content')
<div class="space-y-6">
    <!-- Header Halaman & Aksi Cepat -->
    <div class="app-card flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('batches.index') }}" class="text-blue-600 hover:underline">&larr; Riwayat Validasi</a>
                <span>&bull;</span>
                <span>Sesi #{{ $batch->id }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight flex items-center">
                <span>Hasil Validasi: {{ $batch->file_name }}</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Profil: <strong class="text-slate-800">{{ $batch->profile->name }}</strong> &bull; 
                Total Data: <strong class="text-slate-800">{{ $stats['total_rows'] }} Baris</strong> &bull; 
                Diunggah: {{ $batch->created_at->format('d/m/Y H:i') }} WIB
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('validation.export.data', $batch) }}" class="app-button-success text-xs">
                Ekspor Data Excel
            </a>
            <a href="{{ route('validation.export.report', $batch) }}" class="app-button-primary bg-teal-700 hover:bg-teal-800 focus:ring-teal-700/20 text-xs">
                Ekspor Laporan 2 Sheet
            </a>
            <a href="{{ route('validation.audit', $batch) }}" class="app-button-secondary border-violet-200 bg-violet-50 text-xs text-violet-800 hover:bg-violet-100">
                Audit Trail
            </a>
            <form action="{{ route('validation.revalidate', $batch) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="app-button-primary bg-indigo-700 hover:bg-indigo-800 focus:ring-indigo-700/20 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Validasi Ulang Data</span>
                </button>
            </form>

            <a href="{{ route('batches.preview', $batch) }}" class="app-button-secondary text-xs">
                Pratinjau Asli
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik Metrik Kualitas Data -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Baris -->
        <div class="app-card p-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Baris</span>
            <div class="text-2xl font-black text-slate-800 mt-1">{{ $stats['total_rows'] }}</div>
            <span class="text-xs text-slate-500">Pegawai terdata</span>
        </div>

        <!-- 2. Data Bersih / Valid -->
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Data Bersih (Valid)</span>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['valid_rows'] }}</div>
            <span class="text-xs text-emerald-600 font-medium">
                {{ $stats['total_rows'] > 0 ? round(($stats['valid_rows'] / $stats['total_rows']) * 100, 1) : 0 }}% tingkat kevalidan
            </span>
        </div>

        <!-- 3. Baris Memuat Anomali -->
        <div class="rounded-xl border border-amber-200 bg-amber-50/30 p-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Baris Anomali</span>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ $stats['anomalous_rows'] }}</div>
            <span class="text-xs text-amber-700">Memerlukan koreksi</span>
        </div>

        <!-- 4. Total Kesalahan Anomali -->
        <div class="rounded-xl border border-rose-200 bg-rose-50/30 p-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Total Anomali</span>
            <div class="text-2xl font-black text-rose-600 mt-1">{{ $stats['total_anomalies'] }}</div>
            <span class="text-xs text-rose-600">{{ $stats['total_corrected'] }} baris sudah diperbaiki</span>
        </div>
    </div>

    <!-- Filter & Pencarian Baris -->
    <div class="app-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <!-- Tab Filter Status -->
        <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-lg text-xs font-semibold">
            <a href="{{ route('validation.results', ['batch' => $batch, 'filter' => 'all', 'search' => $search]) }}"
                class="px-3 py-1.5 rounded-md transition-colors {{ $filter === 'all' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                Semua Data ({{ $stats['total_rows'] }})
            </a>
            <a href="{{ route('validation.results', ['batch' => $batch, 'filter' => 'anomalies_only', 'search' => $search]) }}"
                class="px-3 py-1.5 rounded-md transition-colors {{ $filter === 'anomalies_only' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                Anomali ({{ $stats['anomalous_rows'] }})
            </a>
            <a href="{{ route('validation.results', ['batch' => $batch, 'filter' => 'valid_only', 'search' => $search]) }}"
                class="px-3 py-1.5 rounded-md transition-colors {{ $filter === 'valid_only' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                Valid ({{ $stats['valid_rows'] }})
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('validation.results', $batch) }}" class="flex items-center space-x-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIP, NIK..."
                    class="app-input pl-9 py-2 text-xs">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button type="submit" class="app-button-primary py-2 text-xs">
                Cari
            </button>
            @if($search)
                <a href="{{ route('validation.results', ['batch' => $batch, 'filter' => $filter]) }}" class="app-button-secondary py-2 text-xs">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel Rincian Baris & Anomali -->
    <div class="app-card overflow-hidden">
        <div class="app-table-wrap">
            <table class="app-table min-w-[1120px] text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 text-center">Baris</th>
                        <th class="px-4 py-3.5">Nama & NIP</th>
                        <th class="px-4 py-3.5">Pangkat / Golongan</th>
                        <th class="px-4 py-3.5">Status Validasi</th>
                        <th class="px-4 py-3.5">Temuan Anomali & Pesan Kesalahan</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($rows as $row)
                        @php
                            $data = $row->current_data;
                            $nama = $data[1] ?? '-';
                            $nip = $data[2] ?? '-';
                            $pangkat = $data[4] ?? '-';
                            $gol = $data[5] ?? '-';
                            $anomalies = $row->anomalies;
                        @endphp
                        <tr class="hover:bg-slate-50/75 transition-colors {{ $row->has_anomalies ? 'bg-amber-50/20' : '' }}">
                            <td class="px-4 py-4 text-center font-mono font-bold text-slate-400">
                                #{{ $row->row_number }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-slate-900">{{ $nama ?: '(Nama Kosong)' }}</div>
                                <div class="text-[11px] font-mono text-slate-500">NIP: {{ $nip ?: '-' }}</div>
                                @if($row->is_corrected)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 mt-1">
                                        <svg class="w-3 h-3 mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Pernah Dikoreksi
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-slate-800 font-medium">{{ $pangkat ?: '-' }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">Gol: {{ $gol ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if(! $row->has_anomalies)
                                    <span class="app-status-valid">
                                        <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Valid / Bersih
                                    </span>
                                @else
                                    <span class="app-status-error">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                        {{ $anomalies->count() }} Anomali
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 max-w-md">
                                @if($anomalies->isEmpty())
                                    <span class="text-slate-400 italic text-[11px]">Seluruh format kolom memenuhi aturan profil.</span>
                                @else
                                    <div class="space-y-1.5">
                                        @foreach($anomalies as $anomaly)
                                            <div class="p-2 rounded bg-rose-50 border border-rose-200 text-[11px] text-rose-900">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-bold text-rose-950">Kolom [{{ $anomaly->column_name }}]:</span>
                                                    @if($anomaly->needs_confirmation)
                                                        <span class="text-[9px] bg-amber-200 text-amber-900 px-1.5 py-0.2 rounded font-bold" title="{{ $anomaly->confirmation_note }}">
                                                            Perlu Konfirmasi BPS
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-0.5 text-rose-700">{{ $anomaly->error_message }}</div>
                                                <div class="mt-0.5 text-[10px] text-slate-500 font-mono">
                                                    Nilai saat ini: <code class="bg-white px-1 py-0.2 rounded border border-rose-200 font-bold text-rose-900">{{ $anomaly->invalid_value !== null && $anomaly->invalid_value !== '' ? $anomaly->invalid_value : '(KOSONG)' }}</code>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('validation.edit-row', [$batch, $row]) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold rounded-lg shadow-xs hover:shadow transition-colors">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Koreksi Data
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                Tidak ada data yang sesuai dengan kriteria filter saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $rows->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
