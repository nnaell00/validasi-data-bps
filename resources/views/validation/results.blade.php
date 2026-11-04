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
                Diunggah: {{ $batch->created_at->copy()->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }} WIB
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

    @if($informationSummary)
        <section class="space-y-4" aria-labelledby="employee-summary-heading">
            <div>
                <h2 id="employee-summary-heading" class="text-lg font-bold text-slate-800">
                    Ringkasan Informasi Data Kepegawaian
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Ringkasan karakteristik dihitung dari {{ $informationSummary['total_rows'] }} baris data terbaru yang tersimpan.
                    Koreksi langsung tercermin pada distribusi; status valid dan anomali mengikuti hasil validasi terakhir.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="app-card p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Pegawai / Baris</span>
                    <div class="mt-1 text-2xl font-black text-slate-800">{{ $informationSummary['total_rows'] }}</div>
                    <span class="text-xs text-slate-500">Baris data yang berhasil dibaca</span>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Baris Valid</span>
                    @if(in_array($batch->status, ['validated', 'completed'], true))
                        <div class="mt-1 text-2xl font-black text-emerald-700">{{ $stats['valid_rows'] }}</div>
                        <span class="text-xs font-medium text-emerald-700">
                            {{ $stats['total_rows'] > 0 ? round(($stats['valid_rows'] / $stats['total_rows']) * 100, 1) : 0 }}% dari baris data
                        </span>
                    @else
                        <div class="mt-1 text-lg font-bold text-slate-500">Belum divalidasi</div>
                        <span class="text-xs text-slate-500">Jalankan validasi untuk mendapatkan jumlah</span>
                    @endif
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/30 p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Baris Anomali</span>
                    @if(in_array($batch->status, ['validated', 'completed'], true))
                        <div class="mt-1 text-2xl font-black text-amber-700">{{ $stats['anomalous_rows'] }}</div>
                        <span class="text-xs text-amber-800">
                            {{ $stats['total_anomalies'] }} temuan belum selesai; satu baris dapat memiliki beberapa temuan
                        </span>
                    @else
                        <div class="mt-1 text-lg font-bold text-slate-500">Belum diketahui</div>
                        <span class="text-xs text-slate-500">Jalankan validasi untuk menemukan anomali</span>
                    @endif
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Mendekati Usia Pensiun</span>
                    <div class="mt-1 text-lg font-bold text-blue-900">Tidak dihitung</div>
                    <span class="text-xs text-blue-800">{{ $informationSummary['retirement']['message'] }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="app-card p-4">
                    <h3 class="text-sm font-bold text-slate-800">Distribusi Jenis Kelamin</h3>
                    @if(! $informationSummary['gender']['available'])
                        <p class="mt-2 text-xs text-slate-500">Kolom jenis kelamin tidak tersedia pada file ini.</p>
                    @else
                        @if($informationSummary['gender']['classified_count'] > 0)
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500">Laki-laki</div>
                                    <div class="text-xl font-bold text-slate-800">
                                        {{ $informationSummary['gender']['male'] }}
                                        <span class="text-xs font-medium text-slate-500">({{ number_format($informationSummary['gender']['male_percentage'], 1, ',', '.') }}%)</span>
                                    </div>
                                    @if($informationSummary['gender']['male_values'])
                                        <div class="mt-1 text-[11px] text-slate-500">
                                            Nilai: {{ implode(', ', array_column($informationSummary['gender']['male_values'], 'label')) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500">Perempuan</div>
                                    <div class="text-xl font-bold text-slate-800">
                                        {{ $informationSummary['gender']['female'] }}
                                        <span class="text-xs font-medium text-slate-500">({{ number_format($informationSummary['gender']['female_percentage'], 1, ',', '.') }}%)</span>
                                    </div>
                                    @if($informationSummary['gender']['female_values'])
                                        <div class="mt-1 text-[11px] text-slate-500">
                                            Nilai: {{ implode(', ', array_column($informationSummary['gender']['female_values'], 'label')) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <p class="mt-2 text-xs text-slate-500">Tidak ada nilai yang dapat dikelompokkan menggunakan kategori pada profil.</p>
                        @endif
                        @if($informationSummary['gender']['unrecognized_categories'])
                            <div class="mt-3 text-xs text-amber-800">
                                Kategori tidak dikenali:
                                @foreach($informationSummary['gender']['unrecognized_categories'] as $category)
                                    <span class="font-semibold">{{ $category['label'] }} ({{ $category['count'] }})</span>{{ ! $loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                        @endif
                        <p class="mt-2 text-[11px] text-slate-500">
                            Persentase dihitung dari {{ $informationSummary['gender']['classified_count'] }} nilai yang dikenali.
                            Nilai kosong: {{ $informationSummary['gender']['missing_count'] }}.
                        </p>
                    @endif
                </div>

                <div class="app-card p-4">
                    <h3 class="text-sm font-bold text-slate-800">Distribusi Golongan</h3>
                    @if(! $informationSummary['rank']['available'])
                        <p class="mt-2 text-xs text-slate-500">Kolom golongan akhir tidak tersedia pada file ini.</p>
                    @elseif($informationSummary['rank']['categories'])
                        <div class="mt-3 max-h-40 overflow-y-auto pr-1 grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3">
                            @foreach($informationSummary['rank']['categories'] as $category)
                                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-1 text-xs">
                                    <span class="truncate text-slate-600" title="{{ $category['label'] }}">{{ $category['label'] }}</span>
                                    <span class="font-bold text-slate-800">{{ $category['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">Nilai kosong: {{ $informationSummary['rank']['missing_count'] }}.</p>
                    @else
                        <p class="mt-2 text-xs text-slate-500">Kolom ditemukan, tetapi seluruh {{ $informationSummary['rank']['missing_count'] }} nilainya kosong.</p>
                    @endif
                </div>

                <div class="app-card p-4">
                    <h3 class="text-sm font-bold text-slate-800">Distribusi Pendidikan</h3>
                    @if(! $informationSummary['education']['available'])
                        <p class="mt-2 text-xs text-slate-500">Kolom pendidikan tidak tersedia pada file ini.</p>
                    @elseif($informationSummary['education']['categories'])
                        <div class="mt-3 max-h-40 overflow-y-auto pr-1 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                            @foreach($informationSummary['education']['categories'] as $category)
                                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-1 text-xs">
                                    <span class="truncate text-slate-600" title="{{ $category['label'] }}">{{ $category['label'] }}</span>
                                    <span class="font-bold text-slate-800">{{ $category['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">Nilai kosong: {{ $informationSummary['education']['missing_count'] }}.</p>
                    @else
                        <p class="mt-2 text-xs text-slate-500">Kolom ditemukan, tetapi seluruh {{ $informationSummary['education']['missing_count'] }} nilainya kosong.</p>
                    @endif
                </div>

                <div class="app-card p-4">
                    <h3 class="text-sm font-bold text-slate-800">Ringkasan Usia dan Masa Kerja</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-semibold text-slate-600">Usia</div>
                            @if(! $informationSummary['age']['available'])
                                <p class="mt-1 text-xs text-slate-500">Kolom tanggal lahir tidak tersedia.</p>
                            @elseif($informationSummary['age']['count'] === 0)
                                <p class="mt-1 text-xs text-slate-500">Tidak ada tanggal lahir yang dapat dibaca.</p>
                            @else
                                <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($informationSummary['age']['average'], 1, ',', '.') }} tahun</p>
                                <p class="text-[11px] text-slate-500">Rata-rata dari {{ $informationSummary['age']['count'] }} tanggal lahir yang valid.</p>
                                <div class="mt-2 space-y-1">
                                    @foreach($informationSummary['age']['groups'] as $label => $count)
                                        <div class="flex justify-between text-[11px] text-slate-600">
                                            <span>{{ $label }} tahun</span>
                                            <span class="font-semibold">{{ $count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">Kosong/tidak terbaca: {{ $informationSummary['age']['unreadable_count'] }}.</p>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-600">Masa Kerja</div>
                            @if(! $informationSummary['tenure']['available'])
                                <p class="mt-1 text-xs text-slate-500">Kolom masa kerja tidak tersedia.</p>
                            @elseif($informationSummary['tenure']['count'] === 0)
                                <p class="mt-1 text-xs text-slate-500">Tidak ada nilai masa kerja dengan format tahun/bulan yang dapat dibaca.</p>
                            @else
                                <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($informationSummary['tenure']['average_years'], 1, ',', '.') }} tahun</p>
                                <p class="text-[11px] text-slate-500">Rata-rata dari {{ $informationSummary['tenure']['count'] }} nilai dengan satuan eksplisit.</p>
                                <p class="mt-2 text-[11px] text-slate-500">Kosong/tidak terbaca: {{ $informationSummary['tenure']['unreadable_count'] }}.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-500">
                Distribusi memakai data terbaru setelah koreksi. Jumlah baris valid/anomali adalah hasil validasi tersimpan; jalankan validasi ulang setelah koreksi untuk memperbaruinya.
            </p>
        </section>
    @else
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="app-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Baris</span>
                <div class="mt-1 text-2xl font-black text-slate-800">{{ $stats['total_rows'] }}</div>
                <span class="text-xs text-slate-500">Pegawai terdata</span>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Data Bersih (Valid)</span>
                <div class="mt-1 text-2xl font-black text-emerald-600">{{ $stats['valid_rows'] }}</div>
                <span class="text-xs font-medium text-emerald-600">
                    {{ $stats['total_rows'] > 0 ? round(($stats['valid_rows'] / $stats['total_rows']) * 100, 1) : 0 }}% tingkat kevalidan
                </span>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/30 p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Baris Anomali</span>
                <div class="mt-1 text-2xl font-black text-amber-600">{{ $stats['anomalous_rows'] }}</div>
                <span class="text-xs text-amber-700">Memerlukan koreksi</span>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50/30 p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Total Anomali</span>
                <div class="mt-1 text-2xl font-black text-rose-600">{{ $stats['total_anomalies'] }}</div>
                <span class="text-xs text-rose-600">{{ $stats['total_corrected'] }} baris sudah diperbaiki</span>
            </div>
        </div>
    @endif

    <h2 class="text-base font-bold text-slate-800">Rincian Hasil Validasi</h2>

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
                            $hasAnomalies = $anomalies->isNotEmpty();
                        @endphp
                        <tr class="hover:bg-slate-50/75 transition-colors {{ $hasAnomalies ? 'bg-amber-50/20' : '' }}">
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
                                @if(! $hasAnomalies)
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
