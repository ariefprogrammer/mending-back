<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; 

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',    
        'role',     
        'owner_id', 
    ];

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
}