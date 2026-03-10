<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /**
     * Get all settings or settings by group
     */
    public function index(Request $request): JsonResponse
    {
        $query = Setting::query();
        
        if ($request->has('group')) {
            $query->where('group', $request->group);
        }
        
        $settings = $query->get();
        
        // Convert to key-value format
        $settingsData = $settings->pluck('value', 'key')->all();

        return response()->json([
            'success' => true,
            'data' => $settingsData,
        ]);
    }

    /**
     * Update or create settings
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:65535',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::set($key, $value, [
                'group' => $this->getSettingGroup($key),
                'description' => $this->getSettingDescription($key),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Get setting group based on key prefix
     */
    private function getSettingGroup(string $key): string
    {
        if (str_starts_with($key, 'business.')) {
            return 'business';
        }
        if (str_starts_with($key, 'whatsapp.')) {
            return 'whatsapp';
        }
        return 'general';
    }

    /**
     * Get setting description based on key
     */
    private function getSettingDescription(string $key): ?string
    {
        $descriptions = [
            'business.store_name' => 'Nama toko yang ditampilkan di receipt',
            'business.address' => 'Alamat toko',
            'business.phone' => 'Nomor telepon toko',
            'business.tax_number' => 'Nomor NPWP / Tax ID',
            'whatsapp.api_key' => 'API Key untuk WhatsApp Engine',
            'whatsapp.enabled' => 'Enable/Disable WhatsApp notifications',
        ];
        
        return $descriptions[$key] ?? null;
    }
}
