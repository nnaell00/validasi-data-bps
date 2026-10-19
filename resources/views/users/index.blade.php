@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="w-full space-y-5">
    <!-- Header Halaman & Tombol Tambah -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Akun Pengguna</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola kredensial, peran akses, dan status keaktifan staf BPS.</p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('users.create') }}" class="app-button-primary w-full sm:w-auto">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Pengguna Baru
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="app-card p-4">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative w-full min-w-0 sm:flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari berdasarkan nama, email, atau NIP..."
                    class="app-input pl-10">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <div class="w-full shrink-0 sm:w-52">
                <select name="role" class="app-input">
                    <option value="">Semua Peran</option>
                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="petugas" {{ $role === 'petugas' ? 'selected' : '' }}>Petugas BPS</option>
                </select>
            </div>

            <div class="flex w-full shrink-0 items-center gap-2 sm:w-auto">
                <button type="submit" class="w-full rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-900 sm:w-auto">
                    Filter
                </button>
                @if($search || $role)
                    <a href="{{ route('users.index') }}" class="w-full rounded-lg bg-slate-100 px-4 py-2 text-center text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200 sm:w-auto">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="app-card overflow-hidden">
        <div class="app-table-wrap">
            <table class="app-table w-full min-w-[760px] table-fixed text-left">
                <colgroup>
                    <col class="w-[23%]">
                    <col class="w-[27%]">
                    <col class="w-[16%]">
                    <col class="w-[12%]">
                    <col class="w-[22%]">
                </colgroup>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-3 py-3.5 sm:px-4">Nama & NIP</th>
                        <th class="px-3 py-3.5 sm:px-4">Email & Kontak</th>
                        <th class="px-3 py-3.5 sm:px-4">Peran</th>
                        <th class="px-3 py-3.5 sm:px-4">Status</th>
                        <th class="px-3 py-3.5 text-right sm:px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="break-words px-3 py-4 sm:px-4">
                                <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500">NIP: {{ $user->nip ?: '-' }}</div>
                            </td>
                            <td class="break-words px-3 py-4 sm:px-4">
                                <div class="break-all text-slate-900">{{ $user->email }}</div>
                                <div class="text-xs text-slate-500">Telp: {{ $user->phone ?: '-' }}</div>
                            </td>
                            <td class="px-3 py-4 sm:px-4">
                                @if($user->isAdmin())
                                    <span class="inline-flex max-w-full items-center whitespace-normal rounded-full border border-orange-200 bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-orange-800">
                                        Administrator
                                    </span>
                                @else
                                    <span class="inline-flex max-w-full items-center whitespace-normal rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                        Petugas BPS
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-4 sm:px-4">
                                @if($user->is_active)
                                    <span class="inline-flex items-center whitespace-nowrap rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-4 sm:px-4">
                                <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-2">
                                    <a href="{{ route('users.edit', $user) }}" class="inline-flex min-h-7 items-center text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                        Edit
                                    </a>

                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="min-h-7 text-xs font-semibold {{ $user->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }} hover:underline">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="min-h-7 text-xs font-semibold text-red-600 hover:text-red-800 hover:underline">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400 italic">(Akun Anda)</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                Tidak ada data pengguna yang sesuai dengan pencarian Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
