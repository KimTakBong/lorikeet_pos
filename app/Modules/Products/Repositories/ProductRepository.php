<?php

namespace App\Modules\Products\Repositories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductCost;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function __construct(
        private Product $product,
        private ProductCategory $category,
        private ProductPrice $price,
        private ProductCost $cost
    ) {}

    public function getAll(array $filters = [], int $perPage = 20)
    {
        $query = $this->product->with(['category'])
            ->select('products.*')
            ->leftJoinSub(
                ProductPrice::select('product_id', 'price')
                    ->whereIn('id', function ($q) {
                        $q->select(DB::raw('MAX(id)'))
                          ->from('product_prices')
                          ->groupBy('product_id');
                    }),
                'latest_prices',
                'latest_prices.product_id', '=', 'products.id'
            )
            ->leftJoinSub(
                ProductCost::select('product_id', 'cost')
                    ->whereIn('id', function ($q) {
                        $q->select(DB::raw('MAX(id)'))
                          ->from('product_costs')
                          ->groupBy('product_id');
                    }),
                'latest_costs',
                'latest_costs.product_id', '=', 'products.id'
            )
            ->leftJoinSub(
                \App\Models\StockMovement::select('product_id', DB::raw('COALESCE(SUM(quantity), 0) as stock'))
                    ->groupBy('product_id'),
                'stock_agg',
                'stock_agg.product_id', '=', 'products.id'
            )
            ->addSelect([
                'latest_prices.price as current_price',
                'latest_costs.cost as current_cost',
                DB::raw('COALESCE(stock_agg.stock, 0) as stock'),
            ]);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('products.name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('products.sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['is_active'])) {
            $query->where('products.is_active', $filters['is_active']);
        }

        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $column = in_array($filters['sort_by'], ['current_price', 'current_cost']) 
                ? $filters['sort_by'] 
                : 'products.' . $filters['sort_by'];
            $query->orderBy($column, $direction);
        } else {
            $query->orderBy('products.name', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function getById(int $id)
    {
        return $this->product->with(['category', 'prices', 'costs'])->findOrFail($id);
    }

    public function create(array $data): Product
    {
        return $this->product->create([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'category_id' => $data['category_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update([
            'name' => $data['name'] ?? $product->name,
            'sku' => $data['sku'] ?? $product->sku,
            'category_id' => $data['category_id'] ?? $product->category_id,
            'is_active' => $data['is_active'] ?? $product->is_active,
        ]);

        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function setPrice(Product $product, int $price): ProductPrice
    {
        return $this->price->create([
            'product_id' => $product->id,
            'price' => $price,
            'effective_from' => now(),
        ]);
    }

    public function setCost(Product $product, int $cost): ProductCost
    {
        return $this->cost->create([
            'product_id' => $product->id,
            'cost' => $cost,
            'effective_from' => now(),
        ]);
    }

    public function getCurrentPrice(Product $product): ?int
    {
        return $product->prices()->latest('effective_from')->first()?->price;
    }

    public function getCurrentCost(Product $product): ?int
    {
        return $product->costs()->latest('effective_from')->first()?->cost;
    }

    public function getCategories()
    {
        return $this->category->orderBy('name')->get();
    }

    public function createCategory(string $name): ProductCategory
    {
        return $this->category->create(['name' => $name]);
    }
}
