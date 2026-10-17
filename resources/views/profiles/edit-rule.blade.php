@extends('layouts.app')

@section('title', 'Ubah Aturan: ' . $rule->column_name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Ubah Aturan Validasi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kolom: <strong class="text-slate-900">{{ $rule->column_name }}</strong> (Profil: {{ $profile->name }})</p>
        </div>
        <a href="{{ route('profiles.show', $profile) }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 sm:p-8">
        @if($errors->any())
            <div class="p-4 mb-6 text-xs text-red-800 rounded-lg bg-red-50 border border-red-200">
                <div class="font-bold mb-1">Terdapat kesalahan pengisian:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profiles.rules.update', [$profile, $rule]) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4 p-3.5 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                <div>
                    <span class="text-slate-500 block">Indeks Posisi Kolom:</span>
                    <span class="font-bold text-slate-800 font-mono text-sm">#{{ $rule->column_index }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Tipe Validasi:</span>
                    <span class="font-bold text-slate-800 uppercase">{{ $rule->validation_type }}</span>
                </div>
            </div>

            <div>
                <label for="error_message" class="block text-xs font-semibold text-slate-700 mb-1.5">Pesan Kesalahan Anomali <span class="text-red-500">*</span></label>
                <input type="text" id="error_message" name="error_message" value="{{ old('error_message', $rule->error_message) }}" required
                    class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Contoh: NIP harus berupa 18 digit angka valid">
                <p class="text-[11px] text-slate-500 mt-1">Pesan ini akan ditampilkan kepada Petugas BPS saat anomali data terdeteksi.</p>
            </div>

            @if($rule->validation_type === 'regex')
                <div>
                    <label for="pattern" class="block text-xs font-semibold text-slate-700 mb-1.5">Pola Format Regular Expression (Regex)</label>
                    <input type="text" id="pattern" name="pattern" value="{{ old('pattern', $rule->parameters['pattern'] ?? '') }}"
                        class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            @endif

            <div class="pt-1">
                <label class="flex items-center text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_required" value="1" {{ old('is_required', $rule->is_required) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <span class="ml-2 font-medium">Kolom wajib diisi (tidak boleh kosong)</span>
                </label>
                @if($rule->column_name === 'No. WA')
                    <p class="text-[11px] text-emerald-600 mt-0.5 ml-6">Aturan khusus: No. WA sebaiknya tidak dicentang agar nilai kosong tidak dianggap anomali.</p>
                @endif
            </div>

            <div class="p-4 bg-amber-50/60 rounded-lg border border-amber-200/80 space-y-3">
                <label class="flex items-center text-xs text-amber-900 cursor-pointer">
                    <input type="checkbox" name="needs_confirmation" value="1" {{ old('needs_confirmation', $rule->needs_confirmation) ? 'checked' : '' }}
                        class="w-4 h-4 text-amber-600 border-amber-300 rounded focus:ring-amber-500">
                    <span class="ml-2 font-bold">Tandai aturan ini masih perlu dikonfirmasi ke BPS</span>
                </label>

                <div>
                    <label for="confirmation_note" class="block text-xs font-semibold text-amber-900 mb-1">Catatan Konfirmasi BPS</label>
                    <textarea id="confirmation_note" name="confirmation_note" rows="2"
                        class="w-full px-3 py-2 text-xs bg-white border border-amber-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-slate-800"
                        placeholder="Contoh: Perlu kepastian dari Kasubag Umum apakah NIP menggunakan spasi atau murni 18 digit">{{ old('confirmation_note', $rule->confirmation_note) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('profiles.show', $profile) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-xs hover:shadow transition-colors">
                    Simpan Perubahan Aturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
