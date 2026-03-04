<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "hotels";
    protected $fillable = [
        'name',
        'adress'
    ];

    public function rooms(){
        return $this->hasMany(Room::class,'hotel_id');
    }
    public function reviews(){
        return $this->hasMany(Reviews::class,'hotel_id');
    }


}
