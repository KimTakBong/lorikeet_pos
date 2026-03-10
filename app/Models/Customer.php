<?php

namespace App\Models;

use App\Modules\Loyalty\Models\CustomerMembership;
use App\Modules\Loyalty\Models\LoyaltyTransaction;
use App\Modules\Marketing\Models\MessageQueue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'birthday',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function membership()
    {
        return $this->hasMany(CustomerMembership::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function messageQueue()
    {
        return $this->hasMany(MessageQueue::class);
    }
}
