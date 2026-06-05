<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseImport;
use App\Models\PurchaseImportItem;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseImportController extends Controller
{
    public function index(): Response
    {
        $imports = PurchaseImport::latest()
            ->withCount('items')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'original_filename' => $i->original_filename,
                'supplier' => $i->supplier,
                'invoice_number' => $i->invoice_number,
                'invoice_date' => $i->invoice_date?->format('Y-m-d'),
                'total_amount' => $i->total_amount,
                'status' => $i->status,
                'items_count' => $i->items_count,
                'created_at' => $i->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('PurchaseImport/Index', compact('imports'));
    }

    public function create(): Response
    {
        return Inertia::render('PurchaseImport/Create');
    }

    /**
     * Upload PDF and extract with Gemini — returns preview data without saving confirmed stock.
     */
    public function extract(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file = $request->file('pdf');
        $pdfBase64 = base64_encode(file_get_contents($file->getRealPath()));

        $extracted = $gemini->extractInvoice($pdfBase64);

        // Save as pending import
        $import = PurchaseImport::create([
            'original_filename' => $file->getClientOriginalName(),
            'supplier' => $extracted['supplier'] ?? null,
            'invoice_date' => $extracted['invoice_date'] ?? null,
            'invoice_number' => $extracted['invoice_number'] ?? null,
            'total_amount' => $extracted['total_amount'] ?? 0,
            'raw_extracted' => $extracted,
            'status' => 'pending',
        ]);

        // Save items (unlinked to products yet)
        foreach ($extracted['items'] ?? [] as $item) {
            $import->items()->create([
                'product_name' => $item['product_name'] ?? 'Unknown',
                'variant_name' => $item['variant_name'] ?? null,
                'sku' => $item['sku'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_cost' => (float) ($item['unit_cost'] ?? 0),
                'total_cost' => (float) ($item['total_cost'] ?? 0),
            ]);
        }

        return redirect()->route('purchase-imports.show', $import);
    }

    public function show(PurchaseImport $purchaseImport): Response
    {
        $purchaseImport->load('items');

        return Inertia::render('PurchaseImport/Show', [
            'purchaseImport' => [
                'id' => $purchaseImport->id,
                'original_filename' => $purchaseImport->original_filename,
                'supplier' => $purchaseImport->supplier,
                'invoice_number' => $purchaseImport->invoice_number,
                'invoice_date' => $purchaseImport->invoice_date?->format('Y-m-d'),
                'total_amount' => $purchaseImport->total_amount,
                'status' => $purchaseImport->status,
                'items' => $purchaseImport->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                ]),
            ],
        ]);
    }

    /**
     * Confirm import: create/update products, variants, and stock.
     */
    public function confirm(Request $request, PurchaseImport $purchaseImport)
    {
        if ($purchaseImport->status === 'confirmed') {
            return back()->withErrors(['error' => 'This import has already been confirmed.']);
        }

        // Allow editing items before confirm
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.variant_name' => ['nullable', 'string'],
            'items.*.sku' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.total_cost' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $purchaseImport) {
            foreach ($request->items as $itemData) {
                // Negative ID = new row added manually in the UI
                if ($itemData['id'] < 0) {
                    $importItem = $purchaseImport->items()->create([
                        'product_name' => $itemData['product_name'],
                        'variant_name' => $itemData['variant_name'] ?? null,
                        'sku'          => $itemData['sku'] ?? null,
                        'quantity'     => $itemData['quantity'],
                        'unit_cost'    => $itemData['unit_cost'],
                        'total_cost'   => $itemData['total_cost'],
                    ]);
                } else {
                    $importItem = PurchaseImportItem::findOrFail($itemData['id']);
                }

                // Update item in case user edited
                $importItem->update([
                    'product_name' => $itemData['product_name'],
                    'variant_name' => $itemData['variant_name'] ?? null,
                    'sku' => $itemData['sku'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total_cost' => $itemData['total_cost'],
                ]);

                // Find or create product by name (case-insensitive) or SKU
                $product = $this->findOrCreateProduct(
                    $itemData['product_name'],
                    $itemData['sku'] ?? null,
                    $itemData['variant_name'] ?? null
                );

                // Find or create variant
                $variant = $this->findOrCreateVariant(
                    $product,
                    $itemData['variant_name'] ?? null,
                    $itemData['sku'] ?? null,
                    (float) $itemData['unit_cost']
                );

                // Update stock
                $variant->increment('stock', (int) $itemData['quantity']);

                // Link item to product/variant
                $importItem->update([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                ]);
            } // end foreach item

            $purchaseImport->update(['status' => 'confirmed']);
        });

        return redirect()->route('purchase-imports.index')
            ->with('success', 'Purchase import confirmed. Stock updated.');
    }

    private function findOrCreateProduct(string $name, ?string $sku, ?string $variantName): Product
    {
        // If SKU provided and no variant, try to find by SKU directly on product
        if ($sku && !$variantName) {
            $product = Product::where('sku', $sku)->first();
            if ($product) {
                return $product;
            }
        }

        // Find by name (case-insensitive)
        $product = Product::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

        if (!$product) {
            $product = Product::create([
                'name' => $name,
                'sku' => (!$variantName && $sku) ? $sku : null,
            ]);
        }

        return $product;
    }

    private function findOrCreateVariant(Product $product, ?string $variantName, ?string $sku, float $costPrice): ProductVariant
    {
        $query = $product->variants();

        if ($sku) {
            $variant = ProductVariant::where('sku', $sku)->first();
            if ($variant) {
                return $variant;
            }
        }

        if ($variantName) {
            $variant = $query->whereRaw('LOWER(variant_name) = ?', [strtolower($variantName)])->first();
        } else {
            $variant = $query->whereNull('variant_name')->first();
        }

        if (!$variant) {
            $variant = $product->variants()->create([
                'variant_name' => $variantName,
                'sku' => $sku,
                'cost_price' => $costPrice,
                'sale_price' => 0,
                'stock' => 0,
            ]);
        } else {
            // Update cost price to latest
            $variant->update(['cost_price' => $costPrice]);
        }

        return $variant;
    }
}
