<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Offer extends Model
{
    protected $table = 'offers';

    protected $fillable = [
        'variant_id',
        'from',
        'to',
        'discount_percentage',
        'discount_value',
    ];

    protected $casts = [
        'from' => 'date',
        'to' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->whereDate('from', '<=', now())
            ->whereDate('to', '>=', now());
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    public function getDiscountValueAttribute($value): float
    {
        $discountValue = (float) ($value ?? 0);

        if (! $this->variant?->is_dollar || $this->shouldShowAdminPrice()) {
            return round($discountValue, 2);
        }

        $dollarValue = (float) Setting::getValue('dollar_value', 1.0);

        return round($discountValue * $dollarValue, 2);
    }

    protected function shouldShowAdminPrice(): bool
    {
        return Auth::check() && (bool) Auth::user()?->is_admin;
    }
}