<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'category_id' => $request->query('category_id'),
            'low_stock' => $request->query('low_stock'),
        ];

        $stockLevels = $this->inventoryService->getStockLevels(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $stockLevels,
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $filters = [
            'product_id' => $request->query('product_id'),
            'movement_type' => $request->query('movement_type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $movements = $this->inventoryService->getAllStockMovements(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    public function productMovements(int $productId): JsonResponse
    {
        $movements = $this->inventoryService->getStockMovements($productId);

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    public function restock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|integer|min:0',
        ]);

        $purchase = $this->inventoryService->restockProducts($validated);

        return response()->json([
            'success' => true,
            'data' => $purchase,
            'message' => 'Stock restocked successfully',
        ]);
    }

    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'reason' => 'required|string|max:500',
        ]);

        $movement = $this->inventoryService->adjustStock(
            $validated['product_id'],
            $validated['quantity'],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'data' => $movement,
            'message' => 'Stock adjusted successfully',
        ]);
    }

    public function suppliers(): JsonResponse
    {
        $suppliers = cache()->remember('suppliers_list', 3600, function () {
            return $this->inventoryService->getSuppliers();
        });

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    public function show(int $productId): JsonResponse
    {
        $product = $this->inventoryService->getProductWithStock($productId);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }
}
