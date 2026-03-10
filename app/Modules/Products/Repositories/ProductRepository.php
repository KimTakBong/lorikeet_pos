<?php

namespace App\Modules\Products\Repositories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductCost;

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
        $query = $this->product->with(['category', 'prices', 'costs']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $direction);
        } else {
            $query->orderBy('name', 'asc');
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
