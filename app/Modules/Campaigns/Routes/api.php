<?php

use App\Modules\Campaigns\Controllers\CampaignController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Campaigns
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::put('/campaigns/{id}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy']);
    
    // Campaign Recipients
    Route::get('/campaigns/{id}/recipients', [CampaignController::class, 'recipients']);
    Route::post('/campaigns/{id}/recipients', [CampaignController::class, 'addRecipients']);
    Route::delete('/campaigns/{campaignId}/recipients/{customerId}', [CampaignController::class, 'removeRecipient']);
    
    // Send Campaign
    Route::post('/campaigns/{id}/send', [CampaignController::class, 'sendCampaign']);
    
    // Customer Segments
    Route::get('/campaign-segments', [CampaignController::class, 'segments']);
});
