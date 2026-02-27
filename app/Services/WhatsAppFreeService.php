<?php

namespace App\Services;

class WhatsAppFreeService
{
    public static function deliveryLink($deliveryPhone, $message)
    {
        $phone = preg_replace('/\D/', '', $deliveryPhone);

        if (str_starts_with($phone, '01')) {
            $phone = '88'.$phone;
        }

        return "https://wa.me/{$phone}?text=".urlencode($message);
    }
}
