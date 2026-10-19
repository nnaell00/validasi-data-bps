@extends('layouts.app')

@section('title', 'Audit Trail Sesi #' . $batch->id)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                <a href="{{ route('batches.index') }}" class="text-blue-600 hover:underline">&larr; Riwayat Validasi</a>
                <span>&bull;</span>
                <a href="{{ route('validation.results', $batch) }}" class="text-blue-600 hover:underline">Hasil Sesi #{{ $batch->id }}</a>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Riwayat Perubahan</h1>
            <p class="mt-1 text-sm text-slate-500">Sesi #{{ $batch->id }} &middot; {{ $batch->file_name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('validation.export.data', $batch) }}" class="app-button-success text-xs">
                Ekspor Data Excel
            </a>
            <a href="{{ route('validation.export.report', $batch) }}" class="app-button-primary bg-teal-700 hover:bg-teal-800 focus:ring-teal-700/20 text-xs">
                Ekspor Laporan 2 Sheet
            </a>
        </div>
    </div>

    <div class="app-card overflow-hidden">
        <div class="app-table-wrap">
            <table class="app-table min-w-[1100px] text-left text-xs">
                <thead class="bg-slate-50 font-semibold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-4 py-3.5">Waktu</th>
                        <th class="px-4 py-3.5">Petugas</th>
                        <th class="px-4 py-3.5">Baris</th>
                        <th class="px-4 py-3.5">Nama Kolom</th>
                        <th class="px-4 py-3.5">Nilai Sebelum</th>
                        <th class="px-4 py-3.5">Nilai Sesudah</th>
                        <th class="px-4 py-3.5">Aktivitas / Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($auditLogs as $auditLog)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $auditLog->created_at->copy()->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }} WIB</td>
                            <td class="px-4 py-4 font-medium text-slate-900">{{ $auditLog->user?->name ?? '-' }}</td>
                            <td class="px-4 py-4">{{ $auditLog->row ? '#'.$auditLog->row->row_number : '-' }}</td>
                            <td class="px-4 py-4 font-semibold">{{ $auditLog->column_name ?? '-' }}</td>
                            <td class="max-w-xs break-all px-4 py-4 font-mono">{{ $auditLog->old_value ?? '-' }}</td>
                            <td class="max-w-xs break-all px-4 py-4 font-mono">{{ $auditLog->new_value ?? '-' }}</td>
                            <td class="max-w-sm px-4 py-4">
                                <span class="font-semibold">{{ ucfirst($auditLog->action) }}</span>
                                @if($auditLog->notes)
                                    <div class="mt-1 text-slate-500">{{ $auditLog->notes }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <p class="font-semibold text-slate-700">Belum ada aktivitas pada sesi ini.</p>
                                <p class="mt-1 text-slate-500">Perubahan akan tercatat setelah petugas menyimpan koreksi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($auditLogs->hasPages())
            <div class="border-t border-slate-200 p-4">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
