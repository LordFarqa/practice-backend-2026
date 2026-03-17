<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomClasses extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "room_classes";
    protected $fillable = [
        'name',
        'price_per_day'
    ];
    protected $casts = [
        'price_per_day'=>'float'
    ];
    public function rooms()
    {
        return $this->hasMany(Room::class, 'class_id');
    }   


}
