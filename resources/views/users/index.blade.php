@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman & Tombol Tambah -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Akun Pengguna</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola kredensial, peran akses, dan status keaktifan staf BPS.</p>
        </div>
        <div>
            <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-xs hover:shadow transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Pengguna Baru
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-xs">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari berdasarkan nama, email, atau NIP..."
                    class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <div class="w-full sm:w-48">
                <select name="role" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Peran</option>
                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="petugas" {{ $role === 'petugas' ? 'selected' : '' }}>Petugas BPS</option>
                </select>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-lg transition-colors">
                    Filter
                </button>
                @if($search || $role)
                    <a href="{{ route('users.index') }}" class="w-full sm:w-auto text-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Nama & NIP</th>
                        <th class="px-6 py-3.5">Email & Kontak</th>
                        <th class="px-6 py-3.5">Peran</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500">NIP: {{ $user->nip ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-900">{{ $user->email }}</div>
                                <div class="text-xs text-slate-500">Telp: {{ $user->phone ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->isAdmin())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                        Administrator
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                        Petugas BPS
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($user->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                    Edit
                                </a>

                                @if($user->id !== auth()->id())
                                    <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs font-medium {{ $user->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }} hover:underline">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800 hover:underline">
                                            Hapus
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400 italic">(Akun Anda)</span>
                                @endif
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
