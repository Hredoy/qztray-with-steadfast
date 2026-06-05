<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'description'];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): ProductVariant|null
    {
        return $this->variants()->first();
    }

    public function totalStock(): int
    {
        return $this->variants()->sum('stock');
    }
}
