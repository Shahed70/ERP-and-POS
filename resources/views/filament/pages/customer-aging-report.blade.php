<x-filament-panels::page>
    @php
        $customers = $this->agingData;
        $totalDue = $customers->sum('total_due');
        $total0to30 = $customers->sum('bucket_0_30');
        $total31to60 = $customers->sum('bucket_31_60');
        $total61to90 = $customers->sum('bucket_61_90');
        $totalOver90 = $customers->sum('bucket_over_90');
    @endphp

    <!-- Aging Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Total Outstanding Due') }}</div>
            <div class="text-2xl font-black text-rose-600 mt-1.5">৳{{ number_format($totalDue, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $customers->count() }} {{ __('customers with debt') }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">{{ __('0 — 30 Days (চলতি)') }}</div>
            <div class="text-2xl font-black text-emerald-600 mt-1.5">৳{{ number_format($total0to30, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('Current harvest cycle') }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-sky-600 uppercase tracking-wider">{{ __('31 — 60 Days (১-২ মাস)') }}</div>
            <div class="text-2xl font-black text-sky-600 mt-1.5">৳{{ number_format($total31to60, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('Medium risk') }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-amber-600 uppercase tracking-wider">{{ __('61 — 90 Days (২-৩ মাস)') }}</div>
            <div class="text-2xl font-black text-amber-600 mt-1.5">৳{{ number_format($total61to90, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ __('High risk aging') }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="text-xs font-bold text-rose-700 uppercase tracking-wider">{{ __('> 90 Days (৩ মাসের বেশি)') }}</div>
            <div class="text-2xl font-black text-rose-700 mt-1.5">৳{{ number_format($totalOver90, 2) }}</div>
            <div class="text-xs text-rose-500 font-semibold mt-1">{{ __('Urgent collection needed') }}</div>
        </div>
    </div>

    <!-- Aging Analysis Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 text-xs uppercase text-gray-600 dark:text-gray-300">
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">{{ __('Customer Name (ক্রেতা)') }}</th>
                        <th class="py-3 px-4">{{ __('Phone (মোবাইল)') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('Total Due (মোট দেনা)') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('0-30 Days') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('31-60 Days') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('61-90 Days') }}</th>
                        <th class="py-3 px-4 text-right text-rose-600">{{ __('> 90 Days') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('Risk Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($customers as $idx => $c)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                        <td class="py-3 px-4 text-gray-400 text-xs">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                            <a href="/admin/customers/{{ $c['customer_id'] }}" class="hover:underline text-primary-600 dark:text-primary-400">
                                {{ $c['name'] }}
                            </a>
                        </td>
                        <td class="py-3 px-4 text-gray-600 dark:text-gray-400 text-xs">{{ $c['phone'] }}</td>
                        <td class="py-3 px-4 text-right font-black text-gray-900 dark:text-white">
                            ৳{{ number_format($c['total_due'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-semibold text-emerald-600">
                            {{ $c['bucket_0_30'] > 0 ? '৳' . number_format($c['bucket_0_30'], 2) : '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-semibold text-sky-600">
                            {{ $c['bucket_31_60'] > 0 ? '৳' . number_format($c['bucket_31_60'], 2) : '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-semibold text-amber-600">
                            {{ $c['bucket_61_90'] > 0 ? '৳' . number_format($c['bucket_61_90'], 2) : '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-rose-600">
                            {{ $c['bucket_over_90'] > 0 ? '৳' . number_format($c['bucket_over_90'], 2) : '—' }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($c['is_risk_exceeded'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                                {{ __('Ceiling Exceeded') }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                {{ __('Within Limit') }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-6 text-center text-gray-500">
                            {{ __('No customers with outstanding due balances.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
