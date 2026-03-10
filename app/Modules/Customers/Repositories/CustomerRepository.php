<?php

namespace App\Modules\Customers\Repositories;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    public function __construct(
        private Customer $customer
    ) {}

    public function getAll(array $filters = [], int $perPage = 20)
    {
        $query = $this->customer->with(['membership']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('phone', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getById(int $id)
    {
        return $this->customer->with(['membership', 'loyaltyTransactions'])->findOrFail($id);
    }

    public function getByPhone(string $phone)
    {
        return $this->customer->where('phone', $phone)->first();
    }

    public function create(array $data): Customer
    {
        return $this->customer->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'birthday' => $data['birthday'] ?? null,
        ]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update([
            'name' => $data['name'] ?? $customer->name,
            'phone' => $data['phone'] ?? $customer->phone,
            'email' => $data['email'] ?? $customer->email,
            'birthday' => $data['birthday'] ?? $customer->birthday,
        ]);

        return $customer->fresh();
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function getCustomerStats(int $customerId)
    {
        return DB::table('orders')
            ->where('customer_id', $customerId)
            ->select(
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(grand_total) as total_spent'),
                DB::raw('MAX(created_at) as last_purchase_at')
            )
            ->first();
    }
}
