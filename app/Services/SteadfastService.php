<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use SteadFast\SteadFastCourierLaravelPackage\Facades\SteadfastCourier;

class SteadfastService
{
    public function book(array $payload): array
    {
        // NOTE: endpoint may differ depending on your SteadFast account/API version.
        // Update URI if your docs say another endpoint.
        $orderData = [
            'invoice' => '123456',
            'recipient_name' => 'John Doe',
            'recipient_phone' => '01234567890',
            'recipient_address' => 'Fla# A1,House# 17/1, Road# 3/A, Dhanmondi,Dhaka-1209',
            'cod_amount' => 1000,
            'note' => 'Handle with care'
        ];

        try {
            $base = rtrim(env('STEADFAST_BASE_URL'), '/');

            $res = Http::asForm()
                ->withHeaders([
                    'Api-Key' => env('STEADFAST_API_KEY'),
                    'Secret-Key' => env('STEADFAST_SECRET_KEY'),
                    'Accept' => 'application/json',
                ])
                ->post($base.'/create_order', [
                    'invoice' => '123456',
                    'recipient_name' => 'John Doe',
                    'recipient_phone' => '01234567890',
                    'recipient_address' => 'Dhaka',
                    'cod_amount' => '1000',
                    'note' => 'Handle with care',
                ]);

            dd($res->status(), $res->body());
        } catch (\Throwable $e) {
            dd([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function track(string $trackingCode): array
    {
        $base = rtrim(config('services.steadfast.base_url'), '/');

        $res = Http::acceptJson()
            ->withHeaders([
                'Api-Key' => config('services.steadfast.api_key'),
                'Secret-Key' => config('services.steadfast.secret_key'),
            ])
            ->get($base.'/status_by_consignment_id/'.urlencode($trackingCode));

        return [
            'ok' => $res->successful(),
            'status' => $res->status(),
            'data' => $res->json(),
        ];
    }
}
