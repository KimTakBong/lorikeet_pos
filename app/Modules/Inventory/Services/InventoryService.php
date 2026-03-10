<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Repositories\InventoryRepository;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private InventoryRepository $inventoryRepo
    ) {}

    public function getStockLevels(array $filters = [])
    {
        return $this->inventoryRepo->getStockLevels($filters);
    }

    public function getStockMovements(int $productId, array $filters = [])
    {
        return $this->inventoryRepo->getStockMovements($productId, $filters);
    }

    public function getAllStockMovements(array $filters = [])
    {
        return $this->inventoryRepo->getAllStockMovements($filters);
    }

    public function restockProducts(array $data)
    {
        return $this->inventoryRepo->restockProducts($data);
    }

    public function adjustStock(int $productId, int $quantity, string $reason)
    {
        return $this->inventoryRepo->adjustStock($productId, $quantity, $reason);
    }

    public function getSuppliers()
    {
        return $this->inventoryRepo->getSuppliers();
    }

    public function getProductWithStock(int $productId)
    {
        return $this->inventoryRepo->getProductWithStock($productId);
    }
}
