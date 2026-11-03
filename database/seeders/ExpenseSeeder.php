<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin?->id;

        $expenses = [
            // October 2026 Expenses
            [
                'expense_number' => 'EXP-202610-0001',
                'category' => Expense::CATEGORY_SHOP_RENT,
                'title' => 'October 2026 Main Road Shop Premise Rent (দোকান ভাড়া)',
                'amount' => 25000.00,
                'expense_date' => '2026-10-05',
                'payment_method' => 'bank',
                'paid_to' => 'Haji Abdul Barek (Landlord)',
                'reference_no' => 'CHQ-OCT-9901',
                'note' => 'Monthly shop lease payment through Sonali Bank cheque',
                'created_by' => $adminId,
            ],
            [
                'expense_number' => 'EXP-202610-0002',
                'category' => Expense::CATEGORY_WAREHOUSE_RENT,
                'title' => 'Rupsha Ghat Central Feed Godown Lease (গোডাউন ভাড়া)',
                'amount' => 18000.00,
                'expense_date' => '2026-10-06',
                'payment_method' => 'bank',
                'paid_to' => 'Khulna Godown Owners Syndicate',
                'reference_no' => 'VOUCHER-G-102',
                'note' => 'Storage facility lease for 600 bag capacity godown',
                'created_by' => $adminId,
            ],
            [
                'expense_number' => 'EXP-202610-0003',
                'category' => Expense::CATEGORY_ELECTRICITY_BILL,
                'title' => 'WZPDCL Commercial Electric Power Bill (বিদ্যুৎ বিল)',
                'amount' => 4850.00,
                'expense_date' => '2026-10-18',
                'payment_method' => 'bkash',
                'paid_to' => 'WZPDCL Electricity Board',
                'reference_no' => 'ELEC-2026-10-8821',
                'note' => 'Shop cooling fan, godown lighting & office computer power usage',
                'created_by' => $adminId,
            ],
            [
                'expense_number' => 'EXP-202610-0004',
                'category' => Expense::CATEGORY_SNACK_EXPENSES,
                'title' => 'Visiting Gher Farmers Tea, Biscuits & Water (আপ্যায়ন ও নাস্তা)',
                'amount' => 3200.00,
                'expense_date' => '2026-10-24',
                'payment_method' => 'cash',
                'paid_to' => 'Barek Tea Stall & General Store',
                'reference_no' => 'SNACK-OCT-BATCH',
                'note' => 'Daily refreshment for shrimp & tilapia farm owners visiting counter',
                'created_by' => $adminId,
            ],
            [
                'expense_number' => 'EXP-202610-0005',
                'category' => Expense::CATEGORY_OTHERS,
                'title' => 'Invoice Billbooks, Packaging Tape & Cleaning Supplies (বিবিধ খরচ)',
                'amount' => 1450.00,
                'expense_date' => '2026-10-27',
                'payment_method' => 'cash',
                'paid_to' => 'Khulna Stationers & Supplies',
                'reference_no' => 'STAT-2026-10',
                'note' => 'Printed duplicate bill pads, marker pens, sealing tape and godown broom',
                'created_by' => $adminId,
            ],

            // November 2026 Expenses (Current Month)
            [
                'expense_number' => 'EXP-202611-0001',
                'category' => Expense::CATEGORY_SHOP_RENT,
                'title' => 'November 2026 Main Road Shop Premise Rent (দোকান ভাড়া)',
                'amount' => 25000.00,
                'expense_date' => '2026-11-01',
                'payment_method' => 'bank',
                'paid_to' => 'Haji Abdul Barek (Landlord)',
                'reference_no' => 'CHQ-NOV-1024',
                'note' => 'November commercial rent settled at month opening',
                'created_by' => $adminId,
            ],
            [
                'expense_number' => 'EXP-202611-0002',
                'category' => Expense::CATEGORY_WAREHOUSE_RENT,
                'title' => 'November Rupsha Feed Godown Lease (গোডাউন ভাড়া)',
                'amount' => 18000.00,
                'expense_date' => '2026-11-02',
                'payment_method' => 'bank',
                'paid_to' => 'Khulna Godown Owners Syndicate',
                'reference_no' => 'VOUCHER-G-114',
                'note' => 'Godown lease for current harvest delivery holding',
                'created_by' => $adminId,
            ],
            [
                'expense_number' => 'EXP-202611-0003',
                'category' => Expense::CATEGORY_SNACK_EXPENSES,
                'title' => 'Client Entertainment & Staff Lunch/Snacks (নাস্তা ও আপ্যায়ন)',
                'amount' => 1650.00,
                'expense_date' => '2026-11-03',
                'payment_method' => 'cash',
                'paid_to' => 'Barek Tea Stall & Snacks',
                'reference_no' => 'CASH-NOV-03',
                'note' => 'Tea, snacks and mineral water during peak sales hours',
                'created_by' => $adminId,
            ],
            [
                'expense_number' => 'EXP-202611-0004',
                'category' => Expense::CATEGORY_OTHERS,
                'title' => 'Mobile Banking / Internet Router Bill (ওয়াইফাই ও ইন্টারনেট)',
                'amount' => 1200.00,
                'expense_date' => '2026-11-03',
                'payment_method' => 'bkash',
                'paid_to' => 'Link3 Technologies Khulna',
                'reference_no' => 'ISP-NOV-891',
                'note' => 'Broadband WiFi connection for POS counter & CCTV monitoring',
                'created_by' => $adminId,
            ],
        ];

        foreach ($expenses as $item) {
            Expense::firstOrCreate(
                ['expense_number' => $item['expense_number']],
                $item
            );
        }
    }
}
