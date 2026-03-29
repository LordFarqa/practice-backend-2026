<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // ИЗМЕНИТЕ ЭТО
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens; // ДОБАВЬТЕ ЭТО

class User extends Authenticatable // ИЗМЕНИТЕ С Model НА Authenticatable
{
    use HasApiTokens, HasFactory; // ДОБАВЬТЕ HasApiTokens
    
    protected $fillable = [
        'name', 'surname', 'last_name', 'email', 'phone_number'
    ];
    
    // Уберите 'password' из fillable, если его нет в таблице users
    // Пароль хранится в таблице clients, не в users
    
    public function client(): HasOne
    {
        return $this->hasOne(Client::class, 'user_id');
    }
    
    public function bookings(): HasMany
    {
        return $this->hasMany(BookingRooms::class, 'user_id');
    }
}