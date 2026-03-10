<?php

namespace App\Modules\Campaigns\Services;

use App\Modules\Campaigns\Repositories\CampaignRepository;
use App\Models\MessageQueue;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        private CampaignRepository $campaignRepo
    ) {}

    public function getCampaigns(array $filters = [])
    {
        return $this->campaignRepo->getAll($filters);
    }

    public function getCampaignById(int $id)
    {
        return $this->campaignRepo->getById($id);
    }

    public function createCampaign(array $data)
    {
        return $this->campaignRepo->create($data);
    }

    public function updateCampaign(int $id, array $data)
    {
        $campaign = $this->campaignRepo->getById($id);
        return $this->campaignRepo->update($campaign, $data);
    }

    public function deleteCampaign(int $id)
    {
        $campaign = $this->campaignRepo->getById($id);
        return $this->campaignRepo->delete($campaign);
    }

    public function getCustomersBySegment(string $segment)
    {
        return $this->campaignRepo->getCustomersBySegment($segment);
    }

    public function getCampaignStats(int $campaignId)
    {
        return $this->campaignRepo->getCampaignStats($campaignId);
    }

    public function addRecipient(int $campaignId, int $customerId)
    {
        // Check if already exists
        $exists = DB::table('campaign_results')
            ->where('campaign_id', $campaignId)
            ->where('customer_id', $customerId)
            ->exists();
        
        if (!$exists) {
            return $this->campaignRepo->trackResult($campaignId, $customerId);
        }
        return false;
    }

    public function removeRecipient(int $campaignId, int $customerId)
    {
        return $this->campaignRepo->removeRecipient($campaignId, $customerId);
    }

    public function sendCampaign(int $campaignId, string $message)
    {
        return DB::transaction(function () use ($campaignId, $message) {
            $campaign = $this->campaignRepo->getById($campaignId);
            
            // Get all recipients
            $recipients = DB::table('campaign_results')
                ->join('customers', 'campaign_results.customer_id', '=', 'customers.id')
                ->where('campaign_results.campaign_id', $campaignId)
                ->select('customers.id as customer_id', 'customers.phone', 'customers.name')
                ->get();
            
            $queuedCount = 0;
            
            foreach ($recipients as $recipient) {
                // Personalize message
                $personalizedMessage = str_replace(
                    ['{name}', '{campaign}'],
                    [$recipient->name, $campaign->name],
                    $message
                );
                
                // Queue message based on campaign type
                if ($campaign->type === 'whatsapp') {
                    $messageQueue = MessageQueue::create([
                        'customer_id' => $recipient->customer_id,
                        'phone' => $recipient->phone,
                        'message' => $personalizedMessage,
                        'status' => 'pending',
                        'scheduled_at' => now(),
                    ]);
                    
                    // Dispatch job to send immediately via queue
                    SendWhatsAppMessage::dispatch($messageQueue);
                    
                    $queuedCount++;
                }
                // TODO: Add email and SMS support
            }
            
            return $queuedCount;
        });
    }

    public function getCampaignRecipients(int $campaignId)
    {
        return $this->campaignRepo->getCampaignRecipients($campaignId);
    }
}
