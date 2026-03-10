<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer tables
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 30)->index();
            $table->string('email', 150)->nullable();
            $table->date('birthday')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('customer_tag_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Staff tables (must be created before orders)
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('role', 50)->default('staff');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->unsignedBigInteger('opening_cash')->default(0);
            $table->unsignedBigInteger('closing_cash')->nullable();
            $table->unsignedBigInteger('expected_cash')->nullable();
            $table->bigInteger('cash_difference')->nullable();
            $table->timestamps();
        });

        // Product tables
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('sku', 80)->unique();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price');
            $table->dateTime('effective_from');
            $table->timestamps();
        });

        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('cost');
            $table->dateTime('effective_from');
            $table->timestamps();
        });

        // Order tables
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 60)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->constrained('staff');
            $table->foreignId('shift_id')->constrained('shifts');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->integer('discount_percent')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->string('payment_status', 50)->default('pending');
            $table->string('order_status', 50)->default('pending');
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('staff_id');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name', 150);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('cost');
            $table->integer('quantity');
            $table->unsignedBigInteger('total');
            $table->timestamps();
            
            $table->index('order_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained();
            $table->unsignedBigInteger('amount');
            $table->dateTime('paid_at');
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff');
            $table->text('reason');
            $table->timestamps();
        });

        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });

        // Inventory tables
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type', 50); // sale, restock, adjustment, waste
            $table->integer('quantity');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->timestamps();
            
            $table->index('product_id');
        });

        // Supplier tables
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 50);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->string('invoice_number', 100);
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->unsignedBigInteger('cost_price');
            $table->unsignedBigInteger('total');
            $table->timestamps();
        });

        Schema::create('supplier_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price');
            $table->dateTime('recorded_at');
            $table->timestamps();
        });

        // Expense tables
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('expense_categories');
            $table->unsignedBigInteger('amount');
            $table->text('description');
            $table->date('expense_date');
            $table->timestamps();
        });

        // Campaign tables
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        Schema::create('campaign_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('revenue')->default(0);
            $table->timestamps();
        });

        // WhatsApp/Message queue
        Schema::create('message_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 50);
            $table->text('message');
            $table->string('status', 40)->default('pending');
            $table->dateTime('scheduled_at');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });

        // Analytics tables
        Schema::create('daily_sales', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedBigInteger('revenue')->default(0);
            $table->integer('orders_count')->default(0);
            $table->timestamps();
        });

        Schema::create('daily_product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('sale_date');
            $table->integer('quantity')->default(0);
            $table->unsignedBigInteger('revenue')->default(0);
            $table->timestamps();
            
            $table->unique(['product_id', 'sale_date']);
        });

        Schema::create('daily_customer_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->integer('orders_count')->default(0);
            $table->unsignedBigInteger('revenue')->default(0);
            $table->timestamps();
            
            $table->unique(['customer_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        // Drop tables in reverse order (respecting foreign keys)
        Schema::dropIfExists('daily_customer_stats');
        Schema::dropIfExists('daily_product_sales');
        Schema::dropIfExists('daily_sales');
        Schema::dropIfExists('message_queue');
        Schema::dropIfExists('campaign_results');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('supplier_price_history');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('product_costs');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('customer_tag_map');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('customers');
    }
};
