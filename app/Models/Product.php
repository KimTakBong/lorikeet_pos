<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(ProductCost::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplierPriceHistory(): HasMany
    {
        return $this->hasMany(SupplierPriceHistory::class);
    }

    public function dailyProductSales(): HasMany
    {
        return $this->hasMany(DailyProductSale::class);
    }

    public function getCurrentPriceAttribute(): ?int
    {
        return $this->prices()->latest('effective_from')->first()?->price;
    }

    public function getCurrentCostAttribute(): ?int
    {
        return $this->costs()->latest('effective_from')->first()?->cost;
    }
}
