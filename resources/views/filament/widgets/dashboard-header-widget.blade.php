<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-3xl p-5 sm:p-6 md:p-8 text-white shadow-xl border border-teal-500/20"
         style="background: radial-gradient(circle at 15% 20%, rgba(20, 184, 166, 0.22) 0%, transparent 45%), radial-gradient(circle at 85% 85%, rgba(14, 165, 233, 0.18) 0%, transparent 45%), linear-gradient(135deg, #06101e 0%, #0c1a2e 50%, #062b3b 100%);">

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            {{-- Left Column: Store & User Greeting --}}
            <div class="space-y-3">
                {{-- Badges & Status Row --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 shadow-sm shadow-emerald-500/10 shrink-0">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                        </span>
                        {{ __('Live Hub Sync') }}
                    </span>

                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-slate-800/80 text-teal-200 border border-teal-500/30 backdrop-blur-sm">
                        <x-heroicon-m-building-storefront class="w-3.5 h-3.5 text-teal-400 shrink-0" />
                        <span>{{ __('Khulna Central Depot (রূপসা ঘাট)') }}</span>
                    </span>

                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-slate-800/60 text-slate-300 border border-slate-700/60 backdrop-blur-sm shrink-0">
                        <x-heroicon-m-calendar-days class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <span>{{ $dateFormatted }}</span>
                    </span>
                </div>

                {{-- Greeting & Role --}}
                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight text-white flex flex-wrap items-center gap-2.5">
                        <span>{{ __('স্বাগতম,') }} {{ $user?->name ?? __('User') }}</span>
                        @if($isAdmin)
                            <span class="inline-flex items-center gap-1 text-xs font-extrabold px-3 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 shadow-sm shadow-amber-500/20 border border-amber-300 shrink-0">
                                <x-heroicon-s-sparkles class="w-3.5 h-3.5 text-slate-950" />
                                {{ __('Admin') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-0.5 rounded-full bg-sky-500/20 text-sky-200 border border-sky-400/30 shrink-0">
                                <x-heroicon-m-eye class="w-3.5 h-3.5 text-sky-300" />
                                {{ __('Viewer') }}
                            </span>
                        @endif
                    </h1>

                    {{-- Uploaded Store Branding (Logo, Name, Tagline) --}}
                    <div class="flex items-center gap-3 pt-1">
                        @if($shop->getLogoUrl())
                            <img src="{{ $shop->getLogoUrl() }}" alt="{{ $shop->shop_name }}" class="h-8 w-auto max-w-[65px] sm:max-w-[75px] object-contain rounded bg-white/10 p-0.5 border border-white/20 shrink-0">
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2">
                                <span class="font-extrabold text-teal-300 tracking-wide text-sm sm:text-base">{{ $shop->shop_name }}</span>
                                @if($shop->shop_tagline)
                                    <span class="text-slate-500 hidden md:inline">·</span>
                                    <span class="text-xs sm:text-sm text-slate-300 block md:inline">{{ $shop->shop_tagline }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Quick Action CTA Group --}}
            <div class="flex flex-wrap items-center gap-2.5">
                @if($isAdmin)
                    <a href="/admin/sales/create"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl font-bold text-sm bg-gradient-to-r from-orange-500 via-amber-500 to-orange-500 hover:from-orange-600 hover:to-amber-600 text-white shadow-lg shadow-orange-500/25 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        <x-heroicon-m-plus-circle class="w-5 h-5 text-white" />
                        <span>{{ __('+ New Sale (POS)') }}</span>
                    </a>

                    <a href="/admin/purchases/create"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl font-semibold text-sm bg-slate-900/80 hover:bg-slate-800 text-slate-100 border border-slate-700/80 hover:border-teal-500/40 backdrop-blur-md shadow-sm transition-all duration-200 hover:scale-[1.02]">
                        <x-heroicon-m-truck class="w-4 h-4 text-teal-400" />
                        <span>{{ __('+ Purchase (ক্রয়)') }}</span>
                    </a>

                    <a href="/admin/payment-collections/create"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl font-semibold text-sm bg-slate-900/80 hover:bg-slate-800 text-slate-100 border border-slate-700/80 hover:border-emerald-500/40 backdrop-blur-md shadow-sm transition-all duration-200 hover:scale-[1.02]">
                        <x-heroicon-m-banknotes class="w-4 h-4 text-emerald-400" />
                        <span>{{ __('Collect Due (আদায়)') }}</span>
                    </a>
                @endif

                <a href="/admin/executive-summary-report"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl font-semibold text-sm bg-teal-950/40 hover:bg-teal-900/50 text-teal-200 border border-teal-500/40 backdrop-blur-md shadow-sm transition-all duration-200 hover:scale-[1.02]">
                    <x-heroicon-m-presentation-chart-line class="w-4 h-4 text-cyan-400" />
                    <span>{{ __('Executive P&L') }}</span>
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>