<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $orders = $this->orderService->getOrders(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|integer|min:0',
        ]);

        $refund = $this->orderService->createRefund($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $refund,
            'message' => 'Refund processed successfully',
        ]);
    }

    public function getRefund(int $id): JsonResponse
    {
        $refund = $this->orderService->getRefund($id);

        return response()->json([
            'success' => true,
            'data' => $refund,
        ]);
    }
}
