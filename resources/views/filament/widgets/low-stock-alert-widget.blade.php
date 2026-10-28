<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-rose-500" />
                <span>{{ __('Urgent Low-Stock Alerts (মজুদ সতর্কতা)') }}</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            @if($criticalCount > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                    {{ $criticalCount }} {{ __('SKUs Critical') }}
                </span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ __('Stock Normal') }}
                </span>
            @endif
        </x-slot>

        <div class="space-y-3">
            @forelse($lowStockItems as $product)
                @php
                    $isCritical = $product->total_bags <= 5;
                @endphp
                <div class="flex items-center justify-between p-3 rounded-xl border {{ $isCritical ? 'bg-rose-50/50 dark:bg-rose-900/10 border-rose-200 dark:border-rose-800/40' : 'bg-amber-50/50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40' }} transition">
                    <div class="space-y-0.5">
                        <div class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ $product->name }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $product->brand ?? 'Feed' }} | {{ $product->category->name ?? 'Fish Feed' }} ({{ $product->pack_size }} KG)
                        </div>
                        <div class="text-xs font-semibold {{ $isCritical ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ __('Only') }} {{ $product->total_bags }} {{ __('bags left') }}
                            <span class="font-normal text-gray-400">({{ number_format($product->total_stock_kg, 1) }} KG)</span>
                        </div>
                    </div>

                    @if($isAdmin)
                        <a href="/admin/purchases/create"
                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-gray-900 hover:bg-gray-800 text-white dark:bg-gray-100 dark:hover:bg-gray-200 dark:text-gray-900 transition">
                            <x-heroicon-m-plus class="w-3.5 h-3.5" />
                            {{ __('Reorder') }}
                        </a>
                    @else
                        <span class="text-xs font-semibold text-rose-600">
                            {{ __('Low') }}
                        </span>
                    @endif
                </div>
            @empty
                <div class="text-center py-6 text-sm text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-check-badge class="w-10 h-10 text-emerald-500 mx-auto mb-2" />
                    <div class="font-bold text-gray-900 dark:text-white">{{ __('All Stock Healthy') }}</div>
                    <div class="text-xs mt-0.5">{{ __('All feed lines currently exceed minimum safety thresholds.') }}</div>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
