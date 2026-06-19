<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'image',
        'description',
        'sku_code',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Categorie::class, 'category_id');
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'product_id');
    }
}
