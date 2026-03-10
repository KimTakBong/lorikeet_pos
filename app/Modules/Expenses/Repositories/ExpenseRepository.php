<?php

namespace App\Modules\Expenses\Repositories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

class ExpenseRepository
{
    public function __construct(
        private Expense $expense,
        private ExpenseCategory $category
    ) {}

    public function getAll(array $filters = [], int $perPage = 20)
    {
        $query = $this->expense->with(['category']);

        if (!empty($filters['search'])) {
            $query->where('description', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('expense_date', 'desc')->paginate($perPage);
    }

    public function getById(int $id)
    {
        return $this->expense->with(['category'])->findOrFail($id);
    }

    public function create(array $data): Expense
    {
        return $this->expense->create([
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'expense_date' => $data['expense_date'],
        ]);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update([
            'category_id' => $data['category_id'] ?? $expense->category_id,
            'amount' => $data['amount'] ?? $expense->amount,
            'description' => $data['description'] ?? $expense->description,
            'expense_date' => $data['expense_date'] ?? $expense->expense_date,
        ]);

        return $expense->fresh();
    }

    public function delete(Expense $expense): bool
    {
        return $expense->delete();
    }

    public function getCategories()
    {
        return $this->category->orderBy('name')->get();
    }

    public function createCategory(string $name): ExpenseCategory
    {
        return $this->category->create(['name' => $name]);
    }

    public function getTotalsByDateRange(string $dateFrom, string $dateTo)
    {
        return $this->expense
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->selectRaw('SUM(amount) as total, COUNT(*) as count')
            ->first();
    }

    public function getTotalsByCategory(string $dateFrom, string $dateTo)
    {
        return $this->expense
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name')
            ->orderBy('total', 'desc')
            ->get();
    }
}
