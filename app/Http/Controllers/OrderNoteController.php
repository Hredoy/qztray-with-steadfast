<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OrderNote;
use App\Services\IdGenerateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderNoteController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $orderNotes = OrderNote::query()
            ->with(['customer', 'invoice'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('product_list', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('order-notes/Index', [
            'orderNotes' => $orderNotes,
            'filters' => ['q' => $q],
        ]);
    }

    public function create()
    {
        return Inertia::render('order-notes/Create', [
            'customers' => $this->customerOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $orderNote = DB::transaction(function () use ($data) {
            $customer = $this->resolveCustomer($data);

            $orderNote = $customer->orderNotes()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'product_list' => $data['product_list'],
                'paid' => $data['paid'],
                'due' => $data['due'],
                'total' => $data['paid'] + $data['due'],
            ]);

            $customer->addresses()->firstOrCreate(['address' => $data['address']]);

            return $orderNote;
        });

        return redirect()->route('order-notes.show', $orderNote)->with('success', 'Order note created successfully.');
    }

    public function show(OrderNote $orderNote)
    {
        $orderNote->load(['customer.addresses', 'invoice']);

        return Inertia::render('order-notes/Show', [
            'orderNote' => $orderNote,
        ]);
    }

    public function edit(OrderNote $orderNote)
    {
        $orderNote->load(['customer.addresses', 'invoice']);

        return Inertia::render('order-notes/Edit', [
            'orderNote' => $orderNote,
            'customers' => $this->customerOptions(),
        ]);
    }

    public function update(Request $request, OrderNote $orderNote)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $orderNote) {
            $customer = $this->resolveCustomer($data);

            $orderNote->update([
                'customer_id' => $customer->id,
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

        return redirect()->route('order-notes.show', $orderNote)->with('success', 'Order note updated successfully.');
    }

    public function destroy(OrderNote $orderNote)
    {
        if ($orderNote->invoice_id) {
            return back()->with('error', 'Converted order notes cannot be deleted.');
        }

        $orderNote->delete();

        return redirect()->route('order-notes.index')->with('success', 'Order note deleted successfully.');
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

    private function customerOptions()
    {
        return Customer::with('addresses')
            ->latest('id')
            ->get(['id', 'name', 'phone']);
    }
}
