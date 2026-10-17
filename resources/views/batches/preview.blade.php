@extends('layouts.app')

@section('title', 'Pratinjau Data Mentah - ' . $batch->file_name)

@section('content')
<div class="space-y-6">
    <!-- Header Halaman & Aksi Validasi -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('batches.index') }}" class="text-blue-600 hover:underline">&larr; Riwayat Validasi</a>
                <span>&bull;</span>
                <span>Unggahan #{{ $batch->id }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight flex items-center">
                <span>Pratinjau Data Mentah: {{ $batch->file_name }}</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Profil: <strong class="text-slate-800">{{ $batch->profile->name }}</strong> &bull; 
                Total Data: <strong class="text-slate-800">{{ $batch->total_rows }} Baris Pegawai</strong> &bull; 
                Diupload oleh: <strong class="text-slate-800">{{ $batch->user->name }}</strong> &bull; 
                Waktu: {{ $batch->created_at->format('d M Y H:i') }} WIB
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('batches.create') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors">
                Unggah File Lain
            </a>
            <form action="{{ route('validation.execute', $batch) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-colors flex items-center space-x-2">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Jalankan Validasi Sekarang</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Banner Catatan Integritas Data -->
    <div class="p-4 bg-sky-50 border border-sky-200 rounded-xl text-xs text-sky-900 flex items-start space-x-3">
        <svg class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div class="font-bold text-sky-950 mb-0.5">Integritas Data Sumber Terjaga</div>
            <p class="leading-relaxed">
                Tabel di bawah adalah <strong>data mentah asli</strong> dari file Excel. Sistem tidak pernah mengubah berkas sumber secara otomatis. Tiga kolom cadangan yang belum diketahui namanya dipertahankan utuh. Klik tombol <strong>"Jalankan Validasi Sekarang"</strong> untuk memulai pendeteksian anomali.
            </p>
        </div>
    </div>

    <!-- Tabel Pratinjau Data Mentah -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Pratinjau Isi Spreadsheet (Menampilkan {{ $rows->count() }} dari {{ $batch->total_rows }} Baris)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-100/80 text-slate-700 font-semibold uppercase tracking-wider whitespace-nowrap">
                    <tr>
                        <th class="px-3 py-3 border-r border-slate-200 bg-slate-200/60 sticky left-0 z-10 text-center">Baris</th>
                        @foreach($batch->headers ?? [] as $header)
                            <th class="px-3 py-3 border-r border-slate-200 {{ in_array($header, ['Kolom_Raw_1', 'Kolom_Raw_2', 'Kolom_Raw_3']) ? 'bg-amber-50 text-amber-900' : '' }}">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 whitespace-nowrap">
                    @foreach($rows as $row)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-3 py-2.5 font-mono text-center font-bold text-slate-500 bg-slate-50 border-r border-slate-200 sticky left-0 z-10">
                                {{ $row->row_number }}
                            </td>
                            @foreach($row->current_data as $colIdx => $val)
                                <td class="px-3 py-2.5 border-r border-slate-100 font-mono text-[11px]">
                                    @if($val === null || $val === '')
                                        <span class="text-slate-300 italic">(kosong)</span>
                                    @else
                                        {{ $val }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
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
