<?php

namespace App\Http\Controllers;

use App\Services\SteadfastService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SteadfastController extends Controller
{
    public function page()
    {
        return Inertia::render('steadfast/BookTrack');
    }

    public function book(Request $request, SteadfastService $steadfast)
    {
        $payload = $request->validate([
            'invoice' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'recipient_address' => ['required', 'string', 'max:500'],
            'cod_amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        return response()->json($steadfast->book($payload));
    }

    public function track(Request $request, SteadfastService $steadfast)
    {
        $data = $request->validate([
            'tracking_code' => ['required', 'string', 'max:100'],
        ]);

        return response()->json($steadfast->track($data['tracking_code']));
    }
}
