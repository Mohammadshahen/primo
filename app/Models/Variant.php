<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Offer;

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

    // protected $appends = [
    //     'has_active_offer',
    //     'offer_id',
    // ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function offer()
    {
        return $this->hasOne(Offer::class, 'variant_id');
    }

    public function activeOffer()
    {
        return $this->hasOne(Offer::class, 'variant_id')
            ->whereDate('from', '<=', now())
            ->whereDate('to', '>=', now());
    }

    public function getHasActiveOfferAttribute(): bool
    {
        return (bool) $this->activeOffer;
    }

    public function getOfferIdAttribute(): ?int
    {
        return $this->activeOffer?->id;
    }

    public function ordarItams()
    {
        return $this->hasMany(OrdarItam::class, 'variant_id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'variant_id');
    }

    public function scopeIs_deliverable($query)
    {
        return (bool) $query->where('is_active', true)->whereHas('product', function ($query) {
            $query->where('is_active', true);
        });
    }
}
