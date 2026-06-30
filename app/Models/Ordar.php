<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ordar extends Model
{
    protected $table = 'ordars';

    protected $fillable = [
        'user_id',
        'address_id',
        'status',
        'is_delivere',
        'amount',
        'delivere_amount',
        'total_amount',
    ];

    protected $casts = [
        'is_delivere' => 'boolean',
        'amount' => 'float',
        'delivere_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->hasMany(OrdarItam::class, 'ordar_id');
    }
}
