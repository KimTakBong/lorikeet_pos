<?php

namespace App\Modules\Orders\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function __construct(
        private Order $order,
        private OrderItem $orderItem,
        private Refund $refund,
        private RefundItem $refundItem
    ) {}

    public function getAll(array $filters = [], int $perPage = 20)
    {
        $query = $this->order->query()
            ->select(['id', 'invoice_number', 'customer_id', 'staff_id', 'grand_total', 'payment_status', 'order_status', 'created_at'])
            ->with(['customer:id,name,phone', 'staff:id,name']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('invoice_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('customer', function ($cq) use ($filters) {
                      $cq->where('name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('order_status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getById(int $id)
    {
        return $this->order->with([
            'customer',
            'staff',
            'shift.staff',
            'items.product',
            'payments.paymentMethod',
            'refund'
        ])->findOrFail($id);
    }

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->order->create([
                'invoice_number' => $data['invoice_number'],
                'customer_id' => $data['customer_id'] ?? null,
                'staff_id' => $data['staff_id'],
                'shift_id' => $data['shift_id'],
                'subtotal' => $data['subtotal'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_percent' => $data['discount_percent'] ?? 0,
                'tax_total' => $data['tax_total'] ?? 0,
                'grand_total' => $data['grand_total'],
                'payment_status' => 'paid',
                'order_status' => 'completed',
            ]);

            // Create order items
            foreach ($data['items'] as $item) {
                $this->orderItem->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'cost' => $item['cost'] ?? 0,
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                ]);
            }

            // Create payments
            foreach ($data['payments'] as $payment) {
                $this->order->payments()->create([
                    'payment_method_id' => $payment['payment_method_id'],
                    'amount' => $payment['amount'],
                    'paid_at' => now(),
                ]);
            }

            return $order->fresh([
                'customer',
                'staff',
                'shift',
                'items.product',
                'payments.paymentMethod'
            ]);
        });
    }

    public function createRefund(int $orderId, array $data): Refund
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = $this->getById($orderId);
            
            $refund = $this->refund->create([
                'order_id' => $orderId,
                'staff_id' => $data['staff_id'],
                'reason' => $data['reason'],
            ]);

            // Create refund items & return stock
            foreach ($data['items'] as $item) {
                $orderItem = $this->orderItem->findOrFail($item['order_item_id']);
                
                $this->refundItem->create([
                    'refund_id' => $refund->id,
                    'order_item_id' => $item['order_item_id'],
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                ]);

                // Return stock via stock movement
                \App\Models\StockMovement::create([
                    'product_id' => $orderItem->product_id,
                    'movement_type' => 'refund',
                    'quantity' => abs($item['quantity']), // positive = stock returns
                    'reference_id' => $refund->id,
                    'reference_type' => 'refund',
                ]);
            }

            // Update order status
            $order->update(['order_status' => 'refunded']);

            return $refund->fresh(['order', 'items.orderItem']);
        });
    }

    public function getRefund(int $orderId)
    {
        return $this->refund->with(['order', 'items.orderItem.product', 'staff'])
            ->where('order_id', $orderId)
            ->first();
    }
}
