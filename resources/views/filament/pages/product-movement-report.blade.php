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
    <div class="rpt-filter-card">
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
    <div class="rpt-kpi-grid rpt-kpi-grid-5">
        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title">{{ __('Total Sacks Sold') }}</div>
            <div class="rpt-kpi-val text-slate-900 dark:text-white">{{ number_format($totalBagsSold) }} <span class="text-xs uppercase text-slate-400 font-semibold">{{ __('Bags') }}</span></div>
            <div class="rpt-kpi-sub text-teal-600 dark:text-teal-400 font-semibold">{{ number_format($totalKgSold, 1) }} KG {{ __('total weight') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title">{{ __('Sales Revenue') }}</div>
            <div class="rpt-kpi-val text-slate-900 dark:text-white">৳{{ number_format($totalRevenue, 2) }}</div>
            <div class="rpt-kpi-sub">{{ __('Realized gross turnover') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title">{{ __('Purchase Cost (COGS)') }}</div>
            <div class="rpt-kpi-val text-rose-600 dark:text-rose-400">৳{{ number_format($totalCost, 2) }}</div>
            <div class="rpt-kpi-sub">{{ __('Inventory procurement cost') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-emerald-600 dark:text-emerald-400">{{ __('Gross Profit') }}</div>
            <div class="rpt-kpi-val text-emerald-600 dark:text-emerald-400">৳{{ number_format($totalProfit, 2) }}</div>
            <div class="rpt-kpi-sub text-emerald-600 dark:text-emerald-400 font-bold">
                {{ $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0 }}% {{ __('Overall Margin') }}
            </div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-sky-600 dark:text-sky-400">{{ __('Feed Lines Active') }}</div>
            <div class="rpt-kpi-val text-sky-600 dark:text-sky-400">{{ $products->count() }}</div>
            <div class="rpt-kpi-sub">{{ __('Products in catalog') }}</div>
        </div>
    </div>

    <!-- Products Movement Velocity Table -->
    <div class="rpt-table-card">
        <div class="overflow-x-auto">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Product & Brand (পণ্যের নাম)') }}</th>
                        <th class="text-center">{{ __('Pack') }}</th>
                        <th class="text-center">{{ __('Bags Sold') }}</th>
                        <th class="text-center">{{ __('Loose KG') }}</th>
                        <th class="text-right">{{ __('Revenue (৳)') }}</th>
                        <th class="text-right">{{ __('Cost (৳)') }}</th>
                        <th class="text-right">{{ __('Profit (৳)') }}</th>
                        <th class="text-center">{{ __('Margin') }}</th>
                        <th class="text-right">{{ __('Current Stock') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $idx => $p)
                    <tr>
                        <td class="text-xs text-slate-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                        <td class="font-bold text-slate-900 dark:text-white">
                            <a href="/admin/products/{{ $p['product_id'] }}/edit" class="hover:underline text-teal-600 dark:text-teal-400">
                                {{ $p['name'] }}
                            </a>
                            <div class="text-xs text-slate-500 dark:text-slate-400 font-normal">{{ $p['brand'] }} | {{ $p['category'] }}</div>
                        </td>
                        <td class="text-center text-xs text-slate-600 dark:text-slate-400">{{ $p['pack_size'] }} KG</td>
                        <td class="text-center font-bold text-slate-900 dark:text-white">
                            {{ $p['bags_sold'] > 0 ? $p['bags_sold'] . ' Bags' : '—' }}
                        </td>
                        <td class="text-center font-semibold text-slate-600 dark:text-slate-400 text-xs">
                            {{ $p['loose_kg_sold'] > 0 ? $p['loose_kg_sold'] . ' KG' : '—' }}
                        </td>
                        <td class="text-right font-black text-slate-900 dark:text-white">
                            ৳{{ number_format($p['revenue'], 2) }}
                        </td>
                        <td class="text-right text-rose-600 dark:text-rose-400 font-medium">
                            ৳{{ number_format($p['cost'], 2) }}
                        </td>
                        <td class="text-right font-bold text-emerald-600 dark:text-emerald-400">
                            ৳{{ number_format($p['profit'], 2) }}
                        </td>
                        <td class="text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $p['margin_percent'] >= 10 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}">
                                {{ $p['margin_percent'] }}%
                            </span>
                        </td>
                        <td class="text-right font-bold text-sky-600 dark:text-sky-400">
                            {{ $p['stock_bags'] }} Bags ({{ $p['stock_kg'] }} KG)
                            <div class="text-xs text-slate-400 font-normal">৳{{ number_format($p['stock_value'], 2) }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-8 text-center text-slate-400 dark:text-slate-500">
                            {{ __('No product sales recorded in this period.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
