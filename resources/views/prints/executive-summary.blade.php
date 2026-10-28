@php
    $settings = \App\Models\ShopSetting::getSettings();
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Summary & P&L Statement - {{ $data['period']['formatted'] }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Kalpurush', 'SolaimanLipi', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 20px 10px;
            font-size: 13px;
            line-height: 1.5;
        }

        .no-print {
            max-width: 850px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .report-card {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 26px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .shop-info h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        .shop-info .sub-title {
            font-size: 13px;
            font-weight: 600;
            color: #0284c7;
        }

        .badge-box {
            text-align: right;
        }

        .badge-box .title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        .badge-box .period {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }

        table.financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.financial-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
            text-transform: uppercase;
            padding: 7px 10px;
            text-align: left;
        }

        table.financial-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        .section-header td {
            background-color: #f8fafc;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .total-row td {
            font-weight: 800;
            font-size: 14px;
            background-color: #f1f5f9;
        }

        .net-profit-row td {
            font-weight: 900;
            font-size: 16px;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            background-color: #ecfdf5;
            color: #065f46;
        }

        .text-right { text-align: right; }

        .balances-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 24px;
            border-top: 1px solid #cbd5e1;
            padding-top: 16px;
        }

        .bal-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            background-color: #f8fafc;
        }

        .bal-box .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        .bal-box .val {
            font-size: 17px;
            font-weight: 800;
            margin-top: 2px;
        }

        .footer-sig {
            margin-top: 45px;
            display: flex;
            justify-content: space-between;
            padding: 0 30px;
        }

        .sig {
            border-top: 1px dashed #64748b;
            width: 170px;
            text-align: center;
            font-size: 12px;
            padding-top: 4px;
            font-weight: 600;
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .no-print { display: none; }
            .report-card { border: none; box-shadow: none; padding: 0; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="javascript:window.history.back()" class="btn btn-secondary">&larr; Back to ERP</a>
        <button onclick="window.print()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Statement (প্রিন্ট করুন)
        </button>
    </div>

    <div class="report-card">
        <div class="header-section">
            <div class="shop-info">
                @if($settings->getLogoUrl())
                    <img src="{{ $settings->getLogoUrl() }}" style="max-height: 52px; max-width: 160px; object-fit: contain; margin-bottom: 6px;" alt="Logo"><br>
                @endif
                <h1>{{ $settings->shop_name }}</h1>
                <div class="sub-title">{{ $settings->shop_tagline }}</div>
                <div style="font-size: 11px; color: #475569; margin-top: 2px;">{{ $settings->address }} | ফোন: {{ $settings->phone }}</div>
            </div>
            <div class="badge-box">
                <div class="title">PROFIT & LOSS STATEMENT</div>
                <div class="period">Period: <strong>{{ $data['period']['formatted'] }}</strong></div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Generated: {{ now()->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        <table class="financial-table">
            <thead>
                <tr>
                    <th>Account Category / বিবরণী</th>
                    <th class="text-right" style="width: 170px;">Amount (৳ BDT)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="section-header">
                    <td colspan="2">1. Operating Revenue (বিক্রয় আয়)</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Gross Feed Sales (মোট ফিড বিক্রয়)</td>
                    <td class="text-right">৳{{ number_format($data['revenue']['gross_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Less: Special Discounts (বিশেষ কমিশন ও ছাড়)</td>
                    <td class="text-right" style="color: #dc2626;">-৳{{ number_format($data['revenue']['discounts'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Net Sales Revenue (নিট বিক্রয় মূল্য)</td>
                    <td class="text-right">৳{{ number_format($data['revenue']['net_sales'], 2) }}</td>
                </tr>

                <tr class="section-header">
                    <td colspan="2">2. Cost of Goods Sold (বিক্রীত পণ্যের ব্যয় / ফিড ক্রয়দর)</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Feed Procurement Cost (COGS)</td>
                    <td class="text-right" style="color: #dc2626;">-৳{{ number_format($data['cogs'], 2) }}</td>
                </tr>
                <tr class="total-row" style="background-color: #f0fdf4; color: #166534;">
                    <td>Gross Trading Profit (মোট মুনাফা) [{{ $data['gross_margin_percent'] }}%]</td>
                    <td class="text-right">৳{{ number_format($data['gross_profit'], 2) }}</td>
                </tr>

                <tr class="section-header">
                    <td colspan="2">3. Operating Overhead Expenses (পরিচালন ব্যয়)</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Staff Salaries & Allowances (কর্মচারী বেতন)</td>
                    <td class="text-right">-৳{{ number_format($data['expenses']['salaries_paid'], 2) }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Consignment Transport & Loading Labor (ট্রাক ভাড়া ও কুলি)</td>
                    <td class="text-right">-৳{{ number_format($data['expenses']['purchase_freight'], 2) }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Damage, Torn & Wastage Feed Loss (ক্ষতিগ্রস্ত পণ্য)</td>
                    <td class="text-right">-৳{{ number_format($data['expenses']['wastage_cost'], 2) }}</td>
                </tr>
                <tr class="total-row" style="color: #b91c1c;">
                    <td>Total Operating Expenses (মোট পরিচালন ব্যয়)</td>
                    <td class="text-right">-৳{{ number_format($data['expenses']['total_expenses'], 2) }}</td>
                </tr>

                <tr class="net-profit-row">
                    <td>NET OPERATING PROFIT / (LOSS) [নিট ব্যবসায়িক লাভ/লোকসান]</td>
                    <td class="text-right">৳{{ number_format($data['net_profit'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Asset & Balance Positions -->
        <h3 style="font-size: 13px; font-weight: 700; color: #334155; text-transform: uppercase; margin-top: 15px;">Balance Sheet & Working Capital Status:</h3>
        <div class="balances-grid">
            <div class="bal-box">
                <div class="label">Customer Receivables (বাকী)</div>
                <div class="val" style="color: #dc2626;">৳{{ number_format($data['balances']['customer_dues'], 2) }}</div>
            </div>
            <div class="bal-box">
                <div class="label">Supplier Payables (কোম্পানি দেনা)</div>
                <div class="val" style="color: #d97706;">৳{{ number_format($data['balances']['supplier_payables'], 2) }}</div>
            </div>
            <div class="bal-box">
                <div class="label">Closing Stock Valuation (মজুদ)</div>
                <div class="val" style="color: #0284c7;">৳{{ number_format($data['balances']['inventory_valuation'], 2) }}</div>
            </div>
        </div>

        <div class="footer-sig">
            <div class="sig">Accountant / Cashier</div>
            <div class="sig">Proprietor / Managing Director</div>
        </div>
    </div>
</body>
</html>
