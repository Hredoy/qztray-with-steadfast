<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.address' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $customer = Customer::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
            ]);

            foreach ($data['addresses'] as $address) {
                $customer->addresses()->create($address);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Customer created successfully.');
    }
}
