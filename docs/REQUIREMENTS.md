# Software Requirements Specification (SRS) & Architecture Blueprint
## Fish Feed Retail & Wholesale ERP System (Bangladesh Context)

**Version:** 1.1.0-FINAL-REQUIREMENTS  
**Author:** Senior Business Analyst & Laravel/Filament Architect  
**Status:** Approved by Client / Implementation Phase  
**Target Environment:** Local XAMPP MySQL / Laravel 11 / Filament v3  

---

## 1. Business Context & Domain Analysis

### 1.1 The Fish Feed Retail/Wholesale Ecosystem in Bangladesh
The aquaculture sector in Bangladesh is one of the most vibrant rural and semi-urban commercial enterprises. Fish feed retail and wholesale outlets ("মৎস্য খাদ্য ও মেডিসিন বিক্রয় কেন্দ্র") serve as the critical supply line between feed manufacturing conglomerates (e.g., Mega Feed, Quality Feed, ACI Godrej, CP Bangladesh, Nourish, Spectra Hexa) and local fish farmers (*Chashi* / ঘের ও পুকুর মালিক).

Key operational characteristics of this business domain include:
1. **Dual Selling Modalities (Sacks vs. Loose KG):**
   - Commercial fish farmers purchase feed in standard bulk bags/sacks (typically **25 kg** or **50 kg** sealed polypropylene woven sacks).
   - Small pond owners, homestead farmers, or retail buyers frequently purchase feed in **loose kilograms (খুচরা কেজি)** (e.g., 2 kg, 5 kg, 10 kg).
   - Retail shops unseal full sacks to sell loose feed. Stock is stored internally in **Kilograms (KG)** as the absolute base unit, and sack counts are derived dynamically (`Bags = Stock KG / Pack Size`).
2. **Feed Types & Feeding Lifecycle:**
   - Feeds are categorized by floating type (**Floating / ভাসমান**, **Sinking / ডুবন্ত**) and life-stage granule size (**Nursery / রেণু**, **Starter**, **Grower**, **Finisher**).
   - Prices fluctuate based on raw material costs (soya, fish meal) and company trade offers.
3. **Credit (Baki / বাকি) Culture & Cash Flow Realities:**
   - Fish farming is seasonal and harvest-dependent (typically 3 to 6-month cycles for Pangas, Tilapia, Carp, or Shrimp).
   - Farmers buy feed on ongoing credit (*Baki*). They make partial repayments periodically or lump-sum settlements after netting and harvesting fish (*মাছ বিক্রির পর পরিশোধ*).
   - Credit limit enforcement requires flexibility: an informative warning with Admin PIN/authorization override, with every override logged for audit.
4. **Supplier Procurement & Payables:**
   - Feeds are procured either directly from feed mills (company invoices) or regional master distributors/depots.
   - Purchases involve delivery challans, bulk trade discounts, carriage costs, and supplier payables with credit cycles.
5. **Human Resources & Operating Overheads:**
   - Outlets employ store attendants, loaders (*palla/bosta tana labour*), and sales clerks.
   - Employees take advance salaries (*Dadon/Advance*), monthly wages, and occasional bonuses, requiring ledger-level tracking.

---

## 2. User Roles & Permission Matrix

The application enforces strict **Role-Based Access Control (RBAC)** implemented using native Laravel Policies and Filament Shield.

### 2.1 Role Definitions
* **Admin (Shop Owner / Proprietor / স্বত্বাধিকারী):**
  - Complete control over financial ledgers, purchase costs, profit calculations, master settings, employee salaries, stock write-offs, and credit limit overrides.
  - Can view, edit, and void historical transactions subject to audit logs.
* **Staff (Sales Executive / Inventory Assistant / বিক্রয় ও স্টক কর্মী):**
  - Operational access restricted to day-to-day sales, customer creation, stock balance lookup, basic stock entry, and printing customer invoices.
  - **Strictly prohibited** from viewing purchase profit margins, altering historical prices, deleting sales/purchases, or viewing employee payroll and store-level financial reports.
  - Requires Admin PIN or supervisor confirmation when a customer's credit limit is exceeded.

### 2.2 Role Permission Matrix

| Module / Feature | Action | Admin (Owner) | Staff (Sales / Stock) | Business Rule / Constraint |
| :--- | :--- | :---: | :---: | :--- |
| **Dashboard** | Executive Metrics (Profit, Net Due) | ✅ Full | ❌ Hidden | Staff sees only daily sales count & low stock |
| **Product Master** | View / Search / Filter | ✅ Full | ✅ Full | Staff can check retail/wholesale rate |
| | Create / Edit Basic Details | ✅ Full | ❌ Restricted | Admin only or supervised staff |
| | View Purchase Cost Rate | ✅ Visible | ❌ Hidden | Cost price masked from counter staff |
| **Stock Management** | View Current Stock | ✅ Full | ✅ Full | Both Sack and KG visible |
| | Stock Adjustment / Write-off | ✅ Full | ❌ Forbidden | Damage/Loss adjustment requires Admin |
| **Customer Master** | Create / Edit Customer | ✅ Full | ✅ Full | Phone number unique validation |
| | View Total Due Balance | ✅ Full | ✅ Full | Visible during invoicing |
| | Adjust Opening Due | ✅ Full | ❌ Forbidden | Recorded via immutable opening balance entry |
| **Credit Override** | Override Credit Limit | ✅ Full (Logged) | ⚠️ Requires Admin PIN | Every override is logged to audit table |
| **Supplier Master** | View / Create / Manage | ✅ Full | ❌ Hidden | Hidden from retail counter staff |
| **Purchases** | Create New Purchase | ✅ Full | ⚠️ Stock Entry Only | Staff can enter received qty, not price |
| | View Purchase Invoices & Dues | ✅ Full | ❌ Forbidden | Financial trade details hidden |
| **Sales (POS)** | Create Sale (Cash / Credit) | ✅ Full | ✅ Full | Auto-deducts stock, updates ledger |
| | Apply Special Discounts | ✅ Unlimited | ⚠️ Capped (e.g. 2%) | Discount cap enforced for staff |
| | Sale Return / Refund | ✅ Full | ⚠️ Supervisor Approval | Intact bags only; due adjusted first |
| | Delete / Edit Completed Sale | ✅ Full (Logged) | ❌ Forbidden | Prevents cash leakage |
| **Due Collection** | Collect Due from Customer | ✅ Full | ✅ Full | Generates official Money Receipt; FIFO |
| **HR & Payroll** | Employee, Salary, Advance | ✅ Full | ❌ Forbidden | Confidential HR data |
| **Financial Reports** | Monthly Profit/Loss, Sales, Stock Value | ✅ Full | ❌ Forbidden | Confidential financial analytics |
| **Invoices** | Print / Re-print PDF & POS Slips | ✅ Full | ✅ Full | A4/A5 PDF primary, 80mm layout phased |
| **System Backup** | Export Database SQL Dump | ✅ Full | ❌ Forbidden | Security & disaster recovery |

---

## 3. Module Specifications

---

### 3.1 Product Category Management
* **Module Purpose:** Organizes feed items hierarchically into commercial and nutritional classifications.
* **Database Fields:**
  - `id`: Bigint, Primary Key, Auto-increment.
  - `name`: Varchar(100), Required, Unique (e.g., "Floating Feed (ভাসমান)", "Sinking Feed (ডুবন্ত)", "Nursery Feed", "Fish Medicine & Supplements").
  - `slug`: Varchar(120), Unique.
  - `description`: Text, Nullable.
  - `is_active`: Boolean, Default `true`.
  - `created_at`, `updated_at`: Timestamps.
* **Business Rules:**
  - Cannot delete a category containing active products.

---

### 3.2 Product Management
* **Module Purpose:** Master catalog for all fish feeds, nutritional supplements, and pond medicines.
* **Database Fields:**
  - `id`: Bigint, Primary Key.
  - `category_id`: Foreign Key (`product_categories.id`), Indexed.
  - `name`: Varchar(200), Required (e.g., "Mega Floating Grower 3.0mm").
  - `sku`: Varchar(50), Unique, Required (e.g., "MF-FG-25").
  - `brand`: Varchar(100), Required (e.g., "Mega", "Quality", "ACI Godrej", "CP", "Nourish").
  - `feed_type`: Enum/Varchar(30), Nullable (`floating`, `sinking`, `powder`, `medicine`).
  - `pellet_size`: Varchar(20), Nullable (e.g., "0.5mm", "1.0mm", "2.0mm", "3.0mm").
  - `unit`: Varchar(20), Default `'KG'` (Base unit is always KG).
  - `pack_size`: Decimal(8, 2), Required (Weight of a full sealed sack in KG, e.g., `25.00` or `50.00`).
  - `allow_loose_sale`: Boolean, Default `true` (Flags whether this feed can be unsealed and sold in loose kg).
  - `purchase_price`: Decimal(12, 2), Required (Cost price per bag).
  - `sale_price`: Decimal(12, 2), Required (Standard retail selling price per bag).
  - `loose_sale_price_per_kg`: Decimal(12, 2), Nullable (Price per KG for loose sales; default = `sale_price / pack_size`, manual override allowed).
  - `wholesale_price`: Decimal(12, 2), Nullable (Bulk discount rate for sub-dealers or large farm owners).
  - `reorder_level_bags`: Integer, Default `10` (Threshold in bags to trigger low-stock alerts).
  - `image_path`: Varchar(255), Nullable (Product photo/bag snapshot).
  - `is_active`: Boolean, Default `true`.
  - `created_at`, `updated_at`: Timestamps.
* **Business Rules:**
  - If `allow_loose_sale` is `false`, the POS screen disables loose KG selection for this item.
  - `loose_sale_price_per_kg` defaults to `sale_price / pack_size` on product creation/update if left blank.

---

### 3.3 Stock & Inventory Management (Location-Aware Architecture)
* **Module Purpose:** Real-time tracking of physical inventory stored in base unit **KG**, with location support (ready for future multi-godown expansion).
* **Database Fields:**
  - `locations`:
    - `id`: Bigint, Primary Key.
    - `name`: Varchar(100), Required (e.g., "Main Shop / বিক্রয় কেন্দ্র", "Central Godown / গুদাম").
    - `code`: Varchar(30), Unique (e.g., `SHOP-MAIN`, `GODOWN-1`).
    - `is_default`: Boolean, Default `false` (MVP default = Shop Main).
    - `address`: Text, Nullable.
    - `is_active`: Boolean, Default `true`.
  - `stock_balances`:
    - `id`: Bigint, Primary Key.
    - `location_id`: Foreign Key (`locations.id`), Default `1`.
    - `product_id`: Foreign Key (`products.id`).
    - `total_weight_kg`: Decimal(12, 2), Default `0.00` (The single source of truth for physical stock).
    - Unique constraint: `[location_id, product_id]`.
  - `stock_ledgers`:
    - `id`: Bigint, Primary Key.
    - `location_id`: Foreign Key (`locations.id`).
    - `product_id`: Foreign Key (`products.id`).
    - `transaction_type`: Enum (`purchase`, `sale`, `sale_return`, `purchase_return`, `damage_wastage`, `manual_adjustment_surplus`, `transfer_in`, `transfer_out`).
    - `reference_id`: Bigint, Nullable (Polymorphic ID of related Sale, Purchase, Adjustment).
    - `quantity_kg_change`: Decimal(12, 2), Required (Signed value: positive for incoming, negative for outgoing).
    - `running_balance_kg`: Decimal(12, 2), Required.
    - `unit_cost_price`: Decimal(12, 2), Required.
    - `notes_or_reason`: Text, Nullable.
    - `performed_by`: Foreign Key (`users.id`).
    - `created_at`: Timestamp.
  - `stock_adjustments`:
    - `id`: Bigint, Primary Key.
    - `adjustment_number`: Varchar(50), Unique (e.g., `ADJ-202610-001`).
    - `location_id`: Foreign Key (`locations.id`).
    - `product_id`: Foreign Key (`products.id`).
    - `type`: Enum (`damage`, `expired`, `rat_bite`, `theft`, `weighing_loss`, `surplus_found`).
    - `kg_deducted`: Decimal(10, 2), Required.
    - `reason`: Text, Required.
    - `authorized_by`: Foreign Key (`users.id`).
    - `created_at`: Timestamp.
* **Derived Bag & KG Display:**
  - $\text{Full Sacks} = \lfloor \frac{\text{total\_weight\_kg}}{\text{pack\_size}} \rfloor$
  - $\text{Loose KG} = \text{total\_weight\_kg} \pmod{\text{pack\_size}}$
  - Display format: `"42 Bags + 12.5 KG"`

---

### 3.4 Customer Management & Credit Limit Overrides
* **Module Purpose:** Manages fish farmers, commercial growers, sub-retailers, and walk-in cash customers, tracking debt, credit limits, and override logs.
* **Database Fields:**
  - `customers`:
    - `id`: Bigint, Primary Key.
    - `name`: Varchar(150), Required.
    - `phone`: Varchar(20), Required, Unique (BD format: `01XXXXXXXXX`).
    - `alternative_phone`: Varchar(20), Nullable.
    - `address`: Text, Nullable.
    - `farm_details`: Varchar(255), Nullable.
    - `credit_limit`: Decimal(12, 2), Default `0.00` (`0.00` = Unlimited credit allowed).
    - `opening_due`: Decimal(12, 2), Default `0.00` (Recorded as a distinct ledger transaction on registration).
    - `current_due`: Decimal(12, 2), Default `0.00` (Always matches running sum of customer ledger).
    - `is_active`: Boolean, Default `true`.
    - `created_at`, `updated_at`: Timestamps.
  - `customer_ledgers`:
    - `id`: Bigint, Primary Key.
    - `customer_id`: Foreign Key (`customers.id`).
    - `transaction_type`: Enum (`opening_due`, `sale_invoice`, `due_collection`, `sale_return_credit`, `refund_adjustment`).
    - `reference_id`: Bigint, Nullable.
    - `reference_no`: Varchar(50), Nullable (e.g., Invoice # or Money Receipt #).
    - `debit_amount`: Decimal(12, 2), Default `0.00` (Increases due, e.g. new invoice or opening due).
    - `credit_amount`: Decimal(12, 2), Default `0.00` (Decreases due, e.g. payment collection or return).
    - `running_due_balance`: Decimal(12, 2), Required.
    - `notes`: Text, Nullable.
    - `created_at`: Timestamp.
  - `credit_limit_overrides`:
    - `id`: Bigint, Primary Key.
    - `customer_id`: Foreign Key (`customers.id`).
    - `sale_id`: Foreign Key, Nullable.
    - `current_credit_limit`: Decimal(12, 2).
    - `existing_due`: Decimal(12, 2).
    - `attempted_invoice_amount`: Decimal(12, 2).
    - `authorized_by`: Foreign Key (`users.id`).
    - `authorized_at`: Timestamp.
    - `notes`: Text, Nullable.
* **Customer Detail Page (Filament):**
  - KPI Cards: Lifetime Sales, Current Due Balance, Total Paid, Last Purchase Date.
  - Product-Wise Purchase History (Date, Product, Qty Bags/KG, Rate, Total, Paid, Due).
  - Most-Bought Products (Ranked list).
  - Complete Statement of Account (Customer Ledger table).
  - Action Button: "Collect Due Payment" (FIFO or Manual invoice picker).

---

### 3.5 Supplier Management
* **Module Purpose:** Records feed manufacturers and wholesale distributors, tracking payables, product lines, and cost histories.
* **Database Fields:**
  - `suppliers`:
    - `id`: Bigint, Primary Key.
    - `company_name`: Varchar(150), Required.
    - `contact_person`: Varchar(100), Nullable.
    - `phone`: Varchar(20), Required.
    - `alternative_phone`: Varchar(20), Nullable.
    - `email`: Varchar(100), Nullable.
    - `address`: Text, Nullable.
    - `bank_details`: Text, Nullable.
    - `opening_payable`: Decimal(12, 2), Default `0.00`.
    - `current_payable`: Decimal(12, 2), Default `0.00`.
    - `is_active`: Boolean, Default `true`.
    - `created_at`, `updated_at`: Timestamps.
  - `product_supplier`:
    - `supplier_id`: Foreign Key.
    - `product_id`: Foreign Key.
    - `last_purchase_price`: Decimal(12, 2).
    - `last_purchased_at`: Timestamp.
  - `supplier_ledgers`:
    - `id`: Bigint, Primary Key.
    - `supplier_id`: Foreign Key (`suppliers.id`).
    - `transaction_type`: Enum (`opening_payable`, `purchase_bill`, `supplier_payment`, `purchase_return`).
    - `reference_no`: Varchar(50), Nullable.
    - `debit_amount`: Decimal(12, 2), Default `0.00` (Payments made to supplier; reduces payable).
    - `credit_amount`: Decimal(12, 2), Default `0.00` (Bills received; increases payable).
    - `running_payable_balance`: Decimal(12, 2), Required.
    - `created_at`: Timestamp.

---

### 3.6 Purchase Management (Procurement)
* **Module Purpose:** Records bulk consignments of feed sacks, increases warehouse inventory, tracks freight/labor overhead, and maintains supplier balances.
* **Database Fields:**
  - `purchases`:
    - `id`: Bigint, Primary Key.
    - `purchase_invoice_no`: Varchar(50), Unique (e.g., `PUR-202610-0042`).
    - `supplier_challan_no`: Varchar(100), Nullable.
    - `supplier_id`: Foreign Key (`suppliers.id`).
    - `location_id`: Foreign Key (`locations.id`), Default `1`.
    - `purchase_date`: Date, Required.
    - `subtotal_amount`: Decimal(12, 2), Required.
    - `transport_fare_labor_cost`: Decimal(10, 2), Default `0.00`.
    - `discount_amount`: Decimal(10, 2), Default `0.00`.
    - `grand_total`: Decimal(12, 2), Required.
    - `paid_amount`: Decimal(12, 2), Default `0.00`.
    - `due_amount`: Decimal(12, 2), Default `0.00`.
    - `payment_status`: Enum (`paid`, `partial`, `due`).
    - `payment_method`: Enum (`cash`, `bank_transfer`, `cheque`, `mfs_bkash_nagad`).
    - `notes`: Text, Nullable.
    - `created_by`: Foreign Key (`users.id`).
    - `created_at`, `updated_at`: Timestamps.
  - `purchase_items`:
    - `id`: Bigint, Primary Key.
    - `purchase_id`: Foreign Key (`purchases.id`, Cascade).
    - `product_id`: Foreign Key (`products.id`).
    - `quantity_bags`: Integer, Required.
    - `pack_size`: Decimal(8, 2), Required.
    - `total_weight_kg`: Decimal(10, 2), Required (`quantity_bags * pack_size`).
    - `purchase_rate_per_bag`: Decimal(12, 2), Required.
    - `line_total`: Decimal(12, 2), Required.

---

### 3.7 Sell Management & FIFO Due Collection
* **Module Purpose:** Handles counter and wholesale sales, manages cash/credit invoices, auto-deducts inventory in KG, enforces credit limits, and processes due collections via FIFO or manual selection.
* **Database Fields:**
  - `sales`:
    - `id`: Bigint, Primary Key.
    - `invoice_no`: Varchar(50), Unique (e.g., `INV-202610-0158`).
    - `customer_id`: Foreign Key (`customers.id`).
    - `location_id`: Foreign Key (`locations.id`), Default `1`.
    - `sale_date`: Date, Required.
    - `subtotal_amount`: Decimal(12, 2), Required.
    - `discount_amount`: Decimal(10, 2), Default `0.00`.
    - `grand_total`: Decimal(12, 2), Required.
    - `paid_amount`: Decimal(12, 2), Default `0.00`.
    - `due_amount`: Decimal(12, 2), Default `0.00`.
    - `unallocated_due`: Decimal(12, 2), Default `0.00` (Remaining unpaid balance on this specific invoice).
    - `previous_due`: Decimal(12, 2), Default `0.00`.
    - `total_due_after_sale`: Decimal(12, 2), Default `0.00`.
    - `payment_status`: Enum (`paid`, `partial`, `due`).
    - `payment_method`: Enum (`cash`, `bkash`, `nagad`, `bank_cheque`).
    - `sale_type`: Enum (`retail`, `wholesale`).
    - `is_credit_overridden`: Boolean, Default `false`.
    - `created_by`: Foreign Key (`users.id`).
    - `created_at`, `updated_at`: Timestamps.
  - `sale_items`:
    - `id`: Bigint, Primary Key.
    - `sale_id`: Foreign Key (`sales.id`, Cascade).
    - `product_id`: Foreign Key (`products.id`).
    - `unit_type`: Enum (`bag`, `kg`).
    - `quantity`: Decimal(10, 2), Required.
    - `pack_size`: Decimal(8, 2), Required.
    - `deducted_weight_kg`: Decimal(10, 2), Required (`unit_type == 'bag' ? quantity * pack_size : quantity`).
    - `unit_price`: Decimal(12, 2), Required.
    - `line_total`: Decimal(12, 2), Required.
    - `unit_cost_price`: Decimal(12, 2), Required (Historical snapshot for P&L).
  - `payment_collections`:
    - `id`: Bigint, Primary Key.
    - `receipt_no`: Varchar(50), Unique (e.g., `MR-202610-0089`).
    - `customer_id`: Foreign Key.
    - `payment_date`: Date, Required.
    - `amount_paid`: Decimal(12, 2), Required.
    - `allocation_mode`: Enum (`fifo_automatic`, `manual_selected_invoices`).
    - `payment_method`: Enum (`cash`, `bkash`, `nagad`, `bank_cheque`).
    - `transaction_reference`: Varchar(100), Nullable.
    - `collected_by`: Foreign Key (`users.id`).
    - `notes`: Text, Nullable.
  - `payment_collection_allocations`:
    - `id`: Bigint, Primary Key.
    - `payment_collection_id`: Foreign Key (`payment_collections.id`, Cascade).
    - `sale_id`: Foreign Key (`sales.id`).
    - `allocated_amount`: Decimal(12, 2), Required.

---

### 3.8 Sale Returns & Wastage Tracking
* **Module Purpose:** Processes returned goods with strict credit-first rules and physical stock validation.
* **Business Rules:**
  1. **Credit-First Settlement:** Return value is credited against the customer's outstanding balance first.
  2. If the customer has zero due or the return value exceeds their due, the remainder is either:
     - Credited to customer balance as an advance deposit (`advance_balance`), or
     - Refunded in cash (requires Admin approval PIN).
  3. **Physical Stock Integrity:**
     - **Intact Sealed Bags:** Returned to active stock (`stock_ledgers.transaction_type = 'sale_return'`).
     - **Opened / Damaged Goods:** Cannot be returned to active inventory; routed immediately to `stock_adjustments` (`type = 'damage_wastage'`) as an expense.
* **Database Fields:**
  - `sale_returns`:
    - `id`: Bigint, Primary Key.
    - `return_no`: Varchar(50), Unique (e.g., `RET-202610-0012`).
    - `sale_id`: Foreign Key (`sales.id`).
    - `customer_id`: Foreign Key (`customers.id`).
    - `return_date`: Date, Required.
    - `total_return_value`: Decimal(12, 2), Required.
    - `due_deduction_amount`: Decimal(12, 2), Default `0.00`.
    - `cash_refund_amount`: Decimal(12, 2), Default `0.00`.
    - `advance_credited_amount`: Decimal(12, 2), Default `0.00`.
    - `authorized_by`: Foreign Key (`users.id`).
    - `reason`: Text, Nullable.
  - `sale_return_items`:
    - `id`: Bigint, Primary Key.
    - `sale_return_id`: Foreign Key (`sale_returns.id`, Cascade).
    - `product_id`: Foreign Key (`products.id`).
    - `unit_type`: Enum (`bag`, `kg`).
    - `quantity`: Decimal(10, 2), Required.
    - `pack_size`: Decimal(8, 2), Required.
    - `condition`: Enum (`intact_sealed_bag`, `opened_damaged`).
    - `unit_price`: Decimal(12, 2), Required.
    - `line_total`: Decimal(12, 2), Required.

---

### 3.9 Employee Management & Payroll
* **Module Purpose:** Stores staff profiles, tracks monthly salaries, records cash advances, and executes payroll deductions.
* **Database Fields:**
  - `employees`: Name, Phone, NID, Role/Designation, Joining Date, Monthly Base Salary, Current Advance Due, Status.
  - `salary_advances`: Employee ID, Advance Date, Amount, Reason, Approved By.
  - `salaries`: Employee ID, Salary Month (`YYYY-MM`), Base Salary, Advance Deduction, Bonus, Other Deductions, Net Payable, Paid Amount, Due Amount, Disbursement Date, Method, Status.

---

### 3.10 Reporting Engine
* **Included Reports:**
  1. **Monthly Executive Summary:** Sales Revenue, COGS, Gross Profit, Operating Expenses (Salaries + Overheads), Net Profit, Total Customer Dues, Total Supplier Payables, Closing Inventory Valuation (in BDT).
  2. **Supplier-Wise Purchase & Ledger Report:** Volume and value of feeds supplied, historical rates, running payable balance.
  3. **Customer-Wise Sales & Aging Due Report:** Total sales, payments, outstanding due, aging buckets (>30, >60, >90 days).
  4. **Product-Wise Sales & Movement Report:** Sacks & KG sold, purchase cost vs sale revenue, realized gross margin.

---

### 3.11 Invoice & Receipt Generation
* **Phased Architecture:**
  - **Phase 1 (MVP Primary):** Print-friendly **A4 and A5 PDF Invoices** generated via `barryvdh/laravel-dompdf` with custom shop header (Name in Bangla/English, Address, Phone, Proprietor, Invoice No, Itemized Table, Cash Paid, Current Due, Previous Due, Total Due, Customer Signature).
  - **Phase 2 (Subsequent):** **80mm Thermal POS slip layout** formatted for compact thermal printers.

---

## 4. Technical Architecture & Non-Functional Constraints

* **Framework:** Laravel 11.x, PHP 8.2+ (Local environment: PHP 8.5.12).
* **Admin Panel:** Filament v3 (Panel Builder, Resource Tables, Forms, Infolists, Notifications).
* **Database (Strict):**
  - Host: `127.0.0.1`, Port: `3306`, User: `root`, Password: `""` (Empty), Database: `fish_feed_shop`.
  - Zero SQLite, zero cloud DB, zero mock/dummy data.
* **Base Unit of Measurement:** Kilogram (KG) as the absolute unit for all stock calculations.
* **Negative Stock Prevention:** Strict DB transactions with optimistic locking (`lockForUpdate`).
* **Localization & Currency:** Bangla + English strings, BDT currency (`৳` / Tk).
* **Backup:** One-click MySQL dump generation stored in `storage/app/backups/`.

---

## 5. MVP Scope vs. Later Phases

```
  ┌────────────────────────────────────────────────────────────────────────┐
  │                     PHASE 1: MVP (APPROVED SCOPE)                      │
  ├────────────────────────────────────────────────────────────────────────┤
  │ • Product & Category Master with "Allow Loose Sale" flag               │
  │ • Base Stock stored in KG with location schema (Default: Main Shop)    │
  │ • Customer Directory with Credit Limit & Warning/Admin PIN Override Log│
  │ • Supplier Directory & Product-Supplier Last Purchase Price Tracker    │
  │ • Purchase Management (Stock increase in KG, Supplier Due)             │
  │ • Sales Management (Cash/Credit, Dual Unit, Stock Decrement in KG)     │
  │ • Due Collection with FIFO allocation + Manual Invoice Picker          │
  │ • Sale Return (Due deduction first, Intact to stock, Damaged to waste) │
  │ • Employee Master, Advance & Monthly Payroll Generation                │
  │ • Print-Ready A4/A5 PDF Invoices with Due Summary & Shop Branding     │
  │ • Monthly P&L and Essential Financial Reports                          │
  │ • Role-Based Access Control (Admin vs. Staff)                          │
  │ • Clean Seeders (Super Admin + Default Location + Roles ONLY)          │
  └────────────────────────────────────────────────────────────────────────┘
                                     │
                                     ▼
  ┌────────────────────────────────────────────────────────────────────────┐
  │                 PHASE 2: ENHANCEMENTS & ADVANCED HARDWARE              │
  ├────────────────────────────────────────────────────────────────────────┤
  │ • 80mm / 58mm Thermal POS Printer Receipt Layout                       │
  │ • Multi-Location Godown Transfers & Stock Movements                    │
  │ • Automated SMS Gateway Integration (Bangla Due Reminders via API)     │
  │ • Barcode generation & Handheld Scanner Support                        │
  │ • Mobile PWA / Field Sales Android App                                 │
  └────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Resolved Architectural Decisions Log

| Decision Area | Agreed Specification |
| :--- | :--- |
| **Loose Sale & Unit Handling** | `allow_loose_sale` boolean per product. `loose_sale_price_per_kg` defaults to `sale_price / pack_size` with manual override. Stock stored in KG base unit. |
| **Credit Limit Enforcement** | Warning shown when limit exceeded (`credit_limit = 0` means unlimited). Staff can proceed with Admin PIN/authorization. Every override logged with timestamp and amount. |
| **Due Collection Allocation** | Default FIFO auto-allocation against oldest unpaid invoices, with option for user to manually select specific invoices. Opening due tracked as a distinct ledger transaction. |
| **Stock Location Architecture** | Schema includes `locations` table and `location_id` on stock balances/ledgers. MVP operates on single default location ("Main Shop"), ready for Godown support without DB alteration. |
| **Invoice Printing** | A4/A5 PDF invoice built first with full Bangla/English branding and due ledger breakdown. 80mm thermal receipt format scheduled for Phase 2. |
| **Sale Return Handling** | Return value adjusted against outstanding due first. Remainder to advance balance or cash (Admin approval). Sealed bags return to stock; damaged goods routed to wastage. |

---
*End of Requirements Specification Document.*
