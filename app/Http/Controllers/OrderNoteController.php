<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OrderNote;
use App\Services\IdGenerateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderNoteController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $customer = $this->resolveCustomer($data);

            $customer->orderNotes()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'product_list' => $data['product_list'],
                'paid' => $data['paid'],
                'due' => $data['due'],
                'total' => $data['paid'] + $data['due'],
            ]);

            $customer->addresses()->firstOrCreate(['address' => $data['address']]);
        });

        return redirect()->route('dashboard')->with('success', 'Order note created successfully.');
    }

    public function convert(OrderNote $orderNote, IdGenerateService $idGenerateService)
    {
        if ($orderNote->invoice_id) {
            return redirect()->route('invoices.show', $orderNote->invoice_id);
        }

        $invoice = DB::transaction(function () use ($orderNote, $idGenerateService) {
            $invoice = Invoice::create([
                'customer_id' => $orderNote->customer_id,
                'order_note_id' => $orderNote->id,
                'invoice_id' => $idGenerateService->generateNextSaleInvoiceNo(),
                'wgt' => '0.5',
                'name' => $orderNote->name,
                'phone' => $orderNote->phone,
                'address' => $orderNote->address,
                'delivery_type' => 2,
                'cod' => $orderNote->due,
                'total' => $orderNote->total,
                'instruction' => $orderNote->product_list,
                'notes' => null,
            ]);

            $orderNote->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'product_list' => ['required', 'string', 'max:5000'],
            'paid' => ['required', 'numeric', 'min:0'],
            'due' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        return Customer::firstOrCreate(
            ['phone' => $data['phone']],
            ['name' => $data['name']]
        );
    }
}
