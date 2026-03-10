<?php

namespace App\Modules\Expenses\Services;

use App\Modules\Expenses\Repositories\ExpenseRepository;

class ExpenseService
{
    public function __construct(
        private ExpenseRepository $expenseRepo
    ) {}

    public function getExpenses(array $filters = [])
    {
        return $this->expenseRepo->getAll($filters);
    }

    public function getExpenseById(int $id)
    {
        return $this->expenseRepo->getById($id);
    }

    public function createExpense(array $data)
    {
        return $this->expenseRepo->create($data);
    }

    public function updateExpense(int $id, array $data)
    {
        $expense = $this->expenseRepo->getById($id);
        return $this->expenseRepo->update($expense, $data);
    }

    public function deleteExpense(int $id)
    {
        $expense = $this->expenseRepo->getById($id);
        return $this->expenseRepo->delete($expense);
    }

    public function getCategories()
    {
        return $this->expenseRepo->getCategories();
    }

    public function createCategory(string $name)
    {
        return $this->expenseRepo->createCategory($name);
    }

    public function getTotalsByDateRange(string $dateFrom, string $dateTo)
    {
        return $this->expenseRepo->getTotalsByDateRange($dateFrom, $dateTo);
    }

    public function getTotalsByCategory(string $dateFrom, string $dateTo)
    {
        return $this->expenseRepo->getTotalsByCategory($dateFrom, $dateTo);
    }
}
