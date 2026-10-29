<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentCollection;
use App\Models\PaymentCollectionAllocation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Salaries;
use App\Models\Salary;
use App\Models\SalaryAdvance;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\ShopSetting;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing business tables
        DB::table('sale_return_items')->truncate();
        DB::table('sale_returns')->truncate();
        DB::table('payment_collection_allocations')->truncate();
        DB::table('payment_collections')->truncate();
        DB::table('sale_items')->truncate();
        DB::table('sales')->truncate();
        DB::table('purchase_items')->truncate();
        DB::table('purchases')->truncate();
        DB::table('stock_adjustments')->truncate();
        DB::table('stock_ledgers')->truncate();
        DB::table('stock_balances')->truncate();
        DB::table('customer_ledgers')->truncate();
        DB::table('customers')->truncate();
        DB::table('supplier_ledgers')->truncate();
        DB::table('product_supplier')->truncate();
        DB::table('suppliers')->truncate();
        DB::table('products')->truncate();
        DB::table('product_categories')->truncate();
        DB::table('locations')->truncate();
        DB::table('salaries')->truncate();
        DB::table('salary_advances')->truncate();
        DB::table('employees')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Roles & Users
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);

        // Primary Administrator Credentials
        $admin = User::firstOrCreate(
            ['email' => 'amaremail234@gmail.com'],
            [
                'name' => 'Haji Rafiqul Islam (Shop Owner)',
                'password' => Hash::make('shahed@456'),
                'email_verified_at' => now(),
            ]
        );
        $admin->update([
            'password' => Hash::make('shahed@456'),
            'email_verified_at' => now(),
        ]);
        $admin->syncRoles([$adminRole]);

        // Legacy admin for existing automated test fixtures
        $legacyAdmin = User::firstOrCreate(
            ['email' => 'admin@fishfeed.com'],
            [
                'name' => 'Haji Rafiqul Islam (Shop Owner)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $legacyAdmin->update([
            'email_verified_at' => now(),
        ]);
        $legacyAdmin->syncRoles([$adminRole]);

        $viewer = User::firstOrCreate(
            ['email' => 'staff.fishfeed@gmail.com'],
            [
                'name' => 'Md. Shaheen (Cashier / Viewer)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $viewer->update([
            'email_verified_at' => now(),
        ]);
        $viewer->syncRoles([$viewerRole]);

        // 2. Shop Settings
        ShopSetting::updateOrCreate(
            ['id' => 1],
            [
                'shop_name' => 'বিসমিল্লাহ ফিশ ফিড স্টোর',
                'shop_tagline' => 'উন্নতমানের ভাসমান ও ডুবন্ত মাছের খাদ্য পাইকারী ও খুচরা বিক্রেতা',
                'proprietor_name' => 'হাজী মোঃ রফিকুল ইসলাম',
                'phone' => '০১৭১১-২২৩৩৪৪, ০১৯১১-৫৫৬৬৭৭',
                'email' => 'bismillahfeed@gmail.com',
                'address' => 'দোকান নং ১২, ফিড মার্কেট রোড, ত্রিশাল বাজার, ময়মনসিংহ',
                'currency_symbol' => '৳',
                'invoice_footer_note' => 'ধন্যবাদ আবার আসবেন। মাছের সঠিক বৃদ্ধি ও লাভের জন্য নির্ভরযোগ্য ফিড।',
            ]
        );

        // 3. Operational Locations
        $mainShop = Location::create([
            'id' => 1,
            'name' => 'Main Shop (প্রধান বিক্রয় কেন্দ্র)',
            'code' => 'MAIN-SHOP',
            'is_default' => true,
            'address' => 'ত্রিশাল বাজার, ময়মনসিংহ',
            'is_active' => true,
        ]);

        $depot = Location::create([
            'id' => 2,
            'name' => 'Godown Depot (ঘাটাইল ডিপো)',
            'code' => 'DEPOT-02',
            'is_default' => false,
            'address' => 'ঘাটাইল রোড, ত্রিশাল',
            'is_active' => true,
        ]);

        // 4. Product Categories
        $catFloating = ProductCategory::create([
            'name' => 'ভাসমান মাছের খাদ্য (Floating Feed)',
            'slug' => 'floating-feed',
            'description' => 'উন্নতমানের ভাসমান ফিড',
            'is_active' => true,
        ]);

        $catSinking = ProductCategory::create([
            'name' => 'ডুবন্ত মাছের খাদ্য (Sinking Feed)',
            'slug' => 'sinking-feed',
            'description' => 'শিং, মাগুর, কার্প ও তলদেশীয় মাছের ডুবন্ত ফিড',
            'is_active' => true,
        ]);

        $catNursery = ProductCategory::create([
            'name' => 'নার্সারি ও পোনা খাদ্য (Nursery Starter)',
            'slug' => 'nursery-starter',
            'description' => 'পোনা মাছ ও নার্সারির বিশেষ মাইক্রো দানাদার খাদ্য',
            'is_active' => true,
        ]);

        $catCatfish = ProductCategory::create([
            'name' => 'ক্যাটফিশ ও শিং-মাগুর ফিড (Catfish Special)',
            'slug' => 'catfish-special',
            'description' => 'উচ্চ প্রোটিনযুক্ত শিং, মাগুর ও গুলশা খাদ্য',
            'is_active' => true,
        ]);

        $catCarp = ProductCategory::create([
            'name' => 'কার্প ও রুই মিশ্রিত ফিড (Carp Grower)',
            'slug' => 'carp-grower',
            'description' => 'রুই, কাতলা, মৃগেল ও ব্রিগেড কার্প ফিড',
            'is_active' => true,
        ]);

        // 5. Feed Mill Suppliers
        $suppliersData = [
            [
                'company_name' => 'Mega Feed Ltd. (মেগা ফিড লিমিটেড)',
                'contact_person' => 'প্রকৌশলী শফিকুল ইসলাম (এরিয়া ইনচার্জ)',
                'phone' => '01711122334',
                'email' => 'sales@megafeedbd.com',
                'address' => 'ভালুকা শিল্পাঞ্চল, ময়মনসিংহ',
                'bank_details' => 'Islami Bank Bangladesh Ltd, Trishal Branch, A/C: 2050112233',
                'current_payable' => 85000.00,
            ],
            [
                'company_name' => 'CP Bangladesh Co. Ltd. (সিপি বাংলাদেশ)',
                'contact_person' => 'তানভীর আহমেদ (টেরিটরি অফিসার)',
                'phone' => '01722233445',
                'email' => 'sales@cpbangladesh.com',
                'address' => 'সিপি ফিড মিল, গাজীপুর',
                'bank_details' => 'Standard Chartered Bank, Gulshan, A/C: 0199887766',
                'current_payable' => 120000.00,
            ],
            [
                'company_name' => 'Quality Feeds Ltd. (কোয়ালিটি ফিডস)',
                'contact_person' => 'মোঃ কামরুল হাসান',
                'phone' => '01733344556',
                'email' => 'info@qualityfeeds.net',
                'address' => 'কোয়ালিটি ফিডস ফ্যাক্টরি, শ্রীপুর, গাজীপুর',
                'bank_details' => 'Dutch-Bangla Bank, Trishal Branch, A/C: 1101552233',
                'current_payable' => 50000.00,
            ],
            [
                'company_name' => 'Aftab Feed Products Ltd. (আফতাব ফিড)',
                'contact_person' => 'মোঃ সাজ্জাদ হোসেন',
                'phone' => '01744455667',
                'email' => 'aftabfeed@islamgroupbd.com',
                'address' => 'আফতাব বহুমুখী ফার্মস, ভাগলপুর, বাজিতপুর',
                'bank_details' => 'Brac Bank, Mymensingh Branch, A/C: 1501223344',
                'current_payable' => 0.00,
            ],
            [
                'company_name' => 'Nourish Poultry & Hatchery Ltd. (নারিশ ফিড)',
                'contact_person' => 'মাহাবুবুর রহমান (ডিলার অফিসার)',
                'phone' => '01755566778',
                'email' => 'feed@nourish-bd.com',
                'address' => 'নারিশ ফিড মিল কমপ্লেক্স, জামালপুর',
                'bank_details' => 'City Bank, Mymensingh Branch, A/C: 1122334455',
                'current_payable' => 30000.00,
            ],
        ];

        $suppliers = [];
        foreach ($suppliersData as $s) {
            $suppliers[] = Supplier::create($s);
        }

        // 6. Products Master (10 SKUs)
        $productsData = [
            [
                'category_id' => $catFloating->id,
                'name' => 'Mega Feed Pangas Grower 25kg (ভাসমান)',
                'sku' => 'SKU-MEGA-PAN-01',
                'brand' => 'Mega Feed',
                'feed_type' => 'floating',
                'pellet_size' => '2.0mm',
                'pack_size' => 25.00,
                'purchase_price' => 1550.00,
                'sale_price' => 1800.00,
                'loose_sale_price_per_kg' => 75.00,
                'wholesale_price' => 1720.00,
                'reorder_level_bags' => 25,
                'stock_bags' => 120, // 3,000 KG
            ],
            [
                'category_id' => $catFloating->id,
                'name' => 'Mega Feed Tilapia Starter 20kg (ভাসমান)',
                'sku' => 'SKU-MEGA-TIL-02',
                'brand' => 'Mega Feed',
                'feed_type' => 'floating',
                'pellet_size' => '1.0mm',
                'pack_size' => 20.00,
                'purchase_price' => 1600.00,
                'sale_price' => 1900.00,
                'loose_sale_price_per_kg' => 98.00,
                'wholesale_price' => 1820.00,
                'reorder_level_bags' => 30,
                'stock_bags' => 85, // 1,700 KG
            ],
            [
                'category_id' => $catFloating->id,
                'name' => 'CP Tilapia Grower Floating 25kg (ভাসমান)',
                'sku' => 'SKU-CP-TIL-03',
                'brand' => 'CP Bangladesh',
                'feed_type' => 'floating',
                'pellet_size' => '2.5mm',
                'pack_size' => 25.00,
                'purchase_price' => 1650.00,
                'sale_price' => 1950.00,
                'loose_sale_price_per_kg' => 80.00,
                'wholesale_price' => 1860.00,
                'reorder_level_bags' => 40,
                'stock_bags' => 95, // 2,375 KG
            ],
            [
                'category_id' => $catSinking->id,
                'name' => 'CP Shing & Magur Special 25kg (ডুবন্ত)',
                'sku' => 'SKU-CP-SHING-04',
                'brand' => 'CP Bangladesh',
                'feed_type' => 'sinking',
                'pellet_size' => '1.5mm',
                'pack_size' => 25.00,
                'purchase_price' => 2200.00,
                'sale_price' => 2600.00,
                'loose_sale_price_per_kg' => 110.00,
                'wholesale_price' => 2480.00,
                'reorder_level_bags' => 15,
                'stock_bags' => 45, // 1,125 KG
            ],
            [
                'category_id' => $catCarp->id,
                'name' => 'Quality Feed Carp Special 50kg (ডুবন্ত)',
                'sku' => 'SKU-QF-CARP-05',
                'brand' => 'Quality Feeds',
                'feed_type' => 'sinking',
                'pellet_size' => '3.0mm',
                'pack_size' => 50.00,
                'purchase_price' => 2900.00,
                'sale_price' => 3400.00,
                'loose_sale_price_per_kg' => 72.00,
                'wholesale_price' => 3250.00,
                'reorder_level_bags' => 20,
                'stock_bags' => 40, // 2,000 KG
            ],
            [
                'category_id' => $catFloating->id,
                'name' => 'Aftab Pangas Finisher 25kg (ভাসমান)',
                'sku' => 'SKU-AFT-PAN-06',
                'brand' => 'Aftab Feed',
                'feed_type' => 'floating',
                'pellet_size' => '3.0mm',
                'pack_size' => 25.00,
                'purchase_price' => 1500.00,
                'sale_price' => 1750.00,
                'loose_sale_price_per_kg' => 73.00,
                'wholesale_price' => 1680.00,
                'reorder_level_bags' => 25,
                'stock_bags' => 60, // 1,500 KG
            ],
            [
                'category_id' => $catFloating->id,
                'name' => 'Nourish Tilapia Floating Pellet 25kg (ভাসমান)',
                'sku' => 'SKU-NOUR-TIL-07',
                'brand' => 'Nourish Feed',
                'feed_type' => 'floating',
                'pellet_size' => '2.0mm',
                'pack_size' => 25.00,
                'purchase_price' => 1620.00,
                'sale_price' => 1900.00,
                'loose_sale_price_per_kg' => 78.00,
                'wholesale_price' => 1820.00,
                'reorder_level_bags' => 20,
                'stock_bags' => 50, // 1,250 KG
            ],
            // LOW STOCK ALERT SKU 1 (Critical)
            [
                'category_id' => $catNursery->id,
                'name' => 'ACI Godrej Shrimp Nursery 10kg (পোনা ফিড)',
                'sku' => 'SKU-ACI-SHR-08',
                'brand' => 'ACI Godrej',
                'feed_type' => 'floating',
                'pellet_size' => '0.5mm',
                'pack_size' => 10.00,
                'purchase_price' => 1850.00,
                'sale_price' => 2250.00,
                'loose_sale_price_per_kg' => 235.00,
                'wholesale_price' => 2150.00,
                'reorder_level_bags' => 25,
                'stock_bags' => 5, // Only 50 KG! Triggers Critical Alert
            ],
            // LOW STOCK ALERT SKU 2 (Low stock)
            [
                'category_id' => $catCatfish->id,
                'name' => 'Paragon Sinking Catfish Special 25kg (ডুবন্ত)',
                'sku' => 'SKU-PAR-CAT-09',
                'brand' => 'Paragon Feed',
                'feed_type' => 'sinking',
                'pellet_size' => '2.0mm',
                'pack_size' => 25.00,
                'purchase_price' => 1950.00,
                'sale_price' => 2300.00,
                'loose_sale_price_per_kg' => 96.00,
                'wholesale_price' => 2200.00,
                'reorder_level_bags' => 30,
                'stock_bags' => 8, // Only 200 KG! Triggers Low Stock Alert
            ],
            [
                'category_id' => $catNursery->id,
                'name' => 'Spectra Hexa Carp Nursery 20kg (পোনা ফিড)',
                'sku' => 'SKU-SPEC-CARP-10',
                'brand' => 'Spectra Hexa',
                'feed_type' => 'floating',
                'pellet_size' => '0.8mm',
                'pack_size' => 20.00,
                'purchase_price' => 1450.00,
                'sale_price' => 1700.00,
                'loose_sale_price_per_kg' => 88.00,
                'wholesale_price' => 1620.00,
                'reorder_level_bags' => 20,
                'stock_bags' => 45, // 900 KG
            ],
        ];

        $products = [];
        foreach ($productsData as $pd) {
            $stockBags = $pd['stock_bags'];
            unset($pd['stock_bags']);
            $product = Product::create($pd);
            $products[] = $product;

            // Link to primary supplier
            $supplier = match ($product->brand) {
                'Mega Feed' => $suppliers[0],
                'CP Bangladesh' => $suppliers[1],
                'Quality Feeds' => $suppliers[2],
                'Aftab Feed' => $suppliers[3],
                'Nourish Feed' => $suppliers[4],
                default => $suppliers[0],
            };
            $product->suppliers()->attach($supplier->id, [
                'last_purchase_price' => $product->purchase_price,
                'last_purchased_at' => now()->subDays(rand(2, 10)),
            ]);

            // Set initial stock in Main Shop
            $totalKg = $stockBags * (float) $product->pack_size;
            StockBalance::updateOrCreate([
                'location_id' => $mainShop->id,
                'product_id' => $product->id,
            ], [
                'total_weight_kg' => $totalKg,
            ]);

            StockLedger::create([
                'product_id' => $product->id,
                'location_id' => $mainShop->id,
                'transaction_type' => 'initial_opening',
                'reference_id' => 1,
                'reference_no' => 'INIT-' . $product->id,
                'quantity_kg_change' => $totalKg,
                'running_balance_kg' => $totalKg,
                'unit_cost_price' => $product->purchase_price,
                'notes_or_reason' => 'Initial godown opening stock allocation',
                'performed_by' => $admin->id,
                'created_at' => now()->subDays(15),
            ]);
        }

        // 7. Customers (Fish Farmers / Gher Owners)
        $customersData = [
            [
                'name' => 'Walk-in Cash Customer (নগদ খরিদ্দার)',
                'phone' => '01000000000',
                'address' => 'Counter Retail Walk-in',
                'credit_limit' => 0.00,
                'current_due' => 0.00,
                'advance_balance' => 0.00,
            ],
            [
                'name' => 'হাজী মোঃ রফিকুল ইসলাম (রফিক ফিশারিজ)',
                'phone' => '01712345001',
                'address' => 'কাঁঠাল ইউনিয়ন, ত্রিশাল, ময়মনসিংহ',
                'farm_details' => 'পাবদা ও গুলশা প্রজেক্ট (১২ একর)',
                'credit_limit' => 250000.00,
                'current_due' => 65000.00,
                'advance_balance' => 0.00,
            ],
            [
                'name' => 'তারেক হোসেন (মেঘনা ঘের প্রকল্প)',
                'phone' => '01712345002',
                'address' => 'মল্লিকবাড়ি, ভালুকা, ময়মনসিংহ',
                'farm_details' => 'পাঙ্গাস ও তেলাপিয়া খামার (৮ একর)',
                'credit_limit' => 180000.00,
                'current_due' => 110000.00,
                'advance_balance' => 0.00,
            ],
            [
                'name' => 'আলহাজ্ব আবুল কালাম (কালাম এগ্রো ফার্ম)',
                'phone' => '01712345003',
                'address' => 'পৌরসভা রোড, মুক্তাগাছা',
                'farm_details' => 'শিং ও মাগুর নার্সারি (১৫ একর)',
                'credit_limit' => 300000.00,
                'current_due' => 175000.00,
                'advance_balance' => 0.00,
            ],
            [
                'name' => 'মোঃ বাবুল মিয়া (বাবুল মৎস্য খামার)',
                'phone' => '01712345004',
                'address' => 'রাঙ্গামাটিয়া, ফুলবাড়িয়া',
                'farm_details' => 'রুই-কাতলা কার্প প্রজেক্ট (৫ একর)',
                'credit_limit' => 120000.00,
                'current_due' => 42000.00,
                'advance_balance' => 0.00,
            ],
            [
                'name' => 'শফিকুল আলম (শফিক হ্যাচারি অ্যান্ড ফিশারিজ)',
                'phone' => '01712345005',
                'address' => 'রসুলপুর, গফরগাঁও',
                'farm_details' => 'তেলাপিয়া মনোসেক্স হ্যাচারি (৭ একর)',
                'credit_limit' => 150000.00,
                'current_due' => 58000.00,
                'advance_balance' => 0.00,
            ],
            [
                'name' => 'মোঃ জাহাঙ্গীর আলম (আলম একুয়াকালচার)',
                'phone' => '01712345006',
                'address' => 'বৈলর, ত্রিশাল, ময়মনসিংহ',
                'farm_details' => 'কার্প ও পাঙ্গাস মিশ্র চাষ (১০ একর)',
                'credit_limit' => 200000.00,
                'current_due' => 35600.00,
                'advance_balance' => 0.00,
            ],
            [
                'name' => 'মোঃ কামাল উদ্দিন (কমল ফিশ ফার্ম)',
                'phone' => '01712345007',
                'address' => 'চণ্ডীপাশা, নান্দাইল',
                'farm_details' => 'গুলশা কমার্শিয়াল প্রজেক্ট (৪ একর)',
                'credit_limit' => 100000.00,
                'current_due' => 0.00,
                'advance_balance' => 5000.00, // Has advance credit
            ],
        ];

        $customers = [];
        foreach ($customersData as $cd) {
            $customer = Customer::create($cd);
            $customers[] = $customer;

            if ($customer->current_due > 0) {
                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'transaction_type' => 'opening_balance',
                    'reference_id' => null,
                    'reference_no' => 'OB-' . $customer->id,
                    'debit_amount' => $customer->current_due,
                    'credit_amount' => 0.00,
                    'running_due_balance' => $customer->current_due,
                    'notes' => 'Opening debt from harvest cycle',
                    'performed_by' => $admin->id,
                    'created_at' => now()->subDays(15),
                ]);
            }
        }

        // 8. Inward Mill Purchases (5 Consignments)
        $purchasesData = [
            [
                'supplier_idx' => 0, // Mega Feed
                'days_ago' => 12,
                'challan_no' => 'MF-CH-8821',
                'freight' => 4500.00,
                'items' => [
                    ['product_idx' => 0, 'bags' => 100, 'rate' => 1550.00],
                    ['product_idx' => 1, 'bags' => 50, 'rate' => 1600.00],
                ],
                'paid' => 150000.00,
            ],
            [
                'supplier_idx' => 1, // CP Bangladesh
                'days_ago' => 9,
                'challan_no' => 'CP-DK-4412',
                'freight' => 5000.00,
                'items' => [
                    ['product_idx' => 2, 'bags' => 80, 'rate' => 1650.00],
                    ['product_idx' => 3, 'bags' => 40, 'rate' => 2200.00],
                ],
                'paid' => 100000.00,
            ],
            [
                'supplier_idx' => 2, // Quality Feeds
                'days_ago' => 6,
                'challan_no' => 'QF-CH-1092',
                'freight' => 3500.00,
                'items' => [
                    ['product_idx' => 4, 'bags' => 50, 'rate' => 2900.00],
                ],
                'paid' => 95000.00,
            ],
            [
                'supplier_idx' => 3, // Aftab Feed
                'days_ago' => 3,
                'challan_no' => 'AFT-GT-9081',
                'freight' => 4000.00,
                'items' => [
                    ['product_idx' => 5, 'bags' => 60, 'rate' => 1500.00],
                ],
                'paid' => 94000.00, // Fully paid
            ],
            [
                'supplier_idx' => 4, // Nourish Feed
                'days_ago' => 1,
                'challan_no' => 'NOUR-CH-6554',
                'freight' => 3000.00,
                'items' => [
                    ['product_idx' => 6, 'bags' => 50, 'rate' => 1620.00],
                ],
                'paid' => 54000.00,
            ],
        ];

        foreach ($purchasesData as $idx => $pInfo) {
            $supplier = $suppliers[$pInfo['supplier_idx']];
            $date = now()->subDays($pInfo['days_ago'])->toDateString();

            $subtotal = 0.00;
            foreach ($pInfo['items'] as $item) {
                $subtotal += ($item['bags'] * $item['rate']);
            }
            $grandTotal = $subtotal + $pInfo['freight'];
            $paid = $pInfo['paid'];
            $due = $grandTotal - $paid;

            $purchase = Purchase::create([
                'purchase_invoice_no' => 'PUR-' . date('Ym', strtotime($date)) . '-' . sprintf('%04d', $idx + 1),
                'supplier_challan_no' => $pInfo['challan_no'],
                'supplier_id' => $supplier->id,
                'location_id' => $mainShop->id,
                'purchase_date' => $date,
                'subtotal_amount' => $subtotal,
                'transport_fare_labor_cost' => $pInfo['freight'],
                'discount_amount' => 0.00,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'payment_status' => $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
                'payment_method' => 'bank_transfer',
                'notes' => 'Consignment received in intact condition from mill truck.',
                'created_by' => $admin->id,
            ]);

            foreach ($pInfo['items'] as $item) {
                $prod = $products[$item['product_idx']];
                $weight = $item['bags'] * (float) $prod->pack_size;
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $prod->id,
                    'quantity_bags' => $item['bags'],
                    'pack_size' => $prod->pack_size,
                    'total_weight_kg' => $weight,
                    'purchase_rate_per_bag' => $item['rate'],
                    'line_total' => $item['bags'] * $item['rate'],
                ]);
            }

            // Supplier ledger bill entry
            SupplierLedger::create([
                'supplier_id' => $supplier->id,
                'transaction_type' => 'purchase_bill',
                'reference_id' => $purchase->id,
                'reference_no' => $purchase->purchase_invoice_no,
                'debit_amount' => 0.00,
                'credit_amount' => $grandTotal,
                'running_payable_balance' => $grandTotal,
                'notes' => 'Inward procurement bill challan ' . $purchase->supplier_challan_no,
                'performed_by' => $admin->id,
                'created_at' => Carbon::parse($date)->setTime(10, 30),
            ]);

            // Supplier ledger payment entry
            if ($paid > 0) {
                SupplierLedger::create([
                    'supplier_id' => $supplier->id,
                    'transaction_type' => 'payment_outward',
                    'reference_id' => $purchase->id,
                    'reference_no' => 'PAY-' . $purchase->id,
                    'debit_amount' => $paid,
                    'credit_amount' => 0.00,
                    'running_payable_balance' => $due,
                    'payment_method' => 'bank_transfer',
                    'notes' => 'Settlement against purchase invoice ' . $purchase->purchase_invoice_no,
                    'performed_by' => $admin->id,
                    'created_at' => Carbon::parse($date)->setTime(14, 0),
                ]);
            }
        }

        // 9. Sales Transactions across the last 7 days + Today (15 Invoices)
        $salesList = [
            // Today (4 sales)
            ['days_ago' => 0, 'customer_idx' => 1, 'items' => [['prod' => 0, 'unit' => 'bag', 'qty' => 15], ['prod' => 1, 'unit' => 'bag', 'qty' => 5]], 'paid' => 20000.00, 'method' => 'cash'],
            ['days_ago' => 0, 'customer_idx' => 0, 'items' => [['prod' => 2, 'unit' => 'bag', 'qty' => 4], ['prod' => 2, 'unit' => 'kg', 'qty' => 10]], 'paid' => 8600.00, 'method' => 'cash'], // Walk-in Cash
            ['days_ago' => 0, 'customer_idx' => 2, 'items' => [['prod' => 0, 'unit' => 'bag', 'qty' => 20]], 'paid' => 15000.00, 'method' => 'bkash'],
            ['days_ago' => 0, 'customer_idx' => 3, 'items' => [['prod' => 3, 'unit' => 'bag', 'qty' => 10]], 'paid' => 10000.00, 'method' => 'cash'],

            // 1 Day Ago (Yesterday) (2 sales)
            ['days_ago' => 1, 'customer_idx' => 4, 'items' => [['prod' => 4, 'unit' => 'bag', 'qty' => 8]], 'paid' => 15000.00, 'method' => 'cash'],
            ['days_ago' => 1, 'customer_idx' => 0, 'items' => [['prod' => 5, 'unit' => 'bag', 'qty' => 6]], 'paid' => 10500.00, 'method' => 'cash'],

            // 2 Days Ago (2 sales)
            ['days_ago' => 2, 'customer_idx' => 5, 'items' => [['prod' => 1, 'unit' => 'bag', 'qty' => 12]], 'paid' => 12000.00, 'method' => 'bkash'],
            ['days_ago' => 2, 'customer_idx' => 6, 'items' => [['prod' => 0, 'unit' => 'bag', 'qty' => 10], ['prod' => 6, 'unit' => 'kg', 'qty' => 15]], 'paid' => 10000.00, 'method' => 'cash'],

            // 3 Days Ago (2 sales)
            ['days_ago' => 3, 'customer_idx' => 2, 'items' => [['prod' => 2, 'unit' => 'bag', 'qty' => 16]], 'paid' => 20000.00, 'method' => 'bank_cheque'],
            ['days_ago' => 3, 'customer_idx' => 0, 'items' => [['prod' => 3, 'unit' => 'kg', 'qty' => 25]], 'paid' => 2750.00, 'method' => 'cash'],

            // 4 Days Ago (2 sales)
            ['days_ago' => 4, 'customer_idx' => 3, 'items' => [['prod' => 3, 'unit' => 'bag', 'qty' => 12]], 'paid' => 20000.00, 'method' => 'cash'],
            ['days_ago' => 4, 'customer_idx' => 1, 'items' => [['prod' => 0, 'unit' => 'bag', 'qty' => 8]], 'paid' => 14400.00, 'method' => 'cash'],

            // 5 Days Ago (2 sales)
            ['days_ago' => 5, 'customer_idx' => 4, 'items' => [['prod' => 4, 'unit' => 'bag', 'qty' => 10]], 'paid' => 20000.00, 'method' => 'cash'],
            ['days_ago' => 5, 'customer_idx' => 0, 'items' => [['prod' => 1, 'unit' => 'bag', 'qty' => 5]], 'paid' => 9500.00, 'method' => 'cash'],

            // 6 Days Ago (1 sale)
            ['days_ago' => 6, 'customer_idx' => 6, 'items' => [['prod' => 6, 'unit' => 'bag', 'qty' => 14]], 'paid' => 15000.00, 'method' => 'bkash'],
        ];

        foreach ($salesList as $idx => $sInfo) {
            $customer = $customers[$sInfo['customer_idx']];
            $date = now()->subDays($sInfo['days_ago'])->toDateString();
            $invNo = 'INV-' . date('Ym', strtotime($date)) . '-' . sprintf('%04d', $idx + 1);

            $grandTotal = 0.00;
            $itemsToCreate = [];

            foreach ($sInfo['items'] as $item) {
                $prod = $products[$item['prod']];
                $isBag = $item['unit'] === 'bag';
                $qty = (float) $item['qty'];
                $unitPrice = $isBag ? (float) $prod->sale_price : (float) ($prod->loose_sale_price_per_kg ?? 80.00);
                $lineTotal = $qty * $unitPrice;
                $deductedKg = $isBag ? ($qty * (float) $prod->pack_size) : $qty;
                $unitCost = $isBag ? (float) $prod->purchase_price : ((float) $prod->purchase_price / (float) $prod->pack_size);

                $grandTotal += $lineTotal;

                $itemsToCreate[] = [
                    'product_id' => $prod->id,
                    'unit_type' => $item['unit'],
                    'quantity' => $qty,
                    'pack_size' => $prod->pack_size,
                    'deducted_weight_kg' => $deductedKg,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'unit_cost_price' => $unitCost,
                ];
            }

            $paid = min((float) $sInfo['paid'], $grandTotal);
            $due = $grandTotal - $paid;

            $sale = Sale::create([
                'invoice_no' => $invNo,
                'customer_id' => $customer->id,
                'location_id' => $mainShop->id,
                'sale_date' => $date,
                'subtotal_amount' => $grandTotal,
                'discount_amount' => 0.00,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'unallocated_due' => $due,
                'previous_due' => (float) $customer->current_due,
                'total_due_after_sale' => (float) $customer->current_due + $due,
                'payment_status' => $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
                'payment_method' => $sInfo['method'],
                'sale_type' => 'retail',
                'is_credit_overridden' => false,
                'notes' => 'Dispatched from Trishal Counter',
                'created_by' => $admin->id,
                'created_at' => Carbon::parse($date)->setTime(rand(9, 17), rand(10, 50)),
            ]);

            foreach ($itemsToCreate as $it) {
                $it['sale_id'] = $sale->id;
                SaleItem::create($it);
            }

            // Customer ledger record
            if ($customer->id > 1) { // Skip walk-in cash customer from ledger
                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'transaction_type' => 'sale_invoice',
                    'reference_id' => $sale->id,
                    'reference_no' => $sale->invoice_no,
                    'debit_amount' => $grandTotal,
                    'credit_amount' => $paid,
                    'running_due_balance' => (float) $customer->current_due + $due,
                    'payment_method' => $sInfo['method'],
                    'notes' => 'Sale invoice billing ' . $sale->invoice_no,
                    'performed_by' => $admin->id,
                    'created_at' => Carbon::parse($date)->setTime(rand(9, 17), rand(10, 50)),
                ]);
            }
        }

        // 10. Due Payment Collections (4 Receipts)
        $collectionsData = [
            ['customer_idx' => 1, 'amount' => 25000.00, 'method' => 'cash', 'days_ago' => 5],
            ['customer_idx' => 2, 'amount' => 35000.00, 'method' => 'bkash', 'days_ago' => 3],
            ['customer_idx' => 3, 'amount' => 50000.00, 'method' => 'bank_cheque', 'days_ago' => 2],
            ['customer_idx' => 4, 'amount' => 18000.00, 'method' => 'cash', 'days_ago' => 0], // Collected today!
        ];

        foreach ($collectionsData as $idx => $cRec) {
            $customer = $customers[$cRec['customer_idx']];
            $date = now()->subDays($cRec['days_ago'])->toDateString();
            $receiptNo = 'MR-' . date('Ym', strtotime($date)) . '-' . sprintf('%04d', $idx + 1);

            $pc = PaymentCollection::create([
                'receipt_no' => $receiptNo,
                'customer_id' => $customer->id,
                'payment_date' => $date,
                'amount_paid' => $cRec['amount'],
                'allocation_mode' => 'fifo_automatic',
                'payment_method' => $cRec['method'],
                'transaction_reference' => 'REF-' . rand(10000, 99999),
                'collected_by' => $admin->id,
                'notes' => 'Cash collection received from farmer during pond visit.',
                'created_at' => Carbon::parse($date)->setTime(16, 30),
            ]);

            CustomerLedger::create([
                'customer_id' => $customer->id,
                'transaction_type' => 'payment_collection',
                'reference_id' => $pc->id,
                'reference_no' => $pc->receipt_no,
                'debit_amount' => 0.00,
                'credit_amount' => $cRec['amount'],
                'running_due_balance' => max(0, (float) $customer->current_due - $cRec['amount']),
                'payment_method' => $cRec['method'],
                'notes' => 'Money receipt ' . $pc->receipt_no,
                'performed_by' => $admin->id,
                'created_at' => Carbon::parse($date)->setTime(16, 30),
            ]);
        }

        // 11. Sales Return & Wastage
        // Intact return
        $sampleSale = Sale::where('customer_id', $customers[1]->id)->first();
        if ($sampleSale) {
            $return = SaleReturn::create([
                'sale_id' => $sampleSale->id,
                'customer_id' => $sampleSale->customer_id,
                'location_id' => $mainShop->id,
                'return_no' => 'RET-202610-0001',
                'return_date' => now()->subDays(2)->toDateString(),
                'total_return_value' => 3600.00,
                'due_deduction_amount' => 0.00,
                'cash_refund_amount' => 0.00,
                'advance_credited_amount' => 3600.00,
                'reason' => 'Farmer ordered 2 extra bags of Tilapia feed, returned in sealed condition.',
                'authorized_by' => $admin->id,
            ]);

            SaleReturnItem::create([
                'sale_return_id' => $return->id,
                'product_id' => $products[0]->id,
                'unit_type' => 'bag',
                'quantity' => 2,
                'pack_size' => $products[0]->pack_size,
                'condition' => 'intact_sealed_bag',
                'unit_price' => 1800.00,
                'line_total' => 3600.00,
            ]);
        }

        // Godown damaged feed write-off (3 bags rat bite)
        StockAdjustment::create([
            'adjustment_number' => 'ADJ-202610-0001',
            'product_id' => $products[0]->id,
            'location_id' => $mainShop->id,
            'type' => 'damage',
            'mode' => 'decrease',
            'unit_type' => 'bag',
            'input_quantity' => 3.00,
            'quantity_kg' => 75.00,
            'reason' => '3 bags torn during warehouse truck unloading and rodent infestation.',
            'authorized_by' => $admin->id,
            'created_at' => now()->subDays(4),
        ]);

        // 12. Employees & Payroll
        $emp1 = Employee::create([
            'name' => 'মোঃ শাহীন আলম (ম্যানেজার ও ক্যাশিয়ার)',
            'phone' => '01811223301',
            'nid' => '19882691234567890',
            'designation' => 'Store Manager & Head Cashier',
            'joining_date' => '2023-01-01',
            'base_salary' => 22000.00,
            'current_advance_due' => 0.00,
            'address' => 'ত্রিশাল পৌরসভা, ময়মনসিংহ',
            'is_active' => true,
        ]);

        $emp2 = Employee::create([
            'name' => 'মোঃ করিম মিয়া (গোডাউন ইনচার্জ)',
            'phone' => '01811223302',
            'nid' => '19922691234567891',
            'designation' => 'Warehouse Supervisor',
            'joining_date' => '2023-06-15',
            'base_salary' => 15000.00,
            'current_advance_due' => 3000.00, // Has 3,000 advance
            'address' => 'বগার বাজার, ত্রিশাল',
            'is_active' => true,
        ]);

        $emp3 = Employee::create([
            'name' => 'মোঃ বিল্লাল হোসেন (ডেলিভারি সহকারী)',
            'phone' => '01811223303',
            'nid' => '19952691234567892',
            'designation' => 'Delivery Assistant & Labor',
            'joining_date' => '2024-02-01',
            'base_salary' => 12000.00,
            'current_advance_due' => 2000.00, // Has 2,000 advance
            'address' => 'পাঁচপাড়া, ত্রিশাল',
            'is_active' => true,
        ]);

        // Cash advances
        SalaryAdvance::create([
            'advance_no' => 'ADV-202610-0001',
            'employee_id' => $emp2->id,
            'advance_date' => now()->subDays(15)->toDateString(),
            'amount' => 3000.00,
            'payment_method' => 'cash',
            'reason' => 'Family medical emergency advance',
            'approved_by' => $admin->id,
        ]);

        SalaryAdvance::create([
            'advance_no' => 'ADV-202610-0002',
            'employee_id' => $emp3->id,
            'advance_date' => now()->subDays(12)->toDateString(),
            'amount' => 2000.00,
            'payment_method' => 'cash',
            'reason' => 'House repair cash advance',
            'approved_by' => $admin->id,
        ]);

        // Disbursed salary for previous month (September 2026)
        Salary::create([
            'salary_slip_no' => 'PAY-202609-0001',
            'employee_id' => $emp1->id,
            'salary_month' => '2026-09',
            'base_salary' => 22000.00,
            'bonus_amount' => 1000.00,
            'advance_deduction' => 0.00,
            'other_deductions' => 0.00,
            'net_payable' => 23000.00,
            'paid_amount' => 23000.00,
            'due_amount' => 0.00,
            'disbursement_date' => '2026-10-05',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'notes' => 'September salary disbursed via bank transfer',
            'processed_by' => $admin->id,
        ]);
    }
}
