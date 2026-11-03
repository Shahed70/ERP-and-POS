<?php

use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/sales/{sale}/print', [PrintController::class, 'printSale'])->name('sales.print');
    Route::get('/admin/payment-collections/{collection}/print', [PrintController::class, 'printReceipt'])->name('payment-collections.print');
    Route::get('/admin/reports/executive-summary/print', [PrintController::class, 'printExecutiveSummary'])->name('reports.executive.print');

    // Offline Synchronization API
    Route::get('/admin/offline-sync/data', [OfflineSyncController::class, 'getData'])->name('offline-sync.data');
    Route::post('/admin/offline-sync/sync', [OfflineSyncController::class, 'sync'])->name('offline-sync.sync');
    Route::get('/admin/offline-sync/ping', [OfflineSyncController::class, 'ping'])->name('offline-sync.ping');
});
