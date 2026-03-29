<?php
// app/Models/Hotel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    protected $table = "hotels";
    protected $fillable = ['name', 'address', 'class'];
    
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'hotel_id');
    }
    
    public function reviews(): HasMany
    {
        return $this->hasMany(Reviews::class, 'hotel_id');
    }
}