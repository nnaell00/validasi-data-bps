@extends('layouts.app')

@section('title', 'Tambah Pengguna Baru')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Pengguna Baru</h1>
            <p class="text-sm text-slate-500 mt-0.5">Daftarkan akun Admin atau Petugas BPS ke dalam sistem.</p>
        </div>
        <a href="{{ route('users.index') }}" class="app-button-secondary text-sm">
            &larr; Kembali
        </a>
    </div>

    <div class="app-card p-6 sm:p-8">
        @if($errors->any())
            <div class="p-4 mb-6 text-xs text-red-800 rounded-lg bg-red-50 border border-red-200">
                <div class="font-bold mb-1">Terdapat kesalahan pengisian formulir:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('users.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="app-input"
                    placeholder="Contoh: Budi Santoso, S.Si">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email BPS <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        class="app-input"
                        placeholder="nama@bps.go.id">
                </div>

                <div>
                    <label for="role" class="block text-xs font-semibold text-slate-700 mb-1.5">Peran Hak Akses <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required class="app-input">
                        <option value="petugas" {{ old('role') === 'petugas' ? 'selected' : '' }}>Petugas BPS (Validasi & Perbaikan)</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator (Kelola Sistem & Aturan)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nip" class="block text-xs font-semibold text-slate-700 mb-1.5">NIP (Opsional)</label>
                    <input type="text" id="nip" name="nip" value="{{ old('nip') }}"
                        class="app-input"
                        placeholder="18 digit angka NIP">
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1.5">No. Telepon / WA (Opsional)</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                        class="app-input"
                        placeholder="Contoh: 08123456789">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required
                        class="app-input"
                        placeholder="Minimal 6 karakter">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Kata Sandi <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="app-input"
                        placeholder="Ulangi kata sandi">
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <span class="ml-2 font-medium">Aktifkan akun pengguna ini secara langsung</span>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('users.index') }}" class="app-button-secondary">
                    Batal
                </a>
                <button type="submit" class="app-button-primary">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
