<?php

namespace App\Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'category_id' => $request->query('category_id'),
            'sort_by' => $request->query('sort_by', 'name'),
            'sort_direction' => $request->query('sort_direction', 'asc'),
        ];
        $page = $request->query('page', 1);

        $products = $this->productService->getProducts(array_filter($filters), (int) $page);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'required|string|max:80|unique:products,sku',
            'category_id' => 'nullable|exists:product_categories,id',
            'is_active' => 'boolean',
            'price' => 'required|integer|min:0',
            'cost' => 'nullable|integer|min:0',
        ]);

        $product = $this->productService->createProduct($validated);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product created successfully',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'sku' => 'sometimes|required|string|max:80|unique:products,sku,' . $id,
            'category_id' => 'nullable|exists:product_categories,id',
            'is_active' => 'boolean',
            'price' => 'sometimes|required|integer|min:0',
            'cost' => 'sometimes|nullable|integer|min:0',
        ]);

        $product = $this->productService->updateProduct($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->productService->getCategories();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
