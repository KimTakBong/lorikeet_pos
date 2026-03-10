<?php

namespace App\Modules\Campaigns\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'type' => $request->query('type'),
        ];

        $campaigns = $this->campaignService->getCampaigns(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = $this->campaignService->getCampaignById($id);
        $stats = $this->campaignService->getCampaignStats($id);

        return response()->json([
            'success' => true,
            'data' => [
                'campaign' => $campaign,
                'stats' => $stats,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|string|in:whatsapp,email,sms',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $campaign = $this->campaignService->createCampaign($validated);

        return response()->json([
            'success' => true,
            'data' => $campaign,
            'message' => 'Campaign created successfully',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|string|in:whatsapp,email,sms',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
        ]);

        $campaign = $this->campaignService->updateCampaign($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $campaign,
            'message' => 'Campaign updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->campaignService->deleteCampaign($id);

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ]);
    }

    public function segments(Request $request): JsonResponse
    {
        $segment = $request->query('segment', 'all');
        $customers = $this->campaignService->getCustomersBySegment($segment);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function addRecipients(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $this->campaignService->addRecipient($id, $validated['customer_id']);

        return response()->json([
            'success' => true,
            'message' => 'Recipient added successfully',
        ]);
    }

    public function removeRecipient(int $campaignId, int $customerId): JsonResponse
    {
        $this->campaignService->removeRecipient($campaignId, $customerId);

        return response()->json([
            'success' => true,
            'message' => 'Recipient removed successfully',
        ]);
    }

    public function sendCampaign(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $sentCount = $this->campaignService->sendCampaign($id, $validated['message']);

        return response()->json([
            'success' => true,
            'data' => ['sent_count' => $sentCount],
            'message' => "Campaign queued for sending to {$sentCount} recipients",
        ]);
    }

    public function recipients(int $id): JsonResponse
    {
        $recipients = $this->campaignService->getCampaignRecipients($id);

        return response()->json([
            'success' => true,
            'data' => $recipients,
        ]);
    }
}
