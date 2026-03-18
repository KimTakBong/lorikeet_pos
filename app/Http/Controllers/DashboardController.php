<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $today = now()->toDateString();
        $cacheKey = "dashboard_stats_{$today}";

        $stats = Cache::remember($cacheKey, 60, function () use ($today) {
            // Today's sales & orders in ONE query
            $todayStats = Order::whereDate('created_at', $today)
                ->selectRaw('COALESCE(SUM(grand_total), 0) as total_sales, COUNT(*) as total_orders')
                ->first();

            // Counts only - no data loading
            $totalProducts = Product::where('is_active', true)->count();
            $totalCustomers = Customer::count();

            // Recent orders - minimal columns
            $recentOrders = Order::select('id', 'invoice_number', 'customer_id', 'grand_total', 'order_status', 'created_at')
                ->with(['customer:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return [
                'today_sales' => (int) $todayStats->total_sales,
                'today_orders' => (int) $todayStats->total_orders,
                'total_products' => $totalProducts,
                'total_customers' => $totalCustomers,
                'recent_orders' => $recentOrders,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
