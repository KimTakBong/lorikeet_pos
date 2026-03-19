<?php

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductCost;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Staff;
use App\Models\Shift;
use App\Modules\POS\Services\POSService;
use App\Services\ReceiptImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function __construct(private POSService $posService)
    {
    }

    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_phone' => 'nullable|string|max:30',
            'staff_id' => 'required|exists:staff,id',
            'shift_id' => 'required|exists:shifts,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'discount_amount' => 'nullable|integer|min:0',
            'tax_amount' => 'nullable|integer|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|integer|min:0',
        ]);

        try {
            $order = $this->posService->createOrder($validated);

            // Send WhatsApp receipt if customer phone is provided
            if (($validated['customer_phone'] ?? null) || $order->customer_id) {
                $this->sendWhatsAppReceipt($order, $validated['customer_phone'] ?? null);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'grand_total' => $order->grand_total,
                ],
                'message' => 'Order created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'ORDER_CREATION_FAILED',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function sendWhatsAppReceipt(Order $order, ?string $customerPhone = null): void
    {
        $phone = $customerPhone;
        $customerId = $order->customer_id;
        
        // If no phone provided but customer exists, get from customer
        if (!$phone && $order->customer_id) {
            $customer = $order->customer;
            $phone = $customer?->phone;
        }

        // If phone provided but no customer, create walk-in customer
        if ($phone && !$customerId) {
            $customer = \App\Models\Customer::firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => 'Walk-in Customer',
                    'email' => null,
                ]
            );
            $customerId = $customer->id;
        }

        if (!$phone) {
            return;
        }

        // Generate receipt image
        $receiptImageService = app(ReceiptImageService::class);
        $imagePath = $receiptImageService->generate($order);

        // Get store name from settings
        $storeName = \App\Models\Setting::get('business.store_name', 'TOKO KOPI NUSANTARA');

        // Format caption message
        $message = "*{$storeName}*\n\n";
        $message .= "Invoice: {$order->invoice_number}\n";
        $message .= "Date: " . \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') . "\n\n";
        $message .= "Total: Rp " . number_format($order->grand_total, 0, ',', '.') . "\n\n";
        $message .= "Thank you for your purchase!";

        // Queue WhatsApp message with image
        $messageQueue = \App\Models\MessageQueue::create([
            'customer_id' => $customerId,
            'phone' => $phone,
            'message' => $message,
            'image_path' => $imagePath,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);

        // Queue only - scheduler will process it
        // Don't block POS response with sendNow()
    }

    public function getOrder(Order $order): JsonResponse
    {
        $order->load(['customer', 'staff', 'shift', 'items.product', 'payments.paymentMethod']);
        
        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::with(['category'])
            ->where('is_active', true)
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
            ->addSelect([
                'latest_prices.price as current_price',
                'latest_costs.cost as current_cost',
            ]);
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%')
                  ->orWhere('products.sku', 'like', '%' . $search . '%');
            });
        }
        
        if ($request->has('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }
        
        $products = $query->orderBy('products.name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function getCustomers(Request $request): JsonResponse
    {
        $query = Customer::query();
        
        if ($request->has('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }
        
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        $customers = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function createCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'birthday' => 'nullable|date',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully',
        ]);
    }

    public function getPaymentMethods(): JsonResponse
    {
        $methods = cache()->remember('payment_methods_active', 3600, function () {
            return PaymentMethod::where('is_active', true)->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    public function startShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'opening_cash' => 'required|integer|min:0',
        ]);

        $shift = Shift::create([
            'staff_id' => $validated['staff_id'],
            'start_time' => now(),
            'opening_cash' => $validated['opening_cash'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $shift,
            'message' => 'Shift started successfully',
        ]);
    }

    public function endShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'closing_cash' => 'required|integer|min:0',
        ]);

        $shift = Shift::findOrFail($validated['shift_id']);
        
        // Calculate expected cash from orders
        $expectedCash = Order::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->sum('grand_total');
        
        $shift->update([
            'end_time' => now(),
            'closing_cash' => $validated['closing_cash'],
            'expected_cash' => $expectedCash,
            'cash_difference' => $validated['closing_cash'] - $expectedCash,
        ]);

        return response()->json([
            'success' => true,
            'data' => $shift,
            'message' => 'Shift ended successfully',
        ]);
    }
}
