<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'users';
    public $timestamps = true;
    
    protected $fillable = [
        'name',
        'surname',
        'last_name',
        'email',
        'phone_number'
    ];
    
    protected $hidden = [
        'updated_at',
        'created_at'
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'user_id');
    }

    public function booking()
    {
        return $this->hasMany(BookingRooms::class, 'user_id');
    }
}