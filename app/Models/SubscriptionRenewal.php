<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRenewal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'subscription_id',
        'duration_days',
        'extended_from',
        'extended_until',
        'note',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'extended_from' => 'datetime',
        'extended_until' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}