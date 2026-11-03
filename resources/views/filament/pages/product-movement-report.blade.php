<x-filament-panels::page>
    @php
        $products = $this->movementData;
        $totalRevenue = $products->sum('revenue');
        $totalCost = $products->sum('cost');
        $totalProfit = $products->sum('profit');
        $totalKgSold = $products->sum('total_kg_sold');
        $totalBagsSold = $products->sum('bags_sold');
    @endphp

    <!-- Date Range Filter Form -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm mb-6">
        <form wire:submit="applyFilter" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[280px]">
                {{ $this->form }}
            </div>
            <div class="mb-2">
                <x-filament::button type="submit" icon="heroicon-m-funnel">
                    {{ __('Filter Period (ফিল্টার)') }}
                </x-filament::button>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Total Sacks Sold') }}</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5">{{ number_format($totalBagsSold) }} {{ __('Bags') }}</div>
            <div class="text-xs text-teal-600 dark:text-teal-400 mt-1 font-semibold">{{ number_format($totalKgSold, 1) }} KG {{ __('total weight') }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Sales Revenue') }}</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5">৳{{ number_format($totalRevenue, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('Realized gross turnover') }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Purchase Cost (COGS)') }}</div>
            <div class="text-2xl font-black text-rose-600 mt-1.5">৳{{ number_format($totalCost, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('Inventory procurement cost') }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">{{ __('Gross Profit') }}</div>
            <div class="text-2xl font-black text-emerald-600 mt-1.5">৳{{ number_format($totalProfit, 2) }}</div>
            <div class="text-xs text-emerald-600 font-bold mt-1">
                {{ $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0 }}% {{ __('Overall Margin') }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-sky-600 uppercase tracking-wider">{{ __('Feed Lines Active') }}</div>
            <div class="text-2xl font-black text-sky-600 mt-1.5">{{ $products->count() }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('Products in catalog') }}</div>
        </div>
    </div>

    <!-- Products Movement Velocity Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 text-xs uppercase text-gray-600 dark:text-gray-300">
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">{{ __('Product & Brand (পণ্যের নাম)') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('Pack') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('Bags Sold') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('Loose KG') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Revenue (৳)') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Cost (৳)') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Profit (৳)') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('Margin') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Current Stock') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($products as $idx => $p)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                        <td class="py-3 px-4 text-gray-400 text-xs">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                            <a href="/admin/products/{{ $p['product_id'] }}/edit" class="hover:underline text-primary-600 dark:text-primary-400">
                                {{ $p['name'] }}
                            </a>
                            <div class="text-xs text-gray-500 font-normal">{{ $p['brand'] }} | {{ $p['category'] }}</div>
                        </td>
                        <td class="py-3 px-4 text-center text-xs text-gray-600 dark:text-gray-400">{{ $p['pack_size'] }} KG</td>
                        <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white">
                            {{ $p['bags_sold'] > 0 ? $p['bags_sold'] . ' Bags' : '—' }}
                        </td>
                        <td class="py-3 px-4 text-center font-semibold text-gray-600 dark:text-gray-400 text-xs">
                            {{ $p['loose_kg_sold'] > 0 ? $p['loose_kg_sold'] . ' KG' : '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-black text-gray-900 dark:text-white">
                            ৳{{ number_format($p['revenue'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-rose-600 font-medium">
                            ৳{{ number_format($p['cost'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-emerald-600">
                            ৳{{ number_format($p['profit'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $p['margin_percent'] >= 10 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}">
                                {{ $p['margin_percent'] }}%
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-sky-600">
                            {{ $p['stock_bags'] }} Bags ({{ $p['stock_kg'] }} KG)
                            <div class="text-xs text-gray-400 font-normal">৳{{ number_format($p['stock_value'], 2) }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-6 text-center text-gray-500">
                            {{ __('No product sales recorded in this period.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
