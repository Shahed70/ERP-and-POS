<x-filament-panels::page>
    @php
        $data = $this->executiveData;
        $rev = $data['revenue'];
        $exp = $data['expenses'];
        $bal = $data['balances'];
        $isProfit = $data['net_profit'] >= 0;
    @endphp

    <style>
        /* Scoped styling to ensure perfect dark/light mode rendering and generous breathing room */
        .pnl-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Filter Section */
        .pnl-filter-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .dark .pnl-filter-box {
            background-color: #0f172a;
            border-color: #1e293b;
        }

        /* Top 5 KPI Cards */
        .pnl-kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.15rem;
        }
        @media (min-width: 640px) {
            .pnl-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .pnl-kpi-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }

        .pnl-kpi-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 1.35rem;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .pnl-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }
        .dark .pnl-kpi-card {
            background-color: #0f172a;
            border-color: #1e293b;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.3);
        }
        .dark .pnl-kpi-card:hover {
            box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.5);
            border-color: #334155;
        }

        .pnl-kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .dark .pnl-kpi-label {
            color: #94a3b8;
        }

        .pnl-kpi-value {
            font-size: 1.625rem;
            font-weight: 800;
            line-height: 1.2;
            margin-top: 0.5rem;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
        }
        .dark .pnl-kpi-value {
            color: #f8fafc;
        }

        .pnl-kpi-sub {
            font-size: 0.75rem;
            margin-top: 0.4rem;
            color: #64748b;
        }
        .dark .pnl-kpi-sub {
            color: #94a3b8;
        }

        /* Main Grid (P&L on Left, Balances on Right) */
        .pnl-main-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        }
        @media (min-width: 1024px) {
            .pnl-main-grid {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }
            .pnl-col-statement { grid-column: span 7 / span 7; }
            .pnl-col-balances { grid-column: span 5 / span 5; }
        }

        /* Main Panel Containers */
        .pnl-panel {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 1.75rem;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
        }
        .dark .pnl-panel {
            background-color: #0f172a;
            border-color: #1e293b;
            box-shadow: 0 8px 24px -2px rgba(0, 0, 0, 0.35);
        }

        .pnl-panel-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .dark .pnl-panel-header {
            border-color: #1e293b;
        }

        .pnl-panel-title {
            font-size: 1.125rem;
            font-weight: 800;
            color: #0f172a;
        }
        .dark .pnl-panel-title {
            color: #f8fafc;
        }

        .pnl-panel-desc {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        .dark .pnl-panel-desc {
            color: #94a3b8;
        }

        /* Statement Sub-sections */
        .pnl-section-group {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
        }
        .dark .pnl-section-group {
            background-color: #1e293b;
            border-color: #334155;
        }

        .pnl-section-header {
            font-size: 0.8125rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .pnl-section-header {
            color: #cbd5e1;
        }

        /* Spacious Rows */
        .pnl-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9375rem;
        }
        .dark .pnl-row {
            border-color: #334155;
        }
        .pnl-row:last-child {
            border-bottom: none;
        }

        .pnl-row-label-primary {
            font-weight: 600;
            color: #1e293b;
        }
        .dark .pnl-row-label-primary {
            color: #f1f5f9;
        }

        .pnl-row-label-sub {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.15rem;
        }
        .dark .pnl-row-label-sub {
            color: #94a3b8;
        }

        .pnl-row-value {
            font-weight: 700;
            font-size: 1rem;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
        }
        .dark .pnl-row-value {
            color: #f8fafc;
        }

        /* Subtotal Bars */
        .pnl-subtotal-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem 1.25rem;
            border-radius: 0.75rem;
            margin-top: 0.85rem;
            font-weight: 700;
            font-size: 0.9375rem;
        }

        .pnl-subtotal-bar.neutral {
            background-color: #e2e8f0;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }
        .dark .pnl-subtotal-bar.neutral {
            background-color: #0f172a;
            color: #f8fafc;
            border-color: #334155;
        }

        .pnl-subtotal-bar.success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .dark .pnl-subtotal-bar.success {
            background-color: #064e3b;
            color: #6ee7b7;
            border-color: #047857;
        }

        .pnl-subtotal-bar.warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .dark .pnl-subtotal-bar.warning {
            background-color: #451a03;
            color: #fcd34d;
            border-color: #78350f;
        }

        /* Final Net Bottom-Line Card */
        .pnl-net-result {
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .pnl-net-result.profit {
            background-color: #ecfdf5;
            border: 2px solid #10b981;
            color: #064e3b;
        }
        .dark .pnl-net-result.profit {
            background-color: #06281e;
            border-color: #059669;
            color: #a7f3d0;
        }

        .pnl-net-result.loss {
            background-color: #fef2f2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        .dark .pnl-net-result.loss {
            background-color: #2d0b0b;
            border-color: #dc2626;
            color: #fca5a5;
        }

        /* Balance Sheet Cards (Right Column) */
        .pnl-balance-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .dark .pnl-balance-card {
            background-color: #1e293b;
            border-color: #334155;
        }
        .pnl-balance-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
        }

        .pnl-balance-card.receivables {
            border-left: 6px solid #ef4444;
        }
        .pnl-balance-card.payables {
            border-left: 6px solid #f59e0b;
        }
        .pnl-balance-card.inventory {
            border-left: 6px solid #0284c7;
        }
        .pnl-balance-card.advances {
            border-left: 6px solid #8b5cf6;
        }

        .pnl-balance-title {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .pnl-balance-amount {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
        }
        .pnl-balance-desc {
            font-size: 0.8125rem;
            color: #64748b;
        }
        .dark .pnl-balance-desc {
            color: #94a3b8;
        }

        /* Pill Badge */
        .pnl-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
    </style>

    <div class="pnl-container">
        <!-- 1. Date Range Filter Toolbar -->
        <div class="pnl-filter-box">
            <form wire:submit="applyFilter" class="flex flex-wrap items-end justify-between gap-4">
                <div class="flex-1 min-w-[280px]">
                    {{ $this->form }}
                </div>
                <div class="flex items-center gap-3">
                    <x-filament::button type="submit" icon="heroicon-m-funnel">
                        {{ __('Filter Period (ফিল্টার করুন)') }}
                    </x-filament::button>

                    <a href="{{ route('reports.executive.print', ['from' => $this->from_date, 'to' => $this->to_date]) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 transition">
                        <x-heroicon-m-printer class="w-4 h-4" />
                        {{ __('Print A4 (প্রিন্ট)') }}
                    </a>
                </div>
            </form>
        </div>

        <!-- 2. High-Impact KPI Highlight Cards -->
        <div class="pnl-kpi-grid">
            <!-- 1. Net Sales Revenue -->
            <div class="pnl-kpi-card">
                <div class="flex justify-between items-start">
                    <span class="pnl-kpi-label">{{ __('Net Sales Revenue') }}</span>
                    <span class="pnl-pill" style="background-color: #dbeafe; color: #1e40af;">
                        {{ __('আয়') }}
                    </span>
                </div>
                <div class="pnl-kpi-value text-blue-600 dark:text-blue-400">
                    ৳{{ number_format($rev['net_sales'], 2) }}
                </div>
                <div class="pnl-kpi-sub">
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">Cash: ৳{{ number_format($rev['cash_collected'], 2) }}</span>
                    <span class="mx-1">|</span>
                    <span>Credit: ৳{{ number_format($rev['due_accrued'], 2) }}</span>
                </div>
            </div>

            <!-- 2. COGS -->
            <div class="pnl-kpi-card">
                <div class="flex justify-between items-start">
                    <span class="pnl-kpi-label">{{ __('Cost of Goods (COGS)') }}</span>
                    <span class="pnl-pill" style="background-color: #f1f5f9; color: #475569;">
                        {{ __('ক্রয়ব্যয়') }}
                    </span>
                </div>
                <div class="pnl-kpi-value text-slate-700 dark:text-slate-300">
                    ৳{{ number_format($data['cogs'], 2) }}
                </div>
                <div class="pnl-kpi-sub">
                    {{ __('Feed procurement purchase cost') }}
                </div>
            </div>

            <!-- 3. Gross Profit -->
            <div class="pnl-kpi-card">
                <div class="flex justify-between items-start">
                    <span class="pnl-kpi-label">{{ __('Gross Profit') }}</span>
                    <span class="pnl-pill" style="background-color: #dcfce7; color: #166534;">
                        {{ $data['gross_margin_percent'] }}% {{ __('Margin') }}
                    </span>
                </div>
                <div class="pnl-kpi-value text-emerald-600 dark:text-emerald-400">
                    ৳{{ number_format($data['gross_profit'], 2) }}
                </div>
                <div class="pnl-kpi-sub font-medium text-emerald-700 dark:text-emerald-400">
                    {{ __('Net Sales minus Feed COGS') }}
                </div>
            </div>

            <!-- 4. Operating Expenses -->
            <div class="pnl-kpi-card">
                <div class="flex justify-between items-start">
                    <span class="pnl-kpi-label">{{ __('Operating Expenses') }}</span>
                    <span class="pnl-pill" style="background-color: #fef3c7; color: #92400e;">
                        {{ __('পরিচালন ব্যয়') }}
                    </span>
                </div>
                <div class="pnl-kpi-value text-amber-600 dark:text-amber-400">
                    ৳{{ number_format($exp['total_expenses'], 2) }}
                </div>
                <div class="pnl-kpi-sub">
                    {{ __('Salaries, Freight & Wastage') }}
                </div>
            </div>

            <!-- 5. Net Operating Profit/Loss -->
            <div class="pnl-kpi-card" style="{{ $isProfit ? 'border-color: #10b981; background-color: rgba(16, 185, 129, 0.05);' : 'border-color: #ef4444; background-color: rgba(239, 68, 68, 0.05);' }}">
                <div class="flex justify-between items-start">
                    <span class="pnl-kpi-label" style="color: {{ $isProfit ? '#059669' : '#dc2626' }};">
                        {{ $isProfit ? __('Net Profit (নিট লাভ)') : __('Net Loss (লোকসান)') }}
                    </span>
                    <span class="pnl-pill" style="background-color: {{ $isProfit ? '#dcfce7' : '#fee2e2' }}; color: {{ $isProfit ? '#166534' : '#991b1b' }};">
                        {{ $isProfit ? __('লাভজনক') : __('ক্ষতি') }}
                    </span>
                </div>
                <div class="pnl-kpi-value" style="color: {{ $isProfit ? '#059669' : '#dc2626' }};">
                    ৳{{ number_format($data['net_profit'], 2) }}
                </div>
                <div class="pnl-kpi-sub font-semibold" style="color: {{ $isProfit ? '#047857' : '#b91c1c' }};">
                    {{ $isProfit ? __('Realized Net Business Profit') : __('Net Operating Loss in Period') }}
                </div>
            </div>
        </div>

        <!-- 3. Financial Statement & Balances Breakdown -->
        <div class="pnl-main-grid">
            <!-- Left Column: Detailed P&L Statement (Spacious & Clean) -->
            <div class="pnl-col-statement pnl-panel">
                <div class="pnl-panel-header">
                    <div>
                        <h2 class="pnl-panel-title">
                            {{ __('Profit & Loss Statement (লাভ-ক্ষতি বিবরণী)') }}
                        </h2>
                        <p class="pnl-panel-desc">
                            {{ __('Trading and operational results for') }} <strong class="text-primary-600 dark:text-primary-400">{{ $data['period']['formatted'] }}</strong>
                        </p>
                    </div>
                    <span class="pnl-pill" style="background-color: #f1f5f9; color: #475569;">
                        {{ __('Accrual Accounting (বকেয়াভিত্তিক)') }}
                    </span>
                </div>

                <!-- 1. Operating Revenue -->
                <div class="pnl-section-group">
                    <div class="pnl-section-header">
                        <span>{{ __('1. Operating Revenue (ফিড বিক্রয় আয়)') }}</span>
                        <span style="font-size: 0.75rem; text-transform: none; color: #64748b;">(Gross Sales minus Discounts)</span>
                    </div>

                    <div class="pnl-row">
                        <div class="pnl-row-label">
                            <span class="pnl-row-label-primary">{{ __('Gross Feed Sales (মোট বিক্রয় মূল্য)') }}</span>
                            <span class="pnl-row-label-sub">{{ __('Total invoice billing across all orders') }}</span>
                        </div>
                        <span class="pnl-row-value">৳{{ number_format($rev['gross_sales'], 2) }}</span>
                    </div>

                    <div class="pnl-row">
                        <div class="pnl-row-label">
                            <span class="pnl-row-label-primary">{{ __('Less: Special Discounts & Rebates (কমিশন ও ছাড়)') }}</span>
                            <span class="pnl-row-label-sub">{{ __('Customer concessions deducted at billing') }}</span>
                        </div>
                        <span class="pnl-row-value text-rose-600 dark:text-rose-400">-৳{{ number_format($rev['discounts'], 2) }}</span>
                    </div>

                    <div class="pnl-subtotal-bar neutral">
                        <span>{{ __('Net Sales Revenue (নিট বিক্রয় আয়)') }}</span>
                        <span style="font-size: 1.0625rem;">৳{{ number_format($rev['net_sales'], 2) }}</span>
                    </div>
                </div>

                <!-- 2. Cost of Goods Sold -->
                <div class="pnl-section-group">
                    <div class="pnl-section-header">
                        <span>{{ __('2. Cost of Goods Sold (বিক্রীত ফিডের ক্রয়মূল্য)') }}</span>
                        <span style="font-size: 0.75rem; text-transform: none; color: #64748b;">(Feed Procurement COGS)</span>
                    </div>

                    <div class="pnl-row">
                        <div class="pnl-row-label">
                            <span class="pnl-row-label-primary">{{ __('Feed Procurement Cost (মিল থেকে ক্রয়মূল্য)') }}</span>
                            <span class="pnl-row-label-sub">{{ __('Direct purchase cost of feed sold in period') }}</span>
                        </div>
                        <span class="pnl-row-value text-rose-600 dark:text-rose-400">-৳{{ number_format($data['cogs'], 2) }}</span>
                    </div>

                    <div class="pnl-subtotal-bar success">
                        <div class="flex items-center gap-2">
                            <span>{{ __('Gross Trading Profit (মোট ব্যবসায়িক লাভ)') }}</span>
                            <span class="pnl-pill" style="background-color: #bbf7d0; color: #14532d; font-size: 0.7rem;">
                                {{ $data['gross_margin_percent'] }}% Margin
                            </span>
                        </div>
                        <span style="font-size: 1.0625rem;">৳{{ number_format($data['gross_profit'], 2) }}</span>
                    </div>
                </div>

                <!-- 3. Operating Overhead Expenses -->
                <div class="pnl-section-group">
                    <div class="pnl-section-header">
                        <span>{{ __('3. Operating Expenses (দোকান পরিচালন ব্যয়)') }}</span>
                        <span style="font-size: 0.75rem; text-transform: none; color: #64748b;">(Overhead disbursements)</span>
                    </div>

                    <div class="pnl-row">
                        <div class="pnl-row-label">
                            <span class="pnl-row-label-primary">{{ __('Staff Salaries Paid (কর্মচারী বেতন ও ভাতা)') }}</span>
                            <span class="pnl-row-label-sub">{{ __('Disbursed payroll for active shop staff') }}</span>
                        </div>
                        <span class="pnl-row-value text-amber-600 dark:text-amber-400">-৳{{ number_format($exp['salaries_paid'], 2) }}</span>
                    </div>

                    <div class="pnl-row">
                        <div class="pnl-row-label">
                            <span class="pnl-row-label-primary">{{ __('Inward Truck Freight & Labor (গাড়িভাড়া ও কুলি খরচ)') }}</span>
                            <span class="pnl-row-label-sub">{{ __('Transportation and handling from mills') }}</span>
                        </div>
                        <span class="pnl-row-value text-amber-600 dark:text-amber-400">-৳{{ number_format($exp['purchase_freight'], 2) }}</span>
                    </div>

                    <div class="pnl-row">
                        <div class="pnl-row-label">
                            <span class="pnl-row-label-primary">{{ __('Damage & Wastage Loss (নষ্ট ও ক্ষতিগ্রস্থ ফিড)') }}</span>
                            <span class="pnl-row-label-sub">{{ __('Rat-bite and torn bag godown write-offs') }}</span>
                        </div>
                        <span class="pnl-row-value text-rose-600 dark:text-rose-400">-৳{{ number_format($exp['wastage_cost'], 2) }}</span>
                    </div>

                    <div class="pnl-subtotal-bar warning">
                        <span>{{ __('Total Operating Expenses (মোট পরিচালন ব্যয়)') }}</span>
                        <span style="font-size: 1.0625rem;">-৳{{ number_format($exp['total_expenses'], 2) }}</span>
                    </div>
                </div>

                <!-- 4. Net Operating Profit/Loss Summary Box -->
                <div class="pnl-net-result {{ $isProfit ? 'profit' : 'loss' }}">
                    <div>
                        <div style="font-size: 0.8125rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                            {{ $isProfit ? __('NET OPERATING PROFIT (নিট ব্যবসায়িক লাভ)') : __('NET OPERATING LOSS (নিট ব্যবসায়িক ক্ষতি)') }}
                        </div>
                        <div style="font-size: 0.8125rem; margin-top: 0.25rem; opacity: 0.9;">
                            {{ __('Gross Profit (৳' . number_format($data['gross_profit'], 2) . ') minus Operating Overheads (৳' . number_format($exp['total_expenses'], 2) . ')') }}
                        </div>
                    </div>
                    <div style="font-size: 1.875rem; font-weight: 900; font-variant-numeric: tabular-nums;">
                        ৳{{ number_format($data['net_profit'], 2) }}
                    </div>
                </div>
            </div>

            <!-- Right Column: Current Balances & Working Capital Position -->
            <div class="pnl-col-balances pnl-panel">
                <div class="pnl-panel-header">
                    <div>
                        <h2 class="pnl-panel-title">
                            {{ __('Current Balances & Assets (স্থাবর ও দেনা-পাওনা)') }}
                        </h2>
                        <p class="pnl-panel-desc">
                            {{ __('Real-time working capital position & godown stock') }}
                        </p>
                    </div>
                </div>

                <!-- Card 1: Customer Receivables -->
                <div class="pnl-balance-card receivables">
                    <div class="flex justify-between items-center">
                        <span class="pnl-balance-title text-rose-700 dark:text-rose-400">
                            {{ __('Customer Receivables (বাকী পাওনা)') }}
                        </span>
                        <x-heroicon-m-user-group class="w-5 h-5 text-rose-500" />
                    </div>
                    <div class="pnl-balance-amount text-rose-600 dark:text-rose-400">
                        ৳{{ number_format($bal['customer_dues'], 2) }}
                    </div>
                    <div class="pnl-balance-desc">
                        {{ __('Total outstanding credit distributed among fish farmers') }}
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs">
                        <span class="text-gray-500">{{ __('Customer Advance Balance:') }} ৳{{ number_format($bal['customer_advance'], 2) }}</span>
                        <a href="/admin/customer-aging-report" class="font-bold text-rose-600 dark:text-rose-400 hover:underline">
                            {{ __('Aging Report &rarr;') }}
                        </a>
                    </div>
                </div>

                <!-- Card 2: Supplier Payables -->
                <div class="pnl-balance-card payables">
                    <div class="flex justify-between items-center">
                        <span class="pnl-balance-title text-amber-700 dark:text-amber-400">
                            {{ __('Supplier Payables (কোম্পানির দেনা)') }}
                        </span>
                        <x-heroicon-m-building-storefront class="w-5 h-5 text-amber-500" />
                    </div>
                    <div class="pnl-balance-amount text-amber-600 dark:text-amber-400">
                        ৳{{ number_format($bal['supplier_payables'], 2) }}
                    </div>
                    <div class="pnl-balance-desc">
                        {{ __('Outstanding procurement commitments owed to feed mills') }}
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex justify-end text-xs">
                        <a href="/admin/supplier-ledger-report" class="font-bold text-amber-600 dark:text-amber-400 hover:underline">
                            {{ __('Supplier Ledger &rarr;') }}
                        </a>
                    </div>
                </div>

                <!-- Card 3: Closing Godown Stock Valuation -->
                <div class="pnl-balance-card inventory">
                    <div class="flex justify-between items-center">
                        <span class="pnl-balance-title text-sky-700 dark:text-sky-400">
                            {{ __('Closing Stock Valuation (মজুদ ফিডের মূল্য)') }}
                        </span>
                        <x-heroicon-m-archive-box class="w-5 h-5 text-sky-500" />
                    </div>
                    <div class="pnl-balance-amount text-sky-600 dark:text-sky-400">
                        ৳{{ number_format($bal['inventory_valuation'], 2) }}
                    </div>
                    <div class="pnl-balance-desc">
                        {{ __('Current total inventory in godown:') }} <strong class="text-gray-900 dark:text-white">{{ number_format($bal['total_stock_kg'], 1) }} KG</strong>
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex justify-end text-xs">
                        <a href="/admin/product-movement-report" class="font-bold text-sky-600 dark:text-sky-400 hover:underline">
                            {{ __('Stock Movement &rarr;') }}
                        </a>
                    </div>
                </div>

                <!-- Card 4: Staff Advance Due -->
                <div class="pnl-balance-card advances">
                    <div class="flex justify-between items-center">
                        <span class="pnl-balance-title text-purple-700 dark:text-purple-400">
                            {{ __('Staff Advances Outstanding (কর্মচারী অগ্রিম বকেয়া)') }}
                        </span>
                        <x-heroicon-m-banknotes class="w-5 h-5 text-purple-500" />
                    </div>
                    <div class="pnl-balance-amount text-purple-600 dark:text-purple-400">
                        ৳{{ number_format($bal['staff_advance_due'], 2) }}
                    </div>
                    <div class="pnl-balance-desc">
                        {{ __('Cash advances taken by staff pending upcoming monthly salary deductions') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
