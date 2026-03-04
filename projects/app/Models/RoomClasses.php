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

    public function hotel(){
        return $this->hasMany(Room::class,foreignKey: 'class_id');
    }



}
