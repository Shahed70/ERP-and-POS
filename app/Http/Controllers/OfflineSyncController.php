<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Services\SaleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncController extends Controller
{
    /**
     * Get snapshot of data for local browser offline cache (IndexedDB).
     */
    public function getData(): JsonResponse
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand ?? 'Feed',
                'feed_type' => $p->feed_type ?? 'floating',
                'category_name' => $p->category?->name ?? 'Fish Feed',
                'pack_size' => (float) ($p->pack_size ?? 25.00),
                'sale_price' => (float) $p->sale_price,
                'total_bags' => (int) $p->total_bags,
                'total_stock_kg' => (float) $p->total_stock_kg,
                'reorder_level_bags' => (int) ($p->reorder_level_bags ?? 20),
            ]);

        $customers = Customer::where('is_active', true)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'address' => $c->address ?? '',
                'current_due' => (float) $c->current_due,
                'credit_limit' => (float) $c->credit_limit,
                'advance_balance' => (float) $c->advance_balance,
            ]);

        $shop = ShopSetting::getSettings();

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'products' => $products,
            'customers' => $customers,
            'expense_categories' => Expense::getCategoryOptions(),
            'payment_methods' => Expense::getPaymentMethodOptions(),
            'shop' => [
                'name' => $shop->shop_name,
                'tagline' => $shop->shop_tagline,
                'phone' => $shop->phone,
                'address' => $shop->address,
                'logo_url' => $shop->getLogoUrl(),
            ],
        ]);
    }

    /**
     * Synchronize a batch of offline recorded transactions into the database.
     */
    public function sync(Request $request): JsonResponse
    {
        $items = $request->input('transactions', []);
        if (empty($items)) {
            return response()->json([
                'status' => 'success',
                'synced_count' => 0,
                'synced' => [],
                'errors' => [],
            ]);
        }

        $userId = auth()->id() ?? 1;
        $synced = [];
        $errors = [];

        foreach ($items as $item) {
            $offlineId = $item['offline_id'] ?? uniqid('off_');
            $type = $item['type'] ?? 'sale';

            try {
                if ($type === 'sale') {
                    $saleData = $item['data']['sale_data'] ?? [];
                    $itemsData = $item['data']['items_data'] ?? [];
                    $offlineInvoiceNo = $item['data']['offline_invoice_no'] ?? null;

                    if ($offlineInvoiceNo) {
                        $saleData['payment_notes'] = trim(($saleData['payment_notes'] ?? '') . " [Synced from Offline POS: {$offlineInvoiceNo}]");
                    }

                    $sale = SaleService::createSale(
                        saleData: $saleData,
                        itemsData: $itemsData,
                        userId: $userId,
                        allowCreditOverride: (bool) ($item['data']['allow_credit_override'] ?? true),
                        overrideNotes: 'Offline synchronization'
                    );

                    $synced[] = [
                        'offline_id' => $offlineId,
                        'type' => 'sale',
                        'server_id' => $sale->id,
                        'invoice_no' => $sale->invoice_no,
                        'amount' => (float) $sale->grand_total,
                        'customer_name' => $sale->customer?->name,
                        'message' => 'Sale invoice created successfully',
                    ];
                } elseif ($type === 'collection') {
                    $customerId = $item['data']['customer_id'] ?? null;
                    $amount = (float) ($item['data']['amount_paid'] ?? 0);
                    $method = $item['data']['payment_method'] ?? 'cash';
                    $ref = $item['data']['transaction_reference'] ?? null;
                    $notes = ($item['data']['notes'] ?? '') . ' [Synced from Offline Queue]';

                    $customer = Customer::findOrFail($customerId);

                    $collection = SaleService::collectPayment(
                        customer: $customer,
                        amountPaid: $amount,
                        paymentMethod: $method,
                        reference: $ref,
                        notes: $notes,
                        userId: $userId
                    );

                    $synced[] = [
                        'offline_id' => $offlineId,
                        'type' => 'collection',
                        'server_id' => $collection->id,
                        'receipt_no' => $collection->receipt_no,
                        'amount' => (float) $collection->amount_paid,
                        'customer_name' => $customer->name,
                        'message' => 'Due payment collection recorded successfully',
                    ];
                } elseif ($type === 'expense') {
                    $data = $item['data'] ?? [];
                    $expense = Expense::create([
                        'category' => $data['category'] ?? Expense::CATEGORY_OTHERS,
                        'title' => $data['title'] ?? 'Offline Operating Expense',
                        'amount' => (float) ($data['amount'] ?? 0),
                        'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'paid_to' => $data['paid_to'] ?? null,
                        'reference_no' => $data['reference_no'] ?? null,
                        'note' => trim(($data['note'] ?? '') . ' [Synced from Offline Queue]'),
                        'created_by' => $userId,
                    ]);

                    $synced[] = [
                        'offline_id' => $offlineId,
                        'type' => 'expense',
                        'server_id' => $expense->id,
                        'voucher_no' => $expense->expense_number,
                        'amount' => (float) $expense->amount,
                        'category' => $expense->category,
                        'message' => 'Operating expense synced successfully',
                    ];
                } else {
                    $errors[] = [
                        'offline_id' => $offlineId,
                        'error' => "Unknown transaction type: {$type}",
                    ];
                }
            } catch (Exception $e) {
                Log::warning("Offline sync error for {$offlineId}: " . $e->getMessage());
                $errors[] = [
                    'offline_id' => $offlineId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Return refreshed snapshot of products and customers
        $freshProducts = Product::where('is_active', true)->get()->map(fn ($p) => [
            'id' => $p->id,
            'total_bags' => (int) $p->total_bags,
            'total_stock_kg' => (float) $p->total_stock_kg,
        ]);
        $freshCustomers = Customer::where('is_active', true)->get(['id', 'current_due', 'advance_balance']);

        return response()->json([
            'status' => 'success',
            'synced_count' => count($synced),
            'synced' => $synced,
            'errors' => $errors,
            'fresh_products' => $freshProducts,
            'fresh_customers' => $freshCustomers,
        ]);
    }

    /**
     * Heartbeat ping to verify server connectivity.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'online',
            'server_time' => now()->timestamp,
        ]);
    }
}
