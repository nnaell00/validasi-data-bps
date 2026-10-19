@props(['compact' => false, 'inverse' => false])

<div class="flex items-center gap-3">
    <div class="flex h-14 w-16 shrink-0 items-center justify-center rounded-lg border {{ $inverse ? 'border-white/20 bg-white' : 'border-slate-200 bg-white' }} p-1.5">
        <img src="{{ asset('images/logo-bps.webp') }}" alt="Logo resmi Badan Pusat Statistik" class="h-full w-full object-contain">
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
