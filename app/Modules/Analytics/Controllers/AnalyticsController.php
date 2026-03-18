<?php

namespace App\Modules\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $dateFrom = $request->query('date_from', now()->startOfMonth());
        $dateTo = $request->query('date_to', now()->endOfMonth());

        // Sales stats - use whereDate for proper date comparison
        $salesStats = Order::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(
                DB::raw('COALESCE(SUM(grand_total), 0) as total_sales'),
                DB::raw('COALESCE(COUNT(*), 0) as total_orders'),
                DB::raw('COALESCE(AVG(grand_total), 0) as average_order')
            )
            ->first();

        // Expense stats
        $expenseStats = Expense::whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->select(
                DB::raw('COALESCE(SUM(amount), 0) as total_expenses'),
                DB::raw('COALESCE(COUNT(*), 0) as total_expense_transactions')
            )
            ->first();

        // Customer stats
        $newCustomers = Customer::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();
        $totalCustomers = Customer::count();

        // Product stats
        $totalProducts = Product::where('is_active', true)->count();

        // Sales trend
        $salesTrend = Order::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                DB::raw('COALESCE(SUM(grand_total), 0) as total'),
                DB::raw('COALESCE(COUNT(*), 0) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'total' => (int) $item->total,
                    'count' => (int) $item->count,
                ];
            });

        // Top products
        $topProducts = Order::whereBetween('orders.created_at', [
            Carbon::parse($dateFrom)->startOfDay(),
            Carbon::parse($dateTo)->endOfDay(),
        ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as quantity_sold'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        // Payment breakdown
        $paymentBreakdown = Payment::whereDate('payments.created_at', '>=', $dateFrom)
            ->whereDate('payments.created_at', '<=', $dateTo)
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'payment_methods.name',
                DB::raw('COALESCE(SUM(payments.amount), 0) as total'),
                DB::raw('COALESCE(COUNT(payments.id), 0) as count')
            )
            ->groupBy('payment_methods.name')
            ->orderBy('total', 'desc')
            ->get();

        // Recent orders
        $recentOrders = Order::with(['customer', 'items'])
            ->whereDate('orders.created_at', '>=', $dateFrom)
            ->whereDate('orders.created_at', '<=', $dateTo)
            ->orderBy('orders.created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'sales' => [
                        'total' => (int) $salesStats->total_sales,
                        'orders' => (int) $salesStats->total_orders,
                        'average' => (int) round($salesStats->average_order),
                    ],
                    'expenses' => [
                        'total' => (int) $expenseStats->total_expenses,
                        'count' => (int) $expenseStats->total_expense_transactions,
                    ],
                    'profit' => [
                        'total' => (int) $salesStats->total_sales - (int) $expenseStats->total_expenses,
                        'margin' => $salesStats->total_sales > 0
                            ? round((((int) $salesStats->total_sales - (int) $expenseStats->total_expenses) / (int) $salesStats->total_sales) * 100, 1)
                            : 0,
                    ],
                    'customers' => [
                        'new' => (int) $newCustomers,
                        'total' => (int) $totalCustomers,
                    ],
                    'products' => [
                        'total' => (int) $totalProducts,
                    ],
                ],
                'sales_trend' => $salesTrend,
                'top_products' => $topProducts,
                'payment_breakdown' => $paymentBreakdown,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $dateFrom = $request->query('date_from', now()->startOfMonth());
        $dateTo = $request->query('date_to', now()->endOfMonth());
        $limit = $request->query('limit', 10);

        $products = Order::whereBetween('orders.created_at', [
            Carbon::parse($dateFrom)->startOfDay(),
            Carbon::parse($dateTo)->endOfDay(),
        ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as quantity_sold'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function paymentBreakdown(Request $request): JsonResponse
    {
        $dateFrom = $request->query('date_from', now()->startOfMonth());
        $dateTo = $request->query('date_to', now()->endOfMonth());

        $breakdown = Payment::whereBetween('payments.created_at', [
            Carbon::parse($dateFrom)->startOfDay(),
            Carbon::parse($dateTo)->endOfDay(),
        ])
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'payment_methods.name',
                DB::raw('COALESCE(SUM(payments.amount), 0) as total'),
                DB::raw('COALESCE(COUNT(payments.id), 0) as count')
            )
            ->groupBy('payment_methods.name')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }
}
