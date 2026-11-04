@php
    $logoUrl = $shop->getLogoUrl();
    $shopName = $shop->shop_name ?? 'AquaFeed ERP';
@endphp

<div class="flex items-center gap-2 sm:gap-3 overflow-hidden py-1 max-w-full">
    @if($logoUrl)
        <img
            src="{{ $logoUrl }}"
            alt="{{ $shopName }}"
            class="h-8 sm:h-9 w-auto max-w-[65px] sm:max-w-[85px] object-contain rounded-lg shrink-0 shadow-sm transition-all duration-200 hover:scale-105"
        />
    @else
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-tr from-teal-600 via-teal-500 to-cyan-500 flex items-center justify-center text-white shrink-0 shadow-md shadow-teal-500/20">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
        </div>
    @endif

    <div class="flex flex-col text-left min-w-0">
        <span class="font-black text-sm sm:text-base md:text-lg tracking-tight text-slate-900 dark:text-white truncate leading-tight max-w-[150px] sm:max-w-[220px] md:max-w-none">
            {{ $shopName }}
        </span>
    </div>
</div>
