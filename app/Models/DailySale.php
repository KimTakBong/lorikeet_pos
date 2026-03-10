<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'revenue',
        'orders_count',
    ];

    protected $casts = [
        'date' => 'date',
        'revenue' => 'integer',
        'orders_count' => 'integer',
    ];
}
