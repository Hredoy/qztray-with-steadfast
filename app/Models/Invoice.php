<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice',
        'stead_fast_id',
        'wgt',
        'name',
        'phone',
        'address',
        'cod',
        'instruction',
    ];
}
