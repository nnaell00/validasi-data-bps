@extends('layouts.app')

@section('title', 'Koreksi Data - Baris #' . $row->row_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('validation.results', $batch) }}" class="text-blue-600 hover:underline">&larr; Kembali ke Hasil Validasi</a>
                <span>&bull;</span>
                <span>Berkas: {{ $batch->file_name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
                Koreksi Data Pegawai (Baris #{{ $row->row_number }})
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Perbaiki nilai data yang mengalami anomali. Seluruh riwayat perubahan akan dicatat ke audit log.
            </p>
        </div>
        <a href="{{ route('validation.results', $batch) }}" class="app-button-secondary text-xs">
            Kembali
        </a>
    </div>

    <!-- Alert Integritas Sumber -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900 flex items-start space-x-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div class="font-bold text-blue-950 mb-0.5">Prinsip Keamanan & Integritas Data TA</div>
            <p class="leading-relaxed">
                Data sumber asli tetap diarsipkan utuh pada sistem. Kolom dengan tanda merah menunjukkan kesalahan/anomali yang terdeteksi oleh mesin validasi. Setelah dikoreksi, silakan jalankan <strong>Validasi Ulang</strong>.
            </p>
        </div>
    </div>

    <!-- Form Koreksi Baris Data -->
    <div class="app-card p-6 sm:p-8">
        <form action="{{ route('validation.update-row', [$batch, $row]) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($batch->headers ?? [] as $colIndex => $headerName)
                    @php
                        $hasAnomaly = isset($anomaliesByColIndex[$colIndex]);
                        $isRawCol = in_array((int) $colIndex, \App\Models\ValidationBatch::PRESERVED_SOURCE_COLUMN_INDICES, true);
                        $currentVal = $isRawCol ? ($row->original_data[$colIndex] ?? '') : ($row->current_data[$colIndex] ?? '');
                        $originalVal = $row->original_data[$colIndex] ?? '';
                    @endphp

                    <div class="p-3.5 rounded-xl border {{ $hasAnomaly ? 'border-rose-300 bg-rose-50/30 ring-1 ring-rose-200' : 'border-slate-200 bg-slate-50/50' }} transition-colors">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="col_{{ $colIndex }}" class="block text-xs font-bold {{ $hasAnomaly ? 'text-rose-900' : 'text-slate-700' }}">
                                [{{ $colIndex }}] {{ $headerName }}
                            </label>

                            @if($isRawCol)
                                <span class="text-[10px] text-slate-500 italic">Kolom sumber (dikunci)</span>
                            @elseif($hasAnomaly)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                    Anomali
                                </span>
                            @elseif($headerName === 'No. WA')
                                <span class="text-[10px] text-emerald-600 font-medium">Opsional</span>
                            @endif
                        </div>

                        <!-- Input Nilai Terkini -->
                        <div>
                            <input type="text" id="col_{{ $colIndex }}" name="data[{{ $colIndex }}]" value="{{ old("data.{$colIndex}", $currentVal) }}"
                                {{ $isRawCol ? 'readonly' : '' }}
                                class="app-input font-mono text-xs {{ $hasAnomaly ? 'border-rose-400 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10' : '' }} {{ $isRawCol ? 'cursor-not-allowed bg-slate-100' : '' }}">
                        </div>

                        <!-- Pesan Anomali Jika Ada -->
                        @if($hasAnomaly)
                            <div class="mt-2 space-y-1">
                                @foreach($anomaliesByColIndex[$colIndex] as $anom)
                                    <div class="text-[11px] text-rose-700 flex items-start space-x-1">
                                        <span class="font-bold text-rose-600">&bull;</span>
                                        <span>{{ $anom->error_message }}</span>
                                    </div>
                                    @if($anom->needs_confirmation)
                                        <div class="text-[10px] text-amber-800 bg-amber-50 p-1.5 rounded border border-amber-200 mt-1">
                                            <strong>Catatan Konfirmasi BPS:</strong> {{ $anom->confirmation_note ?: 'Format perlu dikonfirmasi.' }}
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <!-- Nilai Asli Sumber (Readonly Info) -->
                        <div class="mt-2 pt-1.5 border-t border-slate-200/60 text-[10px] text-slate-400 flex items-center justify-between">
                            <span>Nilai Asli Sumber:</span>
                            <span class="font-mono text-slate-600">{{ $originalVal !== null && $originalVal !== '' ? $originalVal : '(KOSONG)' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Tombol Simpan & Batal -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-200">
                <a href="{{ route('validation.results', $batch) }}" class="app-button-secondary text-xs">
                    &larr; Batal & Kembali
                </a>

                <button type="submit" class="app-button-primary text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Perubahan & Catat Audit</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
