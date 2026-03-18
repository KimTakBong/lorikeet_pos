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

    public function sendReceipt(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

        if (!$order->customer || !$order->customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'No customer phone number on this order',
            ], 422);
        }

        // Generate receipt image
        $receiptService = app(\App\Services\ReceiptImageService::class);
        $imagePath = $receiptService->generate($order);

        $storeName = \App\Models\Setting::get('business.store_name', 'Lorikeet POS');
        $message = "*{$storeName}*\n\n";
        $message .= "Invoice: {$order->invoice_number}\n";
        $message .= "Date: " . \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') . "\n\n";
        $message .= "Total: Rp " . number_format($order->grand_total, 0, ',', '.') . "\n\n";
        $message .= "Thank you for your purchase!";

        \App\Models\MessageQueue::create([
            'customer_id' => $order->customer_id,
            'phone' => $order->customer->phone,
            'message' => $message,
            'image_path' => $imagePath,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Receipt queued for delivery',
        ]);
    }
}
