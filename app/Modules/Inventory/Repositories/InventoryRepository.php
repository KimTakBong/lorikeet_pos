<?php

namespace App\Modules\Inventory\Repositories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class InventoryRepository
{
    public function __construct(
        private Product $product,
        private StockMovement $stockMovement,
        private Purchase $purchase,
        private PurchaseItem $purchaseItem,
        private Supplier $supplier
    ) {}

    public function getStockLevels(array $filters = [], int $perPage = 20)
    {
        $query = $this->product->with(['category', 'prices', 'costs'])
            ->select('products.*', DB::raw('COALESCE((
                SELECT SUM(sm.quantity) 
                FROM stock_movements sm 
                WHERE sm.product_id = products.id
            ), 0) as current_stock'));

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['low_stock'])) {
            $query->havingRaw('current_stock < ?', [$filters['low_stock']]);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getStockMovements(int $productId, array $filters = [])
    {
        return $this->stockMovement->with('product')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getAllStockMovements(array $filters = [])
    {
        $query = $this->stockMovement->with(['product', 'reference']);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function createStockMovement(array $data): StockMovement
    {
        return $this->stockMovement->create($data);
    }

    public function restockProducts(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $purchase = $this->purchase->create([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'] ?? 'PUR-' . now()->format('Ymd') . '-' . str_pad($this->purchase->count() + 1, 5, '0', STR_PAD_LEFT),
                'total_cost' => 0,
            ]);

            $totalCost = 0;

            foreach ($data['items'] as $item) {
                $purchaseItem = $this->purchaseItem->create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'total' => $item['quantity'] * $item['cost_price'],
                ]);

                $totalCost += $purchaseItem->total;

                // Create stock movement for restock
                $this->createStockMovement([
                    'product_id' => $item['product_id'],
                    'movement_type' => 'restock',
                    'quantity' => $item['quantity'],
                    'reference_id' => $purchase->id,
                    'reference_type' => 'purchase',
                ]);
            }

            $purchase->update(['total_cost' => $totalCost]);

            return $purchase->fresh(['supplier', 'items.product']);
        });
    }

    public function adjustStock(int $productId, int $quantity, string $reason): StockMovement
    {
        return DB::transaction(function () use ($productId, $quantity, $reason) {
            $movement = $this->createStockMovement([
                'product_id' => $productId,
                'movement_type' => 'adjustment',
                'quantity' => $quantity,
                'reference_id' => null,
                'reference_type' => 'adjustment',
            ]);

            return $movement;
        });
    }

    public function getSuppliers()
    {
        return $this->supplier->orderBy('name')->get();
    }

    public function getProductWithStock(int $productId)
    {
        return $this->product->with(['category', 'prices', 'costs'])
            ->select('products.*', DB::raw('COALESCE((
                SELECT SUM(sm.quantity) 
                FROM stock_movements sm 
                WHERE sm.product_id = products.id
            ), 0) as current_stock'))
            ->findOrFail($productId);
    }
}
