@extends('layouts.app')

@section('title', 'Riwayat Validasi Data')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman & Tombol Unggah -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Sesi Validasi Data</h1>
            <p class="text-sm text-slate-500 mt-0.5">Daftar berkas Excel yang telah diunggah dan status hasil pemeriksaannya.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('batches.dummy-template') }}" class="app-button-success text-xs">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Unduh Data Dummy
            </a>
            <a href="{{ route('batches.create') }}" class="app-button-primary text-xs">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Unggah Excel Baru
            </a>
        </div>
    </div>

    <!-- Tabel Daftar Batch -->
    <div class="app-card overflow-hidden">
        <div class="app-table-wrap">
            <table class="app-table min-w-[1050px] text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Nama Berkas Excel</th>
                        <th class="px-6 py-3.5">Profil Data</th>
                        <th class="px-6 py-3.5">Diupload Oleh</th>
                        <th class="px-6 py-3.5">Total Baris</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Waktu Unggah</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $batch->file_name }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">ID Sesi: #{{ $batch->id }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $batch->profile->name }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-900 font-medium">{{ $batch->user->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $batch->user->email }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-slate-800">
                                {{ $batch->total_rows }} baris
                            </td>
                            <td class="px-6 py-4">
                                @if($batch->status === 'uploaded')
                                    <span class="app-status-warning">
                                        Belum Divalidasi
                                    </span>
                                @elseif($batch->status === 'validated')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
                                        Sudah Divalidasi
                                    </span>
                                @else
                                    <span class="app-status-valid">
                                        Selesai / Terverifikasi
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                {{ $batch->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <div class="flex flex-wrap justify-end gap-x-3 gap-y-2">
                                <a href="{{ route('batches.preview', $batch) }}" class="inline-flex items-center text-xs font-semibold text-bps-blue hover:text-bps-navy hover:underline">
                                    Pratinjau Data
                                </a>
                                <a href="{{ route('validation.audit', $batch) }}" class="inline-flex items-center text-xs font-semibold text-violet-700 hover:text-violet-900 hover:underline">
                                    Audit Trail
                                </a>
                                @if($batch->status === 'validated' || $batch->status === 'completed')
                                    <a href="{{ route('validation.results', $batch) }}" class="inline-flex items-center text-xs font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                                        Hasil Validasi &rarr;
                                    </a>
                                    <a href="{{ route('validation.export.data', $batch) }}" class="inline-flex items-center text-xs font-semibold text-emerald-700 hover:text-emerald-900 hover:underline">
                                        Ekspor Data
                                    </a>
                                    <a href="{{ route('validation.export.report', $batch) }}" class="inline-flex items-center text-xs font-semibold text-teal-700 hover:text-teal-900 hover:underline">
                                        Ekspor Laporan
                                    </a>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada berkas validasi yang diunggah</p>
                                <p class="text-xs text-slate-500 mt-1">Mulai dengan mengunduh template data dummy dan mengunggahnya untuk pengujian.</p>
                                <div class="mt-4 flex items-center justify-center space-x-3">
                                    <a href="{{ route('batches.dummy-template') }}" class="px-3.5 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700">
                                        Unduh Data Dummy
                                    </a>
                                    <a href="{{ route('batches.create') }}" class="px-3.5 py-1.5 bg-blue-700 text-white rounded-lg text-xs font-semibold hover:bg-blue-800">
                                        Unggah Berkas Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
