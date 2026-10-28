<?php

namespace App\Http\Controllers;

use App\Models\PaymentCollection;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    /**
     * Display a clean, printer-ready Sales Invoice (POS / A4 / A5).
     */
    public function printSale(Sale $sale): View
    {
        $sale->load(['customer', 'location', 'creator', 'items.product']);

        return view('prints.sale-invoice', [
            'sale' => $sale,
        ]);
    }

    /**
     * Display a clean, printer-ready Money Receipt (Due Payment Collection).
     */
    public function printReceipt(PaymentCollection $collection): View
    {
        $collection->load(['customer', 'collector', 'allocations.sale']);

        return view('prints.payment-receipt', [
            'collection' => $collection,
        ]);
    }

    /**
     * Display a clean, printer-ready Monthly Executive P&L Summary Report.
     */
    public function printExecutiveSummary(Request $request): View
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $data = \App\Services\ReportService::getExecutiveSummary($from, $to);

        return view('prints.executive-summary', [
            'data' => $data,
        ]);
    }
}
