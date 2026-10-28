<x-filament-panels::page>
    @php
        $suppliers = $this->supplierData;
        $totalBilled = $suppliers->sum('total_billed');
        $totalPaid = $suppliers->sum('total_paid');
        $totalPayable = $suppliers->sum('current_payable');
    @endphp

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 uppercase">{{ __('Total Purchases Billed (মোট ক্রয় চালান)') }}</div>
            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">৳{{ number_format($totalBilled, 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ __('Across all feed mills') }}</div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-emerald-600 uppercase">{{ __('Total Payments Disbursed (মোট পরিশোধ)') }}</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">৳{{ number_format($totalPaid, 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ __('Bank & cash settlements') }}</div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-semibold text-amber-600 uppercase">{{ __('Total Outstanding Payable (কোম্পানির দেনা)') }}</div>
            <div class="text-2xl font-black text-amber-600 mt-1">৳{{ number_format($totalPayable, 2) }}</div>
            <div class="text-xs text-amber-600/80 font-semibold mt-1">{{ __('Current unpaid obligations') }}</div>
        </div>
    </div>

    <!-- Supplier Summary Table -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 text-xs uppercase text-gray-600 dark:text-gray-300">
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">{{ __('Supplier Company (কোম্পানি)') }}</th>
                        <th class="py-3 px-4">{{ __('Phone (মোবাইল)') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('Feeds Supplied') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Total Billed (৳)') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Total Paid (৳)') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Net Payable (৳)') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('Last Consignment') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($suppliers as $idx => $s)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                        <td class="py-3 px-4 text-gray-400 text-xs">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                            <a href="/admin/suppliers/{{ $s['supplier_id'] }}" class="hover:underline text-primary-600 dark:text-primary-400">
                                {{ $s['company_name'] }}
                            </a>
                            <div class="text-xs text-gray-500 font-normal">{{ $s['contact_person'] ?? 'Representative' }}</div>
                        </td>
                        <td class="py-3 px-4 text-gray-600 dark:text-gray-400 text-xs">{{ $s['phone'] }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400">
                                {{ $s['feeds_count'] }} feeds
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">
                            ৳{{ number_format($s['total_billed'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-semibold text-emerald-600">
                            ৳{{ number_format($s['total_paid'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-black {{ $s['current_payable'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                            ৳{{ number_format($s['current_payable'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-center text-xs text-gray-500">
                            {{ $s['last_purchase_date'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-gray-500">
                            {{ __('No suppliers registered.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
