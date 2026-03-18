<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders - most queried columns
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'orders_created_at_idx');
            $table->index('payment_status', 'orders_payment_status_idx');
            $table->index('order_status', 'orders_order_status_idx');
            $table->index(['shift_id', 'payment_status'], 'orders_shift_payment_idx');
        });

        // Order Items
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('product_id', 'order_items_product_id_idx');
        });

        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->index('created_at', 'payments_created_at_idx');
        });

        // Products
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id', 'products_category_idx');
            $table->index(['is_active', 'name'], 'products_active_name_idx');
        });

        // Product Prices / Costs - for accessor lookups
        Schema::table('product_prices', function (Blueprint $table) {
            $table->index(['product_id', 'effective_from'], 'product_prices_latest_idx');
        });

        Schema::table('product_costs', function (Blueprint $table) {
            $table->index(['product_id', 'effective_from'], 'product_costs_latest_idx');
        });

        // Stock Movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'stock_movements_product_date_idx');
        });

        // Expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_date', 'expenses_date_idx');
        });

        // Customers
        Schema::table('customers', function (Blueprint $table) {
            $table->index('name', 'customers_name_idx');
        });

        // Shifts
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['staff_id', 'start_time'], 'shifts_staff_time_idx');
        });

        // Message Queue
        Schema::table('message_queue', function (Blueprint $table) {
            $table->index('status', 'message_queue_status_idx');
            $table->index('scheduled_at', 'message_queue_scheduled_idx');
        });

        // Loyalty Transactions
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->index('customer_id', 'loyalty_transactions_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_created_at_idx');
            $table->dropIndex('orders_payment_status_idx');
            $table->dropIndex('orders_order_status_idx');
            $table->dropIndex('orders_shift_payment_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_product_id_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_created_at_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_idx');
            $table->dropIndex('products_active_name_idx');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropIndex('product_prices_latest_idx');
        });

        Schema::table('product_costs', function (Blueprint $table) {
            $table->dropIndex('product_costs_latest_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_product_date_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_date_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_name_idx');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_staff_time_idx');
        });

        Schema::table('message_queue', function (Blueprint $table) {
            $table->dropIndex('message_queue_status_idx');
            $table->dropIndex('message_queue_scheduled_idx');
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropIndex('loyalty_transactions_customer_idx');
        });
    }
};
