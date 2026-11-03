<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2.5">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                </span>
                <span class="font-extrabold text-base tracking-tight text-slate-900 dark:text-white">
                    {{ __('Urgent Low-Stock Alerts (মজুদ সতর্কতা)') }}
                </span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            @if($criticalCount > 0)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    {{ $criticalCount }} {{ __('SKUs Critical') }}
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                    <x-heroicon-m-check class="w-3.5 h-3.5" />
                    {{ __('Stock Normal') }}
                </span>
            @endif
        </x-slot>

        <div class="space-y-3">
            @forelse($lowStockItems as $product)
                @php
                    $threshold = $product->reorder_level_bags ?? 20;
                    $isCritical = $product->total_bags <= 5;
                    $percent = $threshold > 0 ? min(100, max(8, round(($product->total_bags / $threshold) * 100))) : 50;
                @endphp
                <div class="p-3.5 rounded-2xl border transition-all duration-200 hover:shadow-md {{ $isCritical ? 'bg-gradient-to-r from-rose-50/60 to-white dark:from-rose-950/20 dark:to-slate-900/60 border-rose-200/90 dark:border-rose-900/50' : 'bg-gradient-to-r from-amber-50/60 to-white dark:from-amber-950/20 dark:to-slate-900/60 border-amber-200/90 dark:border-amber-900/50' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            {{-- Icon Pill --}}
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-sm {{ $isCritical ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-400' : 'bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-400' }}">
                                <x-heroicon-m-archive-box-x-mark class="w-5 h-5" />
                            </div>

                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-bold text-sm text-slate-900 dark:text-white">
                                        {{ $product->name }}
                                    </span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $product->pack_size }} KG
                                    </span>
                                </div>

                                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <span>{{ $product->brand ?? 'Feed' }}</span>
                                    <span>·</span>
                                    <span>{{ $product->category->name ?? 'Fish Feed' }}</span>
                                </div>

                                <div class="flex items-center gap-2 pt-0.5">
                                    <span class="text-xs font-black {{ $isCritical ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ __('Only') }} {{ $product->total_bags }} {{ __('bags left') }}
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        (Min: {{ $threshold }} bags)
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Action Button --}}
                        <div class="shrink-0 pt-0.5">
                            @if($isAdmin)
                                <a href="/admin/purchases/create"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-xl text-white shadow-sm transition-all duration-200 hover:scale-[1.03] active:scale-[0.98] {{ $isCritical ? 'bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 shadow-rose-500/25' : 'bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 shadow-amber-500/25' }}">
                                    <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                    <span>{{ __('Reorder') }}</span>
                                </a>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                    {{ __('Low') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Mini Progress Bar --}}
                    <div class="w-full bg-slate-200/70 dark:bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                        <div class="h-1.5 rounded-full {{ $isCritical ? 'bg-rose-500' : 'bg-amber-500' }}"
                             style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-sm text-slate-500 dark:text-slate-400">
                    <x-heroicon-o-check-badge class="w-12 h-12 text-emerald-500 mx-auto mb-2" />
                    <div class="font-extrabold text-slate-900 dark:text-white text-base">{{ __('All Feed Stocks Healthy') }}</div>
                    <div class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">
                        {{ __('All feed varieties currently exceed godown safety reserve levels.') }}
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Footer Dispatch Status Note --}}
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">
                <x-heroicon-m-truck class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                <span>{{ __('Feed mill delivery vehicle scheduled today') }}</span>
            </span>
            <a href="/admin/stock-balances"
               class="font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 inline-flex items-center gap-0.5 transition">
                <span>{{ __('Stock Registry') }}</span>
                <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
