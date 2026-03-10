<?php

namespace App\Modules\Expenses\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Expenses\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseService $expenseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'category_id' => $request->query('category_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $expenses = $this->expenseService->getExpenses(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $expenses,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $expense = $this->expenseService->getExpenseById($id);

        return response()->json([
            'success' => true,
            'data' => $expense,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|integer|min:0',
            'description' => 'required|string|max:500',
            'expense_date' => 'required|date',
        ]);

        $expense = $this->expenseService->createExpense($validated);

        return response()->json([
            'success' => true,
            'data' => $expense,
            'message' => 'Expense created successfully',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:expense_categories,id',
            'amount' => 'sometimes|required|integer|min:0',
            'description' => 'sometimes|required|string|max:500',
            'expense_date' => 'sometimes|required|date',
        ]);

        $expense = $this->expenseService->updateExpense($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $expense,
            'message' => 'Expense updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->expenseService->deleteExpense($id);

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully',
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->expenseService->getCategories();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $dateFrom = $request->query('date_from', now()->startOfMonth());
        $dateTo = $request->query('date_to', now()->endOfMonth());

        $totals = $this->expenseService->getTotalsByDateRange($dateFrom, $dateTo);
        $byCategory = $this->expenseService->getTotalsByCategory($dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totals->total ?? 0,
                'count' => $totals->count ?? 0,
                'by_category' => $byCategory,
            ],
        ]);
    }
}
