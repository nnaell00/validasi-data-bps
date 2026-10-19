@php
    $navigationItems = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'active' => ['dashboard'],
            'icon' => 'dashboard',
        ],
        [
            'label' => 'Unggah Excel',
            'route' => 'batches.create',
            'active' => ['batches.create'],
            'icon' => 'upload',
        ],
        [
            'label' => 'Riwayat Validasi',
            'route' => 'batches.index',
            'active' => ['batches.index', 'batches.preview', 'validation.*'],
            'icon' => 'history',
        ],
    ];

    if (auth()->user()->isAdmin()) {
        $navigationItems[] = [
            'label' => 'Profil & Aturan',
            'route' => 'profiles.index',
            'active' => ['profiles.*'],
            'icon' => 'rules',
        ];
        $navigationItems[] = [
            'label' => 'Pengguna',
            'route' => 'users.index',
            'active' => ['users.*'],
            'icon' => 'users',
        ];
    }
@endphp

<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col overflow-y-auto bg-bps-navy text-white shadow-xl transition-[transform,width] duration-200" aria-label="Navigasi utama">
    <div class="flex min-h-20 items-center justify-between gap-2 border-b border-white/10 px-4">
        <a href="{{ route('dashboard') }}" class="sidebar-brand min-w-0 flex-1 rounded-lg p-1 focus:outline-none focus:ring-2 focus:ring-white/70" aria-label="Sistem Validasi Data BPS Kabupaten Purwakarta">
            <x-bps-brand inverse />
        </a>

        <button id="desktop-sidebar-toggle" type="button" class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg text-blue-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/70 lg:flex" aria-label="Perkecil sidebar" aria-expanded="true" title="Perkecil sidebar">
            <svg class="h-5 w-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14 6-6 6 6 6"/>
            </svg>
        </button>

        <button id="mobile-sidebar-close" type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-blue-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/70 lg:hidden" aria-label="Tutup navigasi">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-5" aria-label="Menu utama">
        <p class="sidebar-section-label px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-blue-200/70">Menu Utama</p>

        @foreach($navigationItems as $item)
            @php($isActive = request()->routeIs(...$item['active']))
            <a href="{{ route($item['route']) }}" class="sidebar-link {{ $isActive ? 'sidebar-link-active' : '' }}" title="{{ $item['label'] }}" @if($isActive) aria-current="page" @endif>
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    @switch($item['icon'])
                        @case('dashboard')
                            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke-width="1.7"/>
                            <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke-width="1.7"/>
                            <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke-width="1.7"/>
                            <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke-width="1.7"/>
                            @break
                        @case('upload')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 15V3m0 0L7.5 7.5M12 3l4.5 4.5M5 14v4.5A2.5 2.5 0 0 0 7.5 21h9a2.5 2.5 0 0 0 2.5-2.5V14"/>
                            @break
                        @case('history')
                            <circle cx="12" cy="12" r="8.5" stroke-width="1.7"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 7v5l3.2 2"/>
                            @break
                        @case('rules')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M5 6h14M5 12h14M5 18h14"/>
                            <circle cx="9" cy="6" r="2" fill="#003660" stroke-width="1.7"/>
                            <circle cx="15" cy="12" r="2" fill="#003660" stroke-width="1.7"/>
                            <circle cx="10" cy="18" r="2" fill="#003660" stroke-width="1.7"/>
                            @break
                        @case('users')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20m17 0v-1.5a4 4 0 0 0-3-3.87M14 3.2a4 4 0 0 1 0 7.6"/>
                            <circle cx="9.5" cy="7.5" r="4" stroke-width="1.7"/>
                            @break
                    @endswitch
                </svg>
                <span class="sidebar-link-label truncate">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        <div class="sidebar-account mb-3 flex min-w-0 items-center gap-3 rounded-lg px-3 py-2">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white" aria-hidden="true">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="sidebar-account-copy min-w-0">
                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                <p class="text-xs text-blue-200">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Petugas BPS' }}</p>
            </div>
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sidebar-logout flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-sm font-medium text-blue-100 transition hover:bg-rose-500/15 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/70">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 17l5-5-5-5m5 5H3m9-8h5a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-5"/>
                </svg>
                <span class="sidebar-link-label">Keluar</span>
            </button>
        </form>
    </div>
</aside>
