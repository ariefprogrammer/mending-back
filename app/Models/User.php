<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids; 

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',    
        'role',     
        'owner_id', 
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'superadmin'; 
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function outlets()
    {
        return $this->hasMany(Outlet::class, 'user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latestOfMany('started_at');
    }

    public function isOnFreePlan(): bool
    {
        return $this->currentSubscription?->plan?->slug === 'free';
    }

    public function isPremium(): bool
    {
        return $this->currentSubscription?->plan?->slug === 'premium';
    }

    public function isOnTrial(): bool
    {
        return $this->currentSubscription?->isOnTrial() ?? false;
    }

}