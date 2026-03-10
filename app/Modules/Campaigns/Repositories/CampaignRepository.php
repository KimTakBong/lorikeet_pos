<?php

namespace App\Modules\Campaigns\Repositories;

use App\Models\Campaign;
use App\Models\CampaignResult;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CampaignRepository
{
    public function __construct(
        private Campaign $campaign,
        private CampaignResult $campaignResult,
        private Customer $customer
    ) {}

    public function getAll(array $filters = [], int $perPage = 20)
    {
        $query = $this->campaign->withCount(['results as recipients_count']);

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getById(int $id)
    {
        return $this->campaign->with(['results.customer', 'results.order'])->findOrFail($id);
    }

    public function create(array $data): Campaign
    {
        return $this->campaign->create([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'whatsapp',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update([
            'name' => $data['name'] ?? $campaign->name,
            'type' => $data['type'] ?? $campaign->type,
            'start_date' => $data['start_date'] ?? $campaign->start_date,
            'end_date' => $data['end_date'] ?? $campaign->end_date,
        ]);

        return $campaign->fresh();
    }

    public function delete(Campaign $campaign): bool
    {
        return $campaign->delete();
    }

    public function getCustomersBySegment(string $segment)
    {
        $query = $this->customer->query();

        switch ($segment) {
            case 'all':
                break;
            case 'vip':
                $query->whereHas('membership', function ($q) {
                    $q->whereHas('tier', function ($t) {
                        $t->where('name', 'VIP');
                    });
                });
                break;
            case 'inactive':
                $query->whereDoesntHave('orders', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(30));
                });
                break;
        }

        return $query->get();
    }

    public function trackResult(int $campaignId, int $customerId, ?int $orderId = null, int $revenue = 0)
    {
        return $this->campaignResult->create([
            'campaign_id' => $campaignId,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'revenue' => $revenue,
        ]);
    }

    public function getCampaignStats(int $campaignId)
    {
        return DB::table('campaign_results')
            ->where('campaign_id', $campaignId)
            ->select(
                DB::raw('COUNT(*) as total_recipients'),
                DB::raw('COUNT(DISTINCT order_id) as total_orders'),
                DB::raw('SUM(revenue) as total_revenue')
            )
            ->first();
    }

    public function removeRecipient(int $campaignId, int $customerId)
    {
        return $this->campaignResult
            ->where('campaign_id', $campaignId)
            ->where('customer_id', $customerId)
            ->delete();
    }

    public function getCampaignRecipients(int $campaignId)
    {
        return DB::table('campaign_results')
            ->join('customers', 'campaign_results.customer_id', '=', 'customers.id')
            ->where('campaign_results.campaign_id', $campaignId)
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.email',
                'campaign_results.created_at as added_at',
                'campaign_results.order_id',
                'campaign_results.revenue'
            )
            ->orderBy('customers.name')
            ->get();
    }
}
