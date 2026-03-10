<?php

namespace App\Modules\POS\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\LoyaltyTransaction;
use App\Models\CustomerMembership;
use App\Models\MembershipTier;
use App\Models\MessageQueue;
use Illuminate\Support\Facades\DB;

class POSService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            $discountTotal = $data['discount_amount'] ?? 0;
            $taxTotal = $data['tax_amount'] ?? 0;

            foreach ($data['items'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            $grandTotal = $subtotal - $discountTotal + $taxTotal;

            // Generate unique invoice number with retry logic
            $invoiceNumber = $this->generateUniqueInvoiceNumber();

            $order = Order::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $data['customer_id'] ?? null,
                'staff_id' => $data['staff_id'],
                'shift_id' => $data['shift_id'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'discount_percent' => $data['discount_percent'] ?? 0,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'payment_status' => 'paid',
                'order_status' => 'completed',
            ]);
            
            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $item['price'],
                    'cost' => $product->current_cost ?? 0,
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                ]);
                
                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'sale',
                    'quantity' => -$item['quantity'],
                    'reference_id' => $order->id,
                    'reference_type' => 'order',
                ]);
            }
            
            foreach ($data['payments'] ?? [] as $payment) {
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $payment['payment_method_id'],
                    'amount' => $payment['amount'],
                    'paid_at' => now(),
                ]);
            }
            
            if ($order->customer_id) {
                $this->processLoyaltyPoints($order);
                $this->queueWhatsAppReceipt($order);
            }
            
            return $order;
        });
    }
    
    private function processLoyaltyPoints(Order $order): void
    {
        $customer = $order->customer;
        $pointsEarned = intdiv($order->grand_total, 10000);
        
        if ($pointsEarned > 0) {
            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'points' => $pointsEarned,
                'type' => 'earn',
                'description' => "Earned from order {$order->invoice_number}",
            ]);
            
            $membership = CustomerMembership::firstOrCreate(
                ['customer_id' => $customer->id],
                ['membership_tier_id' => 1, 'current_points' => 0, 'lifetime_spend' => 0]
            );
            
            $membership->increment('current_points', $pointsEarned);
            $membership->increment('lifetime_spend', $order->grand_total);
            $this->checkTierUpgrade($membership);
        }
    }
    
    private function checkTierUpgrade(CustomerMembership $membership): void
    {
        $tier = MembershipTier::where('min_spend', '<=', $membership->lifetime_spend)
            ->orderBy('min_spend', 'desc')->first();
        
        if ($tier && $tier->id !== $membership->membership_tier_id) {
            $membership->update(['membership_tier_id' => $tier->id]);
        }
    }
    
    private function queueWhatsAppReceipt(Order $order): void
    {
        $customer = $order->customer;
        $message = "*TOKO KOPI NUSANTARA*\n\nInvoice: {$order->invoice_number}\n\n";

        foreach ($order->items as $item) {
            $message .= "{$item->product_name} x{$item->quantity}\n";
            $message .= number_format($item->total, 0, ',', '.') . "\n\n";
        }

        $message .= "TOTAL: " . number_format($order->grand_total, 0, ',', '.') . "\n\nThank you for your purchase!";

        MessageQueue::create([
            'customer_id' => $customer->id,
            'phone' => $customer->phone,
            'message' => $message,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);
    }

    /**
     * Generate unique invoice number with retry logic
     */
    private function generateUniqueInvoiceNumber(int $attempt = 1): string
    {
        $datePrefix = 'INV-' . now()->format('Ymd') . '-';
        
        // Get the last invoice number for today
        $lastOrder = Order::where('invoice_number', 'like', $datePrefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastOrder) {
            // Extract the number from last invoice
            $lastNumber = (int) substr($lastOrder->invoice_number, -5);
            $newNumber = $lastNumber + $attempt;
        } else {
            // First order today
            $newNumber = 1;
        }
        
        $invoiceNumber = $datePrefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        
        // Check if this invoice number already exists
        if (Order::where('invoice_number', $invoiceNumber)->exists()) {
            // Retry with next number
            return $this->generateUniqueInvoiceNumber($attempt + 1);
        }
        
        return $invoiceNumber;
    }
}
