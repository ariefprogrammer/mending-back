<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'transaction_limit',
        'trial_days',
        'allowed_menus',
        'price',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'transaction_limit' => 'integer',
        'trial_days' => 'integer',
        'allowed_menus' => 'array',
        'price' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isUnlimited(): bool
    {
        return is_null($this->transaction_limit);
    }

    public function hasUnlimitedMenus(): bool
    {
        return is_null($this->allowed_menus);
    }

    public function canAccessMenu(string $menuKey): bool
    {
        if ($this->hasUnlimitedMenus()) {
            return true;
        }

        return in_array($menuKey, $this->allowed_menus ?? [], true);
    }
}