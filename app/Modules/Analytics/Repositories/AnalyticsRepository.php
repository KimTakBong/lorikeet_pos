<?php

namespace App\Modules\Analytics\Repositories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsRepository
{
    public function getDashboardStats(string $dateFrom, string $dateTo)
    {
        $cacheKey = "dashboard_stats_{$dateFrom}_{$dateTo}";
        
        return cache()->remember($cacheKey, 60, function () use ($dateFrom, $dateTo) {
            // Sales stats
            $salesStats = Order::whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->select(
                    DB::raw('SUM(grand_total) as total_sales'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('AVG(grand_total) as average_order')
                )
                ->first();

            // Expense stats
            $expenseStats = Expense::whereDate('expense_date', '>=', $dateFrom)
                ->whereDate('expense_date', '<=', $dateTo)
                ->select(
                    DB::raw('SUM(amount) as total_expenses'),
                    DB::raw('COUNT(*) as total_expense_transactions')
                )
                ->first();

            // Customer stats
            $newCustomers = Customer::whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->count();
            $totalCustomers = Customer::count();

            // Product stats
            $totalProducts = Product::where('is_active', true)->count();

            return [
                'sales' => [
                    'total' => $salesStats->total_sales ?? 0,
                    'orders' => $salesStats->total_orders ?? 0,
                    'average' => $salesStats->average_order ?? 0,
                ],
                'expenses' => [
                    'total' => $expenseStats->total_expenses ?? 0,
                    'count' => $expenseStats->total_expense_transactions ?? 0,
                ],
                'profit' => [
                    'total' => ($salesStats->total_sales ?? 0) - ($expenseStats->total_expenses ?? 0),
                    'margin' => $salesStats->total_sales > 0 
                        ? ((($salesStats->total_sales ?? 0) - ($expenseStats->total_expenses ?? 0)) / $salesStats->total_sales * 100) 
                        : 0,
                ],
                'customers' => [
                    'new' => $newCustomers,
                    'total' => $totalCustomers,
                ],
                'products' => [
                    'total' => $totalProducts,
                ],
            ];
        });
    }

    public function getSalesTrend(string $dateFrom, string $dateTo, string $groupBy = 'day')
    {
        $cacheKey = "sales_trend_{$dateFrom}_{$dateTo}_{$groupBy}";
        
        return cache()->remember($cacheKey, 120, function () use ($dateFrom, $dateTo, $groupBy) {
            $format = $groupBy === 'month' ? 'Y-m' : 'Y-m-d';
            $displayFormat = $groupBy === 'month' ? 'M Y' : 'M d';

            return Order::whereDate('orders.created_at', '>=', $dateFrom)
                ->whereDate('orders.created_at', '<=', $dateTo)
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$format}') as date"),
                    DB::raw('SUM(grand_total) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) use ($displayFormat) {
                    return [
                        'date' => Carbon::parse($item->date)->format($displayFormat),
                        'total' => $item->total,
                        'count' => $item->count,
                    ];
                });
        });
    }

    public function getTopProducts(string $dateFrom, string $dateTo, int $limit = 10)
    {
        $cacheKey = "top_products_{$dateFrom}_{$dateTo}_{$limit}";
        
        return cache()->remember($cacheKey, 120, function () use ($dateFrom, $dateTo, $limit) {
            return Order::whereDate('orders.created_at', '>=', $dateFrom)
                ->whereDate('orders.created_at', '<=', $dateTo)
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(order_items.quantity) as quantity_sold'),
                    DB::raw('SUM(order_items.total) as revenue')
                )
                ->groupBy('products.id', 'products.name')
                ->orderBy('quantity_sold', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function getPaymentBreakdown(string $dateFrom, string $dateTo)
    {
        $cacheKey = "payment_breakdown_{$dateFrom}_{$dateTo}";
        
        return cache()->remember($cacheKey, 120, function () use ($dateFrom, $dateTo) {
            return Payment::whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->select(
                    'payment_methods.name',
                    DB::raw('SUM(payments.amount) as total'),
                    DB::raw('COUNT(payments.id) as count')
                )
                ->groupBy('payment_methods.name')
                ->orderBy('total', 'desc')
                ->get();
        });
    }

    public function getExpenseBreakdown(string $dateFrom, string $dateTo)
    {
        $cacheKey = "expense_breakdown_{$dateFrom}_{$dateTo}";
        
        return cache()->remember($cacheKey, 120, function () use ($dateFrom, $dateTo) {
            return Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                ->select(
                    'expense_categories.name as category',
                    DB::raw('SUM(expenses.amount) as total'),
                    DB::raw('COUNT(expenses.id) as count')
                )
                ->groupBy('expense_categories.name')
                ->orderBy('total', 'desc')
                ->get();
        });
    }

    public function getRecentOrders(int $limit = 10)
    {
        return Order::with(['customer:id,name,phone'])
            ->select('id', 'invoice_number', 'customer_id', 'grand_total', 'order_status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
