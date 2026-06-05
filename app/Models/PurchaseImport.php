<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseImport extends Model
{
    protected $fillable = [
        'original_filename', 'supplier', 'invoice_date', 'invoice_number',
        'total_amount', 'raw_extracted', 'status',
    ];

    protected $casts = [
        'raw_extracted' => 'array',
        'invoice_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseImportItem::class);
    }
}
