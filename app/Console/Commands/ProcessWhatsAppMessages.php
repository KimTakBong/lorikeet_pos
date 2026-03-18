<?php

namespace App\Console\Commands;

use App\Models\MessageQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppMessages extends Command
{
    protected $signature = 'whatsapp:process';
    protected $description = 'Process pending WhatsApp messages using WA Engine';

    private $waEngineUrl;
    private $waEngineApiKey;

    public function __construct()
    {
        parent::__construct();
        $this->waEngineUrl = config('services.whatsapp_engine.url', 'http://localhost:3000');
        $this->waEngineApiKey = config('services.whatsapp_engine.api_key', 'wa-engine-secret-key-2026');
    }

    public function handle()
    {
        $pendingMessages = MessageQueue::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('created_at')
            ->limit(50) // Process in batches
            ->get();

        if ($pendingMessages->isEmpty()) {
            $this->info('No pending messages to process.');
            return 0;
        }

        $this->info("Processing {$pendingMessages->count()} messages...");

        foreach ($pendingMessages as $message) {
            try {
                $result = $this->sendViaWaEngine($message);

                if ($result['success']) {
                    $message->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    $this->line("✓ Sent to {$message->phone}");
                    Log::info("WhatsApp sent to {$message->phone}");
                } else {
                    throw new \Exception($result['error'] ?? 'Unknown error');
                }

            } catch (\Exception $e) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $this->error("✗ Failed to send to {$message->phone}: {$e->getMessage()}");
                Log::error("WhatsApp failed for {$message->phone}: {$e->getMessage()}");
            }
        }

        $this->info('Message processing completed.');
        return 0;
    }

    private function sendViaWaEngine(MessageQueue $message)
    {
        $payload = [
            'phone' => $message->phone,
        ];

        // If image exists, send as image message
        if ($message->image_path) {
            $payload['type'] = 'image';
            $payload['image_url'] = url($message->image_path);
            $payload['caption'] = $message->message;
        } else {
            $payload['type'] = 'text';
            $payload['message'] = $message->message;
        }

        $response = Http::withHeaders([
            'X-API-KEY' => $this->waEngineApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$this->waEngineUrl}/api/messages/send", $payload);

        return $response->json();
    }
}
