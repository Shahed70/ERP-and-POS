<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Receipt - {{ $collection->receipt_no }}</title>
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
            max-width: 750px;
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
            background-color: #16a34a;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #15803d;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .receipt-card {
            max-width: 750px;
            margin: 0 auto;
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .header-section {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .shop-info h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .shop-info .sub-title {
            font-size: 13px;
            font-weight: 600;
            color: #16a34a;
        }

        .shop-info .address {
            font-size: 12px;
            color: #475569;
            margin-top: 3px;
        }

        .receipt-badge-box {
            text-align: right;
        }

        .receipt-badge-box .title {
            font-size: 18px;
            font-weight: 800;
            background: #0f172a;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .receipt-badge-box .meta-item {
            font-size: 12px;
            color: #334155;
            margin-top: 3px;
        }

        .amount-highlight-box {
            background-color: #f0fdf4;
            border: 2px solid #16a34a;
            border-radius: 6px;
            padding: 14px 16px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .amount-highlight-box .label {
            font-size: 14px;
            font-weight: 700;
            color: #166534;
        }

        .amount-highlight-box .amount-val {
            font-size: 24px;
            font-weight: 900;
            color: #15803d;
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

        .meta-col .row {
            font-size: 13px;
            margin-bottom: 4px;
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

        table.allocations-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        table.allocations-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 10px;
            text-align: left;
        }

        table.allocations-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        .remaining-debt-box {
            text-align: right;
            padding: 10px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            margin-bottom: 24px;
        }

        .remaining-debt-box span {
            font-size: 13px;
            color: #991b1b;
            font-weight: 600;
        }

        .remaining-debt-box strong {
            font-size: 16px;
            color: #b91c1c;
            margin-left: 8px;
        }

        .footer-signatures {
            margin-top: 40px;
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

            .receipt-card {
                border: 1px solid #0f172a;
                box-shadow: none;
                padding: 15px;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="javascript:window.history.back()" class="btn btn-secondary">&larr; Back to ERP</a>
        <button onclick="window.print()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Money Receipt (রসিদ প্রিন্ট করুন)
        </button>
    </div>

    <div class="receipt-card">
        <!-- Header -->
        <div class="header-section">
            <div class="shop-info">
                <h1>বিসমিল্লাহ ফিশ ফিড স্টোর</h1>
                <div class="sub-title">Fish Feed Retail & Wholesale ERP</div>
                <div class="address">
                    মেগা, সিপি, কোয়ালিটি ও আফতাব ফিশ ফিডের অনুমোদিত ডিলার<br>
                    স্থান: প্রধান বাজার, ত্রিশাল, ময়মনসিংহ | মোবাইল: ০১৭১১-০০০০০০
                </div>
            </div>
            <div class="receipt-badge-box">
                <div class="title">MONEY RECEIPT</div>
                <div class="meta-item">Receipt #: <strong>{{ $collection->receipt_no }}</strong></div>
                <div class="meta-item">Date: <strong>{{ \Carbon\Carbon::parse($collection->payment_date)->format('d M Y') }}</strong></div>
            </div>
        </div>

        <!-- Highlight Box -->
        <div class="amount-highlight-box">
            <div class="label">Amount Received (আদায়কৃত অর্থ):</div>
            <div class="amount-val">৳{{ number_format($collection->amount_paid, 2) }}</div>
        </div>

        <!-- Meta -->
        <div class="customer-meta-grid">
            <div class="meta-col">
                <div class="row">
                    <span class="label">Customer:</span>
                    <span class="val">{{ $collection->customer->name }}</span>
                </div>
                <div class="row">
                    <span class="label">Phone:</span>
                    <span class="val">{{ $collection->customer->phone }}</span>
                </div>
                @if($collection->customer->address)
                <div class="row">
                    <span class="label">Address:</span>
                    <span class="val">{{ $collection->customer->address }}</span>
                </div>
                @endif
            </div>

            <div class="meta-col">
                <div class="row">
                    <span class="label">Payment Mode:</span>
                    <span class="val" style="text-transform: uppercase;">{{ $collection->payment_method }}</span>
                </div>
                @if($collection->transaction_reference)
                <div class="row">
                    <span class="label">Trx Reference:</span>
                    <span class="val">{{ $collection->transaction_reference }}</span>
                </div>
                @endif
                <div class="row">
                    <span class="label">Received By:</span>
                    <span class="val">{{ $collection->collector->name ?? 'Admin Staff' }}</span>
                </div>
            </div>
        </div>

        <!-- FIFO Allocations Table -->
        @if($collection->allocations->count() > 0)
        <h4 style="font-size: 12px; margin-bottom: 6px; color: #334155;">Settled Sales Invoices (যেসব চালানের বিপরীতে জমা হলো):</h4>
        <table class="allocations-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">#</th>
                    <th>Invoice #</th>
                    <th>Invoice Date</th>
                    <th class="text-right">Invoice Total</th>
                    <th class="text-right">Paid Today</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collection->allocations as $idx => $alloc)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-bold">{{ $alloc->sale->invoice_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($alloc->sale->sale_date)->format('d M Y') }}</td>
                    <td class="text-right">৳{{ number_format($alloc->sale->grand_total, 2) }}</td>
                    <td class="text-right font-bold" style="color: #16a34a;">৳{{ number_format($alloc->allocated_amount, 2) }}</td>
                    <td class="text-center">
                        <span style="font-size: 11px; font-weight: 700; color: {{ $alloc->sale->payment_status === 'paid' ? '#16a34a' : '#d97706' }};">
                            {{ strtoupper($alloc->sale->payment_status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Remaining Debt Box -->
        <div class="remaining-debt-box">
            <span>Customer Current Remaining Due (অবশিষ্ট বকেয়া দেনা):</span>
            <strong>৳{{ number_format($collection->customer->current_due, 2) }}</strong>
        </div>

        <!-- Signatures -->
        <div class="footer-signatures">
            <div class="sig-box">
                Payer's Signature<br>(প্রদানকারীর স্বাক্ষর)
            </div>
            <div class="sig-box">
                Receiver's Signature<br>(গ্রহীতার স্বাক্ষর)
            </div>
        </div>

        <!-- Terms -->
        <div class="terms-bar">
            ধন্যবাদ। বকেয়া টাকা পরিশোধের অফিসিয়াল অর্থ আদায় রসিদ। Software Powered by Fish Feed ERP
        </div>
    </div>

    <script>
        if (window.location.search.includes('autoprint=1')) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
