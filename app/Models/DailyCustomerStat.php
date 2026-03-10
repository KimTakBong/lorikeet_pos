<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCustomerStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'stat_date',
        'orders_count',
        'revenue',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'orders_count' => 'integer',
        'revenue' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
