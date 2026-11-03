<x-filament-panels::page>
    @php
        $suppliers = $this->supplierData;
        $totalBilled = $suppliers->sum('total_billed');
        $totalPaid = $suppliers->sum('total_paid');
        $totalPayable = $suppliers->sum('current_payable');
    @endphp

    <!-- Summary KPI Cards -->
    <div class="rpt-kpi-grid rpt-kpi-grid-3">
        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title">{{ __('Total Purchases Billed (মোট ক্রয় চালান)') }}</div>
            <div class="rpt-kpi-val text-slate-900 dark:text-white">৳{{ number_format($totalBilled, 2) }}</div>
            <div class="rpt-kpi-sub">{{ __('Across all feed mills') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-emerald-600 dark:text-emerald-400">{{ __('Total Payments Disbursed (মোট পরিশোধ)') }}</div>
            <div class="rpt-kpi-val text-emerald-600 dark:text-emerald-400">৳{{ number_format($totalPaid, 2) }}</div>
            <div class="rpt-kpi-sub">{{ __('Bank & cash settlements') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-amber-600 dark:text-amber-400">{{ __('Total Outstanding Payable (কোম্পানির দেনা)') }}</div>
            <div class="rpt-kpi-val text-amber-600 dark:text-amber-400">৳{{ number_format($totalPayable, 2) }}</div>
            <div class="rpt-kpi-sub text-amber-600 dark:text-amber-400 font-semibold">{{ __('Current unpaid obligations') }}</div>
        </div>
    </div>

    <!-- Supplier Summary Table -->
    <div class="rpt-table-card">
        <div class="overflow-x-auto">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Supplier Company (কোম্পানি)') }}</th>
                        <th>{{ __('Phone (মোবাইল)') }}</th>
                        <th class="text-center">{{ __('Feeds Supplied') }}</th>
                        <th class="text-right">{{ __('Total Billed (৳)') }}</th>
                        <th class="text-right">{{ __('Total Paid (৳)') }}</th>
                        <th class="text-right">{{ __('Net Payable (৳)') }}</th>
                        <th class="text-center">{{ __('Last Consignment') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $idx => $s)
                    <tr>
                        <td class="text-xs text-slate-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                        <td class="font-bold text-slate-900 dark:text-white">
                            <a href="/admin/suppliers/{{ $s['supplier_id'] }}" class="hover:underline text-teal-600 dark:text-teal-400">
                                {{ $s['company_name'] }}
                            </a>
                            <div class="text-xs text-slate-500 dark:text-slate-400 font-normal">{{ $s['contact_person'] ?? 'Representative' }}</div>
                        </td>
                        <td class="text-slate-600 dark:text-slate-400 text-xs">{{ $s['phone'] }}</td>
                        <td class="text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800/40">
                                {{ $s['feeds_count'] }} feeds
                            </span>
                        </td>
                        <td class="text-right font-semibold text-slate-900 dark:text-white">
                            ৳{{ number_format($s['total_billed'], 2) }}
                        </td>
                        <td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">
                            ৳{{ number_format($s['total_paid'], 2) }}
                        </td>
                        <td class="text-right font-black {{ $s['current_payable'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            ৳{{ number_format($s['current_payable'], 2) }}
                        </td>
                        <td class="text-center text-xs text-slate-500 dark:text-slate-400">
                            {{ $s['last_purchase_date'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500">
                            {{ __('No suppliers registered.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
