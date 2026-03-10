<?php

namespace App\Http\Controllers;

use App\Models\MessageQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function handleIncoming(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'timestamp' => 'nullable|integer',
            'pushName' => 'nullable|string',
            'isGroup' => 'nullable|boolean',
        ]);

        Log::info('WhatsApp webhook received', $data);

        // TODO: Implement auto-reply logic here
        // For now, just log the incoming message

        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
        ]);
    }
}
