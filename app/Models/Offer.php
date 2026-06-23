<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}