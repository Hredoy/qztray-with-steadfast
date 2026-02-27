<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SabitAhmad\SteadFast\Exceptions\SteadfastException;
use SabitAhmad\SteadFast\SteadFast;

class FraudCheckController extends Controller
{
    public function check(Request $request)
    {
        $validated = $request->validate([
            // accept 11 digits OR +88XXXXXXXXXXX (optional)
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        // must be exactly 11 digits after normalize (BD local format)
        if (! preg_match('/^\d{11}$/', $phone)) {
            return response()->json([
                'ok' => false,
                'approved' => false,
                'note' => null,
                'reason' => 'Invalid phone number',
                'customer_stats' => null,
                'priority_processing' => false,
                'send_confirmation' => false,
            ], 422);
        }

        $result = $this->validateCustomerBeforeOrder($phone);

        return response()->json([
            'ok' => true,
            ...$result,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Convert +88XXXXXXXXXXX -> 0XXXXXXXXXX (11 digits)
        if (str_starts_with($phone, '+88')) {
            $phone = substr($phone, 3);
        }
        if (str_starts_with($phone, '88') && strlen($phone) === 13) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    public function validateCustomerBeforeOrder(string $phoneNumber)
    {
        $steadFast = new Steadfast;

        try {
            $fraudCheck = $steadFast->checkFraud($phoneNumber);

            if ($fraudCheck->total === 0) {
                return [
                    'approved' => true,
                    'note' => 'New customer: advance payment required',
                    'reason' => null,
                    'customer_stats' => [
                        'success' => 0,
                        'cancel' => 0,
                        'total' => 0,
                        'success_rate' => 0,
                        'cancel_rate' => 0,
                    ],
                    'priority_processing' => false,
                    'send_confirmation' => true,
                ];
            }

            if ($fraudCheck->getSuccessRate() >= 80) {
                return [
                    'approved' => true,
                    'note' => 'Trusted customer',
                    'reason' => null,
                    'customer_stats' => [
                        'success' => $fraudCheck->success,
                        'cancel' => $fraudCheck->cancel,
                        'total' => $fraudCheck->total,
                        'success_rate' => $fraudCheck->getSuccessRate(),
                        'cancel_rate' => $fraudCheck->getCancelRate(),
                    ],
                    'priority_processing' => true,
                    'send_confirmation' => false,
                ];
            }

            if ($fraudCheck->isRisky(threshold: 40)) {
                return [
                    'approved' => false,
                    'note' => null,
                    'reason' => "High cancellation rate: {$fraudCheck->getCancelRate()}%",
                    'customer_stats' => [
                        'success' => $fraudCheck->success,
                        'cancel' => $fraudCheck->cancel,
                        'total' => $fraudCheck->total,
                        'success_rate' => $fraudCheck->getSuccessRate(),
                        'cancel_rate' => $fraudCheck->getCancelRate(),
                    ],
                    'priority_processing' => false,
                    'send_confirmation' => false,
                ];
            }

            return [
                'approved' => true,
                'note' => 'Medium risk — monitor closely',
                'reason' => null,
                'customer_stats' => [
                    'success' => $fraudCheck->success,
                    'cancel' => $fraudCheck->cancel,
                    'total' => $fraudCheck->total,
                    'success_rate' => $fraudCheck->getSuccessRate(),
                    'cancel_rate' => $fraudCheck->getCancelRate(),
                ],
                'priority_processing' => false,
                'send_confirmation' => true,
            ];
        } catch (SteadfastException $e) {
            Log::warning('Fraud check failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'approved' => true,
                'note' => 'Fraud check unavailable',
                'reason' => null,
                'customer_stats' => null,
                'priority_processing' => false,
                'send_confirmation' => false,
            ];
        }
    }
}
