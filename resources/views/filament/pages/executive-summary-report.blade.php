<x-filament-panels::page>
    @php
        $data = $this->executiveData;
        $rev = $data['revenue'];
        $exp = $data['expenses'];
        $bal = $data['balances'];
        $isProfit = $data['net_profit'] >= 0;
    @endphp

    <!-- Date Range Filter Form -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm mb-6">
        <form wire:submit="applyFilter" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[280px]">
                {{ $this->form }}
            </div>
            <div class="mb-2">
                <x-filament::button type="submit" icon="heroicon-m-funnel">
                    {{ __('Filter Period (ফিল্টার করুন)') }}
                </x-filament::button>
            </div>
            <div class="mb-2">
                <a href="{{ route('reports.executive.print', ['from' => $this->from_date, 'to' => $this->to_date]) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-gray-700 bg-gray-100 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-200 rounded-lg transition">
                    <x-heroicon-m-printer class="w-4 h-4" />
                    {{ __('Print PDF (প্রিন্ট)') }}
                </a>
            </div>
        </form>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Sales Revenue -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('Sales Revenue') }}
            </div>
            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                ৳{{ number_format($rev['net_sales'], 2) }}
            </div>
            <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">
                {{ __('Cash:') }} ৳{{ number_format($rev['cash_collected'], 2) }}
            </div>
        </div>

        <!-- COGS -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('Cost of Goods (COGS)') }}
            </div>
            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                ৳{{ number_format($data['cogs'], 2) }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ __('Procurement feed cost') }}
            </div>
        </div>

        <!-- Gross Profit -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('Gross Profit') }}
            </div>
            <div class="text-2xl font-black text-primary-600 dark:text-primary-400 mt-1">
                ৳{{ number_format($data['gross_profit'], 2) }}
            </div>
            <div class="text-xs text-primary-600 font-semibold mt-1">
                {{ __('Margin:') }} {{ $data['gross_margin_percent'] }}%
            </div>
        </div>

        <!-- Operating Expenses -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('Operating Expenses') }}
            </div>
            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                ৳{{ number_format($exp['total_expenses'], 2) }}
            </div>
            <div class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">
                {{ __('Salary + Freight + Waste') }}
            </div>
        </div>

        <!-- Net Profit -->
        <div class="bg-white dark:bg-gray-900 border-2 {{ $isProfit ? 'border-emerald-500 bg-emerald-50/30' : 'border-rose-500 bg-rose-50/30' }} rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider {{ $isProfit ? 'text-emerald-700' : 'text-rose-700' }}">
                {{ $isProfit ? __('Net Profit (নিট লাভ)') : __('Net Loss (লোকসান)') }}
            </div>
            <div class="text-2xl font-black {{ $isProfit ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                ৳{{ number_format(abs($data['net_profit']), 2) }}
            </div>
            <div class="text-xs font-bold {{ $isProfit ? 'text-emerald-700' : 'text-rose-700' }} mt-1">
                {{ $isProfit ? __('Realized Net Profit') : __('Net Loss in Period') }}
            </div>
        </div>
    </div>

    <!-- Financial Statement & Balances Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- P&L Statement -->
        <div class="lg:col-span-7 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
            <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                {{ __('Profit & Loss Statement (লাভ-ক্ষতি বিবরণী)') }} — <span class="text-sm font-normal text-gray-500">{{ $data['period']['formatted'] }}</span>
            </h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-gray-800">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('Gross Sales (বিক্রয় মূল্য)') }}</span>
                    <span class="font-semibold text-gray-900 dark:text-white">৳{{ number_format($rev['gross_sales'], 2) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-gray-800">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('Less: Special Discounts (ছাড়)') }}</span>
                    <span class="font-semibold text-rose-600">-৳{{ number_format($rev['discounts'], 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700 font-bold bg-gray-50 dark:bg-gray-800/50 px-2 rounded">
                    <span>{{ __('Net Sales Revenue (নিট বিক্রয়)') }}</span>
                    <span>৳{{ number_format($rev['net_sales'], 2) }}</span>
                </div>

                <div class="flex justify-between py-1.5 border-b border-gray-50 dark:border-gray-800">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('Less: Cost of Goods Sold / COGS (ক্রয়মূল্য)') }}</span>
                    <span class="font-semibold text-rose-600">-৳{{ number_format($data['cogs'], 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700 font-bold bg-primary-50 dark:bg-primary-900/20 px-2 rounded text-primary-900 dark:text-primary-200">
                    <span>{{ __('Gross Profit (মোট লাভ)') }}</span>
                    <span>৳{{ number_format($data['gross_profit'], 2) }} ({{ $data['gross_margin_percent'] }}%)</span>
                </div>

                <div class="pt-2">
                    <div class="text-xs uppercase font-bold text-gray-400 tracking-wider mb-2">{{ __('Operating Expenses (পরিচালন ব্যয়)') }}</div>
                    <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-800 text-xs">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Staff Salaries Paid (কর্মচারী বেতন)') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">৳{{ number_format($exp['salaries_paid'], 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-800 text-xs">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Inward Truck Freight & Labor (গাড়িভাড়া)') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">৳{{ number_format($exp['purchase_freight'], 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-800 text-xs">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Damage & Wastage Loss (নষ্ট ফিডের ক্ষতি)') }}</span>
                        <span class="font-medium text-rose-600">৳{{ number_format($exp['wastage_cost'], 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between py-3 border-t-2 border-gray-900 dark:border-white font-black text-base {{ $isProfit ? 'text-emerald-600' : 'text-rose-600' }} bg-gray-50 dark:bg-gray-800/80 px-2 rounded">
                    <span>{{ $isProfit ? __('Net Operating Profit (নিট ব্যবসায়িক লাভ)') : __('Net Loss (ব্যবসায়িক ক্ষতি)') }}</span>
                    <span>৳{{ number_format($data['net_profit'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Current Positions & Stock Valuation -->
        <div class="lg:col-span-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
            <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                {{ __('Current Balances & Assets (স্থাবর ও দেনা-পাওনা)') }}
            </h3>

            <div class="space-y-4 text-sm">
                <!-- Customer Dues -->
                <div class="p-3 bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/30 rounded-lg">
                    <div class="text-xs font-semibold text-rose-700 dark:text-rose-400 uppercase">{{ __('Total Customer Receivables (বাকী পাওনা)') }}</div>
                    <div class="text-xl font-black text-rose-600 mt-1">৳{{ number_format($bal['customer_dues'], 2) }}</div>
                    <div class="text-xs text-rose-600/80 mt-0.5">{{ __('Outstanding credit with fish farmers') }}</div>
                </div>

                <!-- Supplier Payables -->
                <div class="p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 rounded-lg">
                    <div class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase">{{ __('Total Supplier Payables (কোম্পানির দেনা)') }}</div>
                    <div class="text-xl font-black text-amber-600 mt-1">৳{{ number_format($bal['supplier_payables'], 2) }}</div>
                    <div class="text-xs text-amber-600/80 mt-0.5">{{ __('Owed to feed mill manufacturers') }}</div>
                </div>

                <!-- Closing Stock Valuation -->
                <div class="p-3 bg-sky-50 dark:bg-sky-900/10 border border-sky-200 dark:border-sky-800/30 rounded-lg">
                    <div class="text-xs font-semibold text-sky-700 dark:text-sky-400 uppercase">{{ __('Closing Stock Valuation (মজুদ ফিডের মূল্য)') }}</div>
                    <div class="text-xl font-black text-sky-600 mt-1">৳{{ number_format($bal['inventory_valuation'], 2) }}</div>
                    <div class="text-xs text-sky-600/80 mt-0.5">{{ __('Total:') }} {{ number_format($bal['total_stock_kg'], 1) }} KG {{ __('in godown') }}</div>
                </div>

                <!-- Staff Advances -->
                <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800/40 rounded border border-gray-100 dark:border-gray-800 text-xs">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('Staff Advance Due (কর্মচারী অগ্রিম বকেয়া):') }}</span>
                    <span class="font-bold text-gray-900 dark:text-white">৳{{ number_format($bal['staff_advance_due'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
