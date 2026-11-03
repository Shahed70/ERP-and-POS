@php
    $settings = \App\Models\ShopSetting::getSettings();
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice - {{ $sale->invoice_no }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ $settings->getLogoUrl() ?? asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Kalpurush', 'SolaimanLipi', 'Noto Sans Bengali', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 20px 10px;
            font-size: 13px;
            line-height: 1.4;
        }

        .no-print {
            max-width: 800px;
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

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header-section {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .shop-info h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .shop-info .sub-title {
            font-size: 13px;
            font-weight: 600;
            color: #0284c7;
            margin-top: 2px;
        }

        .shop-info .address {
            font-size: 12px;
            color: #475569;
            margin-top: 4px;
        }

        .invoice-badge-box {
            text-align: right;
        }

        .invoice-badge-box .title {
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .invoice-badge-box .meta-item {
            font-size: 12px;
            color: #334155;
            margin-top: 3px;
        }

        .invoice-badge-box .meta-item strong {
            color: #0f172a;
        }

        .customer-meta-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 16px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 18px;
        }

        .meta-col h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .meta-col .row {
            font-size: 13px;
            margin-bottom: 3px;
            display: flex;
            gap: 8px;
        }

        .meta-col .row .label {
            color: #64748b;
            min-width: 90px;
        }

        .meta-col .row .val {
            font-weight: 600;
            color: #0f172a;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        table.items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }

        table.items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12.5px;
        }

        table.items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .summary-wrapper {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .notes-box {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 12px;
            font-size: 12px;
            background-color: #f8fafc;
        }

        .notes-box h4 {
            font-size: 12px;
            color: #334155;
            margin-bottom: 4px;
        }

        table.summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.summary-table td {
            padding: 5px 8px;
            font-size: 13px;
        }

        table.summary-table tr.total-row td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-weight: 800;
            font-size: 15px;
            color: #0f172a;
            background-color: #f1f5f9;
        }

        table.summary-table tr.due-row td {
            font-weight: 700;
            font-size: 14px;
            color: #dc2626;
        }

        table.summary-table tr.paid-row td {
            color: #16a34a;
            font-weight: 700;
        }

        .footer-signatures {
            margin-top: 45px;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
        }

        .sig-box {
            text-align: center;
            border-top: 1px dashed #64748b;
            padding-top: 6px;
            width: 170px;
            font-size: 12px;
            color: #334155;
            font-weight: 600;
        }

        .terms-bar {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .invoice-card {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="javascript:window.history.back()" class="btn btn-secondary">&larr; Back to ERP</a>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Invoice (প্রিন্ট করুন)
            </button>
        </div>
    </div>

    <div class="invoice-card">
        <!-- Header -->
        <div class="header-section">
            <div class="shop-info">
                @if($settings->getLogoUrl())
                    <img src="{{ $settings->getLogoUrl() }}" style="max-height: 52px; max-width: 160px; object-fit: contain; margin-bottom: 6px;" alt="Logo"><br>
                @endif
                <h1>{{ $settings->shop_name }}</h1>
                <div class="sub-title">{{ $settings->shop_tagline }}</div>
                <div class="address">
                    {{ $settings->address }} | মোবাইল: {{ $settings->phone }}
                </div>
            </div>
            <div class="invoice-badge-box">
                <div class="title">SALES INVOICE</div>
                <div class="meta-item">Invoice #: <strong>{{ $sale->invoice_no }}</strong></div>
                <div class="meta-item">Date: <strong>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</strong></div>
                <div class="meta-item">Type: <strong>{{ strtoupper($sale->sale_type) }}</strong></div>
            </div>
        </div>

        <!-- Customer & Billing Meta -->
        <div class="customer-meta-grid">
            <div class="meta-col">
                <h3>Customer Information (ক্রেতার বিবরণ)</h3>
                <div class="row">
                    <span class="label">Name:</span>
                    <span class="val">{{ $sale->customer->name }}</span>
                </div>
                <div class="row">
                    <span class="label">Mobile:</span>
                    <span class="val">{{ $sale->customer->phone }}</span>
                </div>
                @if($sale->customer->address)
                <div class="row">
                    <span class="label">Address:</span>
                    <span class="val">{{ $sale->customer->address }}</span>
                </div>
                @endif
                @if($sale->customer->farm_details)
                <div class="row">
                    <span class="label">Farm/Project:</span>
                    <span class="val">{{ $sale->customer->farm_details }}</span>
                </div>
                @endif
            </div>

            <div class="meta-col">
                <h3>Invoice Details (চালানের বিবরণ)</h3>
                <div class="row">
                    <span class="label">Godown:</span>
                    <span class="val">{{ $sale->location->name ?? 'Main Shop' }}</span>
                </div>
                <div class="row">
                    <span class="label">Created By:</span>
                    <span class="val">{{ $sale->creator->name ?? 'Admin Staff' }}</span>
                </div>
                <div class="row">
                    <span class="label">Time:</span>
                    <span class="val">{{ $sale->created_at->format('h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">#</th>
                    <th>Feed Product (পণ্যের বিবরণ)</th>
                    <th class="text-center" style="width: 90px;">Unit Type</th>
                    <th class="text-center" style="width: 80px;">Quantity</th>
                    <th class="text-center" style="width: 80px;">Weight</th>
                    <th class="text-right" style="width: 100px;">Rate (৳)</th>
                    <th class="text-right" style="width: 110px;">Line Total (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <span class="font-bold">{{ $item->product->name }}</span>
                        <div style="font-size: 11px; color: #64748b;">
                            {{ $item->product->brand }} | Pack: {{ $item->pack_size }} KG
                        </div>
                    </td>
                    <td class="text-center">
                        <span style="display: inline-block; padding: 2px 6px; font-size: 11px; border-radius: 4px; background: {{ $item->unit_type === 'bag' ? '#e0f2fe' : '#fef3c7' }}; color: {{ $item->unit_type === 'bag' ? '#0369a1' : '#b45309' }}; font-weight: 600;">
                            {{ $item->unit_type === 'bag' ? 'Bag (বস্তা)' : 'Loose KG' }}
                        </span>
                    </td>
                    <td class="text-center font-bold">
                        {{ $item->unit_type === 'bag' ? (int) $item->quantity . ' Bags' : number_format($item->quantity, 2) . ' KG' }}
                    </td>
                    <td class="text-center font-bold" style="color: #0369a1;">
                        {{ number_format($item->deducted_weight_kg, 1) }} KG
                    </td>
                    <td class="text-right">৳{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right font-bold">৳{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary & Balance Box -->
        <div class="summary-wrapper">
            <div class="notes-box">
                <h4>Payment & Audit Notes:</h4>
                <p>{{ $sale->notes ?: 'Regular counter feed supply.' }}</p>

                @if($sale->is_credit_overridden)
                <div style="margin-top: 8px; color: #b91c1c; font-weight: 600; font-size: 11px;">
                    ⚠️ Credit ceiling overridden with authorized approval.
                </div>
                @endif

                <div style="margin-top: 12px; font-size: 11px; color: #64748b;">
                    Payment Method: <strong>{{ strtoupper($sale->payment_method ?: 'N/A') }}</strong>
                </div>
            </div>

            <div>
                <table class="summary-table">
                    <tr>
                        <td>Feed Subtotal:</td>
                        <td class="text-right font-bold">৳{{ number_format($sale->subtotal_amount, 2) }}</td>
                    </tr>
                    @if((float) $sale->discount_amount > 0)
                    <tr>
                        <td>Special Discount (ছাড়):</td>
                        <td class="text-right" style="color: #16a34a;">-৳{{ number_format($sale->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Net Bill Total:</td>
                        <td class="text-right">৳{{ number_format($sale->grand_total, 2) }}</td>
                    </tr>
                    <tr class="paid-row">
                        <td>Paid Amount (পরিশোধ):</td>
                        <td class="text-right">-৳{{ number_format($sale->paid_amount, 2) }}</td>
                    </tr>
                    <tr class="due-row">
                        <td>Invoice Due (চালানের বাকী):</td>
                        <td class="text-right">৳{{ number_format($sale->due_amount, 2) }}</td>
                    </tr>
                    @if((float) $sale->previous_due > 0)
                    <tr>
                        <td style="color: #64748b; font-size: 12px;">Previous Due (পূর্বের দেনা):</td>
                        <td class="text-right" style="color: #64748b; font-size: 12px;">৳{{ number_format($sale->previous_due, 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px dashed #cbd5e1; font-weight: 800; font-size: 13.5px; color: #b91c1c;">
                        <td>Total Customer Due (সর্বমোট দেনা):</td>
                        <td class="text-right">৳{{ number_format($sale->total_due_after_sale, 2) }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Signatures -->
        <div class="footer-signatures">
            <div class="sig-box">
                Customer's Signature<br>(ক্রেতার স্বাক্ষর)
            </div>
            <div class="sig-box">
                Authorized Signature<br>(বিক্রেতার স্বাক্ষর)
            </div>
        </div>

        <!-- Terms -->
        <div class="terms-bar">
            {{ $settings->invoice_footer_note }}
        </div>
    </div>

    <script>
        // Auto trigger print if query parameter ?autoprint=1 is passed
        if (window.location.search.includes('autoprint=1')) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
