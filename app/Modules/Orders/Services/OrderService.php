<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Repositories\OrderRepository;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepo
    ) {}

    public function getOrders(array $filters = [])
    {
        return $this->orderRepo->getAll($filters);
    }

    public function getOrderById(int $id)
    {
        return $this->orderRepo->getById($id);
    }

    public function createOrder(array $data)
    {
        return $this->orderRepo->create($data);
    }

    public function createRefund(int $orderId, array $data)
    {
        return $this->orderRepo->createRefund($orderId, $data);
    }

    public function getRefund(int $orderId)
    {
        return $this->orderRepo->getRefund($orderId);
    }
}
