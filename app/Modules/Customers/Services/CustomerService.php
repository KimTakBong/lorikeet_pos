<?php

namespace App\Modules\Customers\Services;

use App\Modules\Customers\Repositories\CustomerRepository;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepo
    ) {}

    public function getCustomers(array $filters = [])
    {
        return $this->customerRepo->getAll($filters);
    }

    public function getCustomerById(int $id)
    {
        return $this->customerRepo->getById($id);
    }

    public function getCustomerByPhone(string $phone)
    {
        return $this->customerRepo->getByPhone($phone);
    }

    public function createCustomer(array $data)
    {
        return $this->customerRepo->create($data);
    }

    public function updateCustomer(int $id, array $data)
    {
        $customer = $this->customerRepo->getById($id);
        return $this->customerRepo->update($customer, $data);
    }

    public function deleteCustomer(int $id)
    {
        $customer = $this->customerRepo->getById($id);
        return $this->customerRepo->delete($customer);
    }

    public function getTags()
    {
        return $this->customerRepo->getTags();
    }

    public function createTag(string $name)
    {
        return $this->customerRepo->createTag($name);
    }

    public function getCustomerStats(int $customerId)
    {
        return $this->customerRepo->getCustomerStats($customerId);
    }
}
