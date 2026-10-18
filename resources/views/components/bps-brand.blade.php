@props(['compact' => false, 'inverse' => false])

<div class="flex items-center gap-3">
    <div class="flex h-14 w-16 shrink-0 items-center justify-center rounded-lg border {{ $inverse ? 'border-white/20 bg-white' : 'border-slate-200 bg-white' }} p-1.5">
        @if(file_exists(public_path('images/logo-bps.png')))
            <img src="{{ asset('images/logo-bps.png') }}" alt="Logo resmi Badan Pusat Statistik" class="max-h-full max-w-full object-contain">
        @else
            <div class="flex h-full w-full flex-col items-center justify-center gap-0.5 rounded border border-dashed border-slate-300 text-center text-[8px] font-semibold leading-tight text-slate-500">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.5"/>
                    <circle cx="8.5" cy="9" r="1.5" stroke-width="1.5"/>
                    <path d="m21 15-5-5L5 20" stroke-width="1.5"/>
                </svg>
                <span>Logo resmi<br>BPS</span>
            </div>
        @endif
    </div>
    <div class="min-w-0">
        <p class="text-xs font-semibold leading-5 {{ $inverse ? 'text-blue-100' : 'text-slate-500' }}">
            Badan Pusat Statistik<br class="{{ $compact ? 'hidden sm:block' : '' }}"> Kabupaten Purwakarta
        </p>
        <p class="mt-0.5 text-sm font-semibold tracking-tight {{ $inverse ? 'text-white' : 'text-slate-900' }}">
            Sistem Validasi Data
        </p>
    </div>
</div>
