<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'name',
        'phone',
        'address',
        'product_list',
        'paid',
        'due',
        'total',
    ];

    protected $casts = [
        'paid' => 'decimal:2',
        'due' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
