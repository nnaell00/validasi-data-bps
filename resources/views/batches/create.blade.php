@extends('layouts.app')

@section('title', 'Unggah Berkas Excel')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Unggah Berkas Excel</h1>
            <p class="text-sm text-slate-500 mt-0.5">Unggah data kepegawaian untuk diperiksa anomali dan divalidasi kualitasnya.</p>
        </div>
        <div>
            <a href="{{ route('batches.dummy-template') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs hover:shadow transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Unduh Data Dummy (Pengujian TA)
            </a>
        </div>
    </div>

    <!-- Alert Edukasi / Petunjuk Pengujian TA -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-900 space-y-1.5">
        <div class="font-bold flex items-center text-amber-950">
            <svg class="w-4 h-4 mr-1.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            Petunjuk Pengujian & Keamanan Data (Sesuai Aturan TA)
        </div>
        <p class="leading-relaxed">
            Gunakan tombol hijau di kanan atas <strong>"Unduh Data Dummy"</strong> untuk mendapatkan file Excel sintetis 20 baris data pegawai BPS (berisi kombinasi data valid dan variasi anomali, termasuk variasi No. WA dan 3 kolom raw). Data asli pegawai BPS tidak boleh diunggah tanpa izin resmi.
        </p>
    </div>

    <!-- Form Upload File -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 sm:p-8">
        @if($errors->any())
            <div class="p-4 mb-6 text-xs text-red-800 rounded-lg bg-red-50 border border-red-200">
                <div class="font-bold mb-1">Gagal mengunggah berkas:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('batches.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="validation_profile_id" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Pilih Profil Validasi Data <span class="text-red-500">*</span>
                </label>
                <select id="validation_profile_id" name="validation_profile_id" required
                    class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $profile->name }} ({{ $profile->rules_count ?? $profile->rules->count() }} Aturan Validasi)
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Sistem akan memeriksa data berdasarkan profil yang dipilih.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Pilih Berkas Spreadsheet Excel (.xlsx / .xls) <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-blue-400 bg-slate-50 transition-colors">
                    <div class="space-y-2 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-slate-600 justify-center">
                            <label for="file" class="relative cursor-pointer bg-white rounded-md font-semibold text-blue-600 hover:text-blue-500 focus-within:outline-none px-2 py-0.5 border border-slate-200">
                                <span>Pilih File Excel</span>
                                <input id="file" name="file" type="file" accept=".xlsx,.xls" required class="sr-only" onchange="updateFileName(this)">
                            </label>
                        </div>
                        <p id="file-name-display" class="text-xs text-slate-500 font-medium">Format: Microsoft Excel (.xlsx atau .xls) hingga 10MB</p>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 rounded-lg border border-slate-200 text-xs text-slate-600 space-y-1">
                <div class="font-bold text-slate-700">Struktur Kolom yang Didukung (20 Kolom):</div>
                <div class="text-[11px] leading-relaxed text-slate-500">
                    No., Nama, NIP, Stat.Jabatan, Pangkat, Gol.Akhir, TMT Gol., Pendidikan (SK), Tgl.Lulus (SK), Jenis Kelamin, Tgl.Lahir, NIK, <em>[3 Kolom Sumber Cadangan]</em>, MK, Pengingat SKS, Pengingat Pensiun, Pengingat KGB, No. WA.
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('batches.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-xs hover:shadow transition-colors flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span>Unggah & Buka Pratinjau</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('file-name-display').innerHTML = '<span class="text-blue-700 font-bold">File terpilih: ' + input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)</span>';
        }
    }
</script>
@endsection
