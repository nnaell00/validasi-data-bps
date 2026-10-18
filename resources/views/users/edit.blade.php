@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Data Pengguna</h1>
            <p class="text-sm text-slate-500 mt-0.5">Perbarui informasi profil atau peran untuk {{ $user->name }}.</p>
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

        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                    class="app-input">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email BPS <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="app-input">
                </div>

                <div>
                    <label for="role" class="block text-xs font-semibold text-slate-700 mb-1.5">Peran Hak Akses <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required class="app-input">
                        <option value="petugas" {{ old('role', $user->role) === 'petugas' ? 'selected' : '' }}>Petugas BPS (Validasi & Perbaikan)</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrator (Kelola Sistem & Aturan)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nip" class="block text-xs font-semibold text-slate-700 mb-1.5">NIP</label>
                    <input type="text" id="nip" name="nip" value="{{ old('nip', $user->nip) }}"
                        class="app-input">
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1.5">No. Telepon / WA</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="app-input">
                </div>
            </div>

            <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                <span class="block text-xs font-bold text-slate-700 mb-1">Ganti Kata Sandi (Opsional)</span>
                <p class="text-[11px] text-slate-500 mb-3">Biarkan kosong jika tidak ingin mengubah kata sandi pengguna ini.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <input type="password" id="password" name="password"
                            class="app-input"
                            placeholder="Kata sandi baru">
                    </div>
                    <div>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            class="app-input"
                            placeholder="Konfirmasi sandi baru">
                    </div>
                </div>
            </div>

            <div class="pt-1">
                <label class="flex items-center text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                        {{ $user->id === auth()->id() ? 'disabled' : '' }}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <span class="ml-2 font-medium">Akun aktif (dapat login ke sistem)</span>
                </label>
                @if($user->id === auth()->id())
                    <p class="text-[11px] text-slate-400 mt-1 italic">Anda tidak dapat menonaktifkan akun sendiri saat sedang login.</p>
                @endif
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('users.index') }}" class="app-button-secondary">
                    Batal
                </a>
                <button type="submit" class="app-button-primary">
                    Perbarui Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
