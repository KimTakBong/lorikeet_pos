<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageQueue extends Model
{
    use HasFactory;

    protected $table = 'message_queue';

    protected $fillable = [
        'customer_id',
        'phone',
        'message',
        'image_path',
        'status',
        'scheduled_at',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Send message immediately (bypass queue)
     */
    public function sendNow(): bool
    {
        $waEngineUrl = config('services.whatsapp_engine.url', 'http://localhost:3000');
        $apiKey = config('services.whatsapp_engine.api_key', 'wa-engine-secret-key-2026');

        try {
            $payload = [
                'phone' => $this->phone,
            ];

            // If image exists, send as image message
            if ($this->image_path) {
                $payload['type'] = 'image';
                // Convert domain to accessible URL for WA Engine
                $imageUrl = $this->image_path;
                if (app()->environment('local')) {
                    // Replace .test domain with localhost (Herd serves on port 80)
                    $imageUrl = preg_replace(
                        '/https?:\/\/[a-zA-Z0-9.-]+\.test/',
                        'http://127.0.0.1',
                        $this->image_path
                    );
                }
                $imageUrl = filter_var($imageUrl, FILTER_VALIDATE_URL) 
                    ? $imageUrl 
                    : url($imageUrl);
                $payload['image_url'] = $imageUrl;
                $payload['caption'] = $this->message;
                $payload['message'] = $this->message;
            } else {
                $payload['type'] = 'text';
                $payload['message'] = $this->message;
            }

            Log::info("Sending WhatsApp to {$this->phone}");

            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->post("{$waEngineUrl}/api/messages/send", $payload);

            $result = $response->json();

            Log::info("WA Engine response", ['result' => $result]);

            if ($result['success']) {
                $this->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                Log::info("WhatsApp sent to {$this->phone}");
                return true;
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            $this->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error("WhatsApp failed for {$this->phone}: {$e->getMessage()}");
            return false;
        }
    }
}
