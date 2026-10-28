<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: A category has many products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Safely check if the category has linked products in database.
     */
    public function hasLinkedProducts(): bool
    {
        if (! Schema::hasTable('products')) {
            return false;
        }

        return $this->products()->exists();
    }

    /**
     * Boot model events.
     */
    protected static function booted(): void
    {
        static::creating(function (ProductCategory $category) {
            if (empty($category->slug) && ! empty($category->name)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::deleting(function (ProductCategory $category) {
            if ($category->hasLinkedProducts()) {
                throw new \Exception(__('Cannot delete category because it contains active products.'));
            }
        });
    }
}
