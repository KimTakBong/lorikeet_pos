<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProductSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sale_date',
        'quantity',
        'revenue',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'quantity' => 'integer',
        'revenue' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
