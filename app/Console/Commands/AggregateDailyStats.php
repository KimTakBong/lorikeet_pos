<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\DailySale;
use App\Models\DailyProductSale;
use App\Models\DailyCustomerStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AggregateDailyStats extends Command
{
    protected $signature = 'stats:daily {date?}';
    protected $description = 'Aggregate daily sales, product sales, and customer stats';

    public function handle(): int
    {
        $date = $this->argument('date') ?? now()->subDay()->toDateString();

        $this->info("Aggregating stats for {$date}...");

        // Daily sales
        $sales = Order::whereDate('created_at', $date)
            ->where('order_status', 'completed')
            ->select(
                DB::raw('COALESCE(SUM(grand_total), 0) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )->first();

        DailySale::updateOrCreate(
            ['date' => $date],
            ['revenue' => $sales->revenue, 'orders_count' => $sales->orders_count]
        );

        // Daily product sales
        $productSales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $date)
            ->where('orders.order_status', 'completed')
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('order_items.product_id')
            ->get();

        foreach ($productSales as $ps) {
            DailyProductSale::updateOrCreate(
                ['product_id' => $ps->product_id, 'sale_date' => $date],
                ['quantity' => $ps->quantity, 'revenue' => $ps->revenue]
            );
        }

        // Daily customer stats
        $customerStats = Order::whereDate('created_at', $date)
            ->whereNotNull('customer_id')
            ->where('order_status', 'completed')
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(grand_total) as revenue')
            )
            ->groupBy('customer_id')
            ->get();

        foreach ($customerStats as $cs) {
            DailyCustomerStat::updateOrCreate(
                ['customer_id' => $cs->customer_id, 'stat_date' => $date],
                ['orders_count' => $cs->orders_count, 'revenue' => $cs->revenue]
            );
        }

        $this->info("Done! Sales: {$sales->revenue}, Products: " . count($productSales) . ", Customers: " . count($customerStats));
        return 0;
    }
}
