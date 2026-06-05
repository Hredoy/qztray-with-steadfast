<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseImportItem extends Model
{
    protected $fillable = [
        'purchase_import_id', 'product_id', 'product_variant_id',
        'product_name', 'variant_name', 'sku',
        'quantity', 'unit_cost', 'total_cost',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
