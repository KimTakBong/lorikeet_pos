<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Repositories\AnalyticsRepository;

class AnalyticsService
{
    public function __construct(
        private AnalyticsRepository $analyticsRepo
    ) {}

    public function getDashboardStats(string $dateFrom, string $dateTo)
    {
        return $this->analyticsRepo->getDashboardStats($dateFrom, $dateTo);
    }

    public function getSalesTrend(string $dateFrom, string $dateTo, string $groupBy = 'day')
    {
        return $this->analyticsRepo->getSalesTrend($dateFrom, $dateTo, $groupBy);
    }

    public function getTopProducts(string $dateFrom, string $dateTo, int $limit = 10)
    {
        return $this->analyticsRepo->getTopProducts($dateFrom, $dateTo, $limit);
    }

    public function getPaymentBreakdown(string $dateFrom, string $dateTo)
    {
        return $this->analyticsRepo->getPaymentBreakdown($dateFrom, $dateTo);
    }

    public function getExpenseBreakdown(string $dateFrom, string $dateTo)
    {
        return $this->analyticsRepo->getExpenseBreakdown($dateFrom, $dateTo);
    }

    public function getRecentOrders(int $limit = 10)
    {
        return $this->analyticsRepo->getRecentOrders($limit);
    }
}
