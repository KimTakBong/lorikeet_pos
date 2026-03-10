<?php

namespace App\Modules\Loyalty\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'membership_tier_id',
        'current_points',
        'lifetime_spend',
    ];

    protected $casts = [
        'current_points' => 'integer',
        'lifetime_spend' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }
}
