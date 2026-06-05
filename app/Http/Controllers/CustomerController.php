<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $customers = Customer::query()
            ->with('addresses')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $q],
        ]);
    }

    public function create()
    {
        return Inertia::render('customers/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.address' => ['required', 'string', 'max:255'],
        ]);

        $customer = DB::transaction(function () use ($data) {
            $customer = Customer::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
            ]);

            foreach ($data['addresses'] as $address) {
                $customer->addresses()->create($address);
            }

            return $customer;
        });

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'addresses',
            'orderNotes' => fn ($query) => $query->latest('id')->with('invoice'),
            'invoices' => fn ($query) => $query->latest('id'),
        ]);

        return Inertia::render('customers/Show', [
            'customer' => $customer,
            'totals' => [
                'spend' => (float) $customer->invoices()->sum('total'),
                'paid' => (float) $customer->orderNotes()->sum('paid'),
                'due' => (float) $customer->orderNotes()->sum('due'),
            ],
        ]);
    }

    public function edit(Customer $customer)
    {
        $customer->load('addresses');

        return Inertia::render('customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.address' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($customer, $data) {
            $customer->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
            ]);

            $customer->addresses()->delete();

            foreach ($data['addresses'] as $address) {
                $customer->addresses()->create($address);
            }
        });

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
