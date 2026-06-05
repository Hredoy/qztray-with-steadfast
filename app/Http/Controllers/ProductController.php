<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->string('q'));

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        // Search variants by SKU (exact first), then product name or variant name
        $variants = ProductVariant::with('product')
            ->where(function ($query) use ($q) {
                $query->where('sku', $q)                          // exact SKU / barcode match
                      ->orWhere('sku', 'like', "%{$q}%")         // partial SKU
                      ->orWhereHas('product', fn ($p) =>
                            $p->where('name', 'like', "%{$q}%")  // product name
                      )
                      ->orWhere('variant_name', 'like', "%{$q}%"); // variant name
            })
            ->orderByRaw("CASE WHEN sku = ? THEN 0 ELSE 1 END", [$q]) // exact SKU first
            ->limit(12)
            ->get()
            ->map(fn ($v) => [
                'variant_id'   => $v->id,
                'product_id'   => $v->product_id,
                'product_name' => $v->product->name,
                'variant_name' => $v->variant_name,
                'sku'          => $v->sku,
                'sale_price'   => $v->sale_price,
                'cost_price'   => $v->cost_price,
                'stock'        => $v->stock,
                'label'        => $v->variant_name
                    ? "{$v->product->name} — {$v->variant_name}"
                    : $v->product->name,
            ]);

        return response()->json($variants);
    }

    public function index(): Response
    {
        $products = Product::with('variants')
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'total_stock' => $p->variants->sum('stock'),
                'variants' => $p->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'variant_name' => $v->variant_name,
                    'sku' => $v->sku,
                    'cost_price' => $v->cost_price,
                    'sale_price' => $v->sale_price,
                    'stock' => $v->stock,
                ]),
            ]);

        return Inertia::render('Products/Index', compact('products'));
    }
}
