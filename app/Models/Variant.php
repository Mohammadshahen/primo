<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Variant extends Model
{
    protected $table = 'variants';

    protected $fillable = [
        'product_id',
        'price',
        'is_dollar',
        'stock',
        'property',
        'is_active',
    ];

    protected $casts = [
        'is_dollar' => 'boolean',
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

    public function getPriceAttribute($value): float
    {
        $basePrice = (float) ($value ?? 0);

        if (! $this->is_dollar || $this->shouldShowAdminPrice()) {
            return $basePrice;
        }

        $dollarValue = (float) Setting::getValue('dollar_value', 1.0);

        return round($basePrice * $dollarValue, 2);
    }

    protected function shouldShowAdminPrice(): bool
    {
        return Auth::check() && (bool) Auth::user()?->is_admin;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->whereHas('product', function ($query) {
                $query->where('is_active', true);
            });
    }

    public function ordarItams()
    {
        return $this->hasMany(OrdarItam::class, 'variant_id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'variant_id');
    }

    public function is_available()
    {
        return (bool) $this->is_active && $this->product->is_active;
    }
}
