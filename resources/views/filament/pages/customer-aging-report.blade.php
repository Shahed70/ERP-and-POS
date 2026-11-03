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
    <div class="rpt-kpi-grid rpt-kpi-grid-5">
        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title">{{ __('Total Outstanding Due') }}</div>
            <div class="rpt-kpi-val text-rose-600 dark:text-rose-400">৳{{ number_format($totalDue, 2) }}</div>
            <div class="rpt-kpi-sub">{{ $customers->count() }} {{ __('customers with debt') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-emerald-600 dark:text-emerald-400">{{ __('0 — 30 Days (চলতি)') }}</div>
            <div class="rpt-kpi-val text-emerald-600 dark:text-emerald-400">৳{{ number_format($total0to30, 2) }}</div>
            <div class="rpt-kpi-sub">{{ __('Current harvest cycle') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-sky-600 dark:text-sky-400">{{ __('31 — 60 Days (১-২ মাস)') }}</div>
            <div class="rpt-kpi-val text-sky-600 dark:text-sky-400">৳{{ number_format($total31to60, 2) }}</div>
            <div class="rpt-kpi-sub">{{ __('Medium risk') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-amber-600 dark:text-amber-400">{{ __('61 — 90 Days (২-৩ মাস)') }}</div>
            <div class="rpt-kpi-val text-amber-600 dark:text-amber-400">৳{{ number_format($total61to90, 2) }}</div>
            <div class="rpt-kpi-sub">{{ __('High risk aging') }}</div>
        </div>

        <div class="rpt-kpi-card">
            <div class="rpt-kpi-title text-rose-700 dark:text-rose-400">{{ __('> 90 Days (৩ মাসের বেশি)') }}</div>
            <div class="rpt-kpi-val text-rose-700 dark:text-rose-400">৳{{ number_format($totalOver90, 2) }}</div>
            <div class="rpt-kpi-sub text-rose-500 font-semibold">{{ __('Urgent collection needed') }}</div>
        </div>
    </div>

    <!-- Aging Analysis Table -->
    <div class="rpt-table-card">
        <div class="overflow-x-auto">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Customer Name (ক্রেতা)') }}</th>
                        <th>{{ __('Phone (মোবাইল)') }}</th>
                        <th class="text-right">{{ __('Total Due (মোট দেনা)') }}</th>
                        <th class="text-right">{{ __('0-30 Days') }}</th>
                        <th class="text-right">{{ __('31-60 Days') }}</th>
                        <th class="text-right">{{ __('61-90 Days') }}</th>
                        <th class="text-right text-rose-600 dark:text-rose-400">{{ __('> 90 Days') }}</th>
                        <th class="text-center">{{ __('Risk Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $idx => $c)
                    <tr>
                        <td class="text-xs text-slate-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                        <td class="font-bold text-slate-900 dark:text-white">
                            <a href="/admin/customers/{{ $c['customer_id'] }}" class="hover:underline text-teal-600 dark:text-teal-400">
                                {{ $c['name'] }}
                            </a>
                        </td>
                        <td class="text-slate-600 dark:text-slate-400 text-xs">{{ $c['phone'] }}</td>
                        <td class="text-right font-black text-slate-900 dark:text-white">
                            ৳{{ number_format($c['total_due'], 2) }}
                        </td>
                        <td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ $c['bucket_0_30'] > 0 ? '৳' . number_format($c['bucket_0_30'], 2) : '—' }}
                        </td>
                        <td class="text-right font-semibold text-sky-600 dark:text-sky-400">
                            {{ $c['bucket_31_60'] > 0 ? '৳' . number_format($c['bucket_31_60'], 2) : '—' }}
                        </td>
                        <td class="text-right font-semibold text-amber-600 dark:text-amber-400">
                            {{ $c['bucket_61_90'] > 0 ? '৳' . number_format($c['bucket_61_90'], 2) : '—' }}
                        </td>
                        <td class="text-right font-bold text-rose-600 dark:text-rose-400">
                            {{ $c['bucket_over_90'] > 0 ? '৳' . number_format($c['bucket_over_90'], 2) : '—' }}
                        </td>
                        <td class="text-center">
                            @if($c['is_risk_exceeded'])
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40">
                                {{ __('Ceiling Exceeded') }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                                {{ __('Within Limit') }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400 dark:text-slate-500">
                            {{ __('No customers with outstanding due balances.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
