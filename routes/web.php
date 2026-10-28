<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/sales/{sale}/print', [PrintController::class, 'printSale'])->name('sales.print');
    Route::get('/admin/payment-collections/{collection}/print', [PrintController::class, 'printReceipt'])->name('payment-collections.print');
    Route::get('/admin/reports/executive-summary/print', [PrintController::class, 'printExecutiveSummary'])->name('reports.executive.print');
});
