<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdarItam extends Model
{
    protected $table = 'ordar_itams';

    protected $fillable = [
        'ordar_id',
        'variant_id',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    public function ordar()
    {
        return $this->belongsTo(Ordar::class, 'ordar_id');
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }
}
