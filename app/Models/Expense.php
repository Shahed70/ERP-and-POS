<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const CATEGORY_SHOP_RENT = 'shop_rent';
    public const CATEGORY_ELECTRICITY_BILL = 'electricity_bill';
    public const CATEGORY_WAREHOUSE_RENT = 'warehouse_rent';
    public const CATEGORY_SNACK_EXPENSES = 'snack_expenses';
    public const CATEGORY_OTHERS = 'others';

    public const CATEGORIES = [
        self::CATEGORY_SHOP_RENT,
        self::CATEGORY_ELECTRICITY_BILL,
        self::CATEGORY_WAREHOUSE_RENT,
        self::CATEGORY_SNACK_EXPENSES,
        self::CATEGORY_OTHERS,
    ];

    protected $fillable = [
        'expense_number',
        'category',
        'title',
        'amount',
        'expense_date',
        'payment_method',
        'paid_to',
        'reference_no',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (empty($expense->expense_number)) {
                $datePrefix = now()->format('Ym');
                $latest = static::where('expense_number', 'like', "EXP-{$datePrefix}-%")
                    ->latest('id')
                    ->first();

                $sequence = 1;
                if ($latest && preg_match("/EXP-{$datePrefix}-(\d+)/", $latest->expense_number, $matches)) {
                    $sequence = ((int) $matches[1]) + 1;
                }

                $expense->expense_number = sprintf('EXP-%s-%04d', $datePrefix, $sequence);
            }

            if (empty($expense->created_by) && auth()->check()) {
                $expense->created_by = auth()->id();
            }
        });
    }

    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_SHOP_RENT => __('Shop Rent (দোকান ভাড়া)'),
            self::CATEGORY_ELECTRICITY_BILL => __('Electricity Bill (বিদ্যুৎ বিল)'),
            self::CATEGORY_WAREHOUSE_RENT => __('Warehouse Rent (গোডাউন ভাড়া)'),
            self::CATEGORY_SNACK_EXPENSES => __('Snack Expenses (আপ্যায়ন / নাস্তা খরচ)'),
            self::CATEGORY_OTHERS => __('Others (অন্যান্য খরচ)'),
        ];
    }

    public static function getCategoryLabel(?string $category): string
    {
        return static::getCategoryOptions()[$category] ?? ucfirst(str_replace('_', ' ', $category ?? 'others'));
    }

    public static function getCategoryColor(?string $category): string
    {
        return match ($category) {
            self::CATEGORY_SHOP_RENT => 'info',
            self::CATEGORY_ELECTRICITY_BILL => 'warning',
            self::CATEGORY_WAREHOUSE_RENT => 'primary',
            self::CATEGORY_SNACK_EXPENSES => 'success',
            default => 'gray',
        };
    }

    public static function getPaymentMethodOptions(): array
    {
        return [
            'cash' => __('Cash (নগদ)'),
            'bank' => __('Bank Transfer (ব্যাংক)'),
            'bkash' => 'bKash (বিকাশ)',
            'nagad' => 'Nagad (নগদ অ্যাপ)',
            'rocket' => 'Rocket (রকেট)',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
