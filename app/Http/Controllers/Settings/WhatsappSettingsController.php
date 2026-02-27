<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $row = BusinessSetting::query()
            ->where('key', 'whatsapp')
            ->first();

        // value can be array/json/string; normalize to array
        $value = $row?->value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($value)) {
            $value = [];
        }

        return Inertia::render('business-settings/Whatsapp', [
            'status' => $request->session()->get('status'),
            'whatsapp' => [
                'delivery_man_phone' => $value['delivery_man_phone'] ?? '',
                'delivery_man_message' => $value['delivery_man_message'] ?? '',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'delivery_man_phone' => ['nullable', 'string', 'max:30'],
            'delivery_man_message' => ['nullable', 'string', 'max:2000'],
        ]);

        // keep nulls as empty string if you prefer
        $payload = [
            'delivery_man_phone' => $data['delivery_man_phone'] ?? '',
            'delivery_man_message' => $data['delivery_man_message'] ?? '',
        ];

        BusinessSetting::query()->updateOrCreate(
            ['key' => 'whatsapp'],
            ['value' => $payload]
        );

        return back()->with('status', 'whatsapp-updated');
    }
}
