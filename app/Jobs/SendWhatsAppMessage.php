<?php

namespace App\Jobs;

use App\Models\MessageQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private MessageQueue $messageQueue;

    public function __construct(MessageQueue $messageQueue)
    {
        $this->messageQueue = $messageQueue;
    }

    public function handle(): void
    {
        $waEngineUrl = config('services.whatsapp_engine.url', 'http://localhost:3000');
        $apiKey = config('services.whatsapp_engine.api_key', 'wa-engine-secret-key-2026');

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$waEngineUrl}/api/messages/send", [
                'phone' => $this->messageQueue->phone,
                'message' => $this->messageQueue->message,
            ]);

            $result = $response->json();

            if ($result['success']) {
                $this->messageQueue->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                Log::info("WhatsApp sent to {$this->messageQueue->phone}");
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            $this->messageQueue->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error("WhatsApp failed for {$this->messageQueue->phone}: {$e->getMessage()}");
        }
    }
}
