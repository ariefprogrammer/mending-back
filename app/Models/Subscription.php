<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'started_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    public function remainingTransactions(): ?int
    {
        if ($this->plan->isUnlimited()) {
            return null;
        }

        $used = Transaction::whereHas('outlet', function ($query) {
            $query->where('user_id', $this->user_id);
        })->count();

        return max(0, $this->plan->transaction_limit - $used);
    }

    public function hasReachedTransactionLimit(): bool
    {
        if ($this->plan->isUnlimited()) {
            return false;
        }

        return $this->remainingTransactions() <= 0;
    }
}