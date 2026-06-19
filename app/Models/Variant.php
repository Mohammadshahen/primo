<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    protected $table = 'variants';

    protected $fillable = [
        'product_id',
        'price',
        'stock',
        'property',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
