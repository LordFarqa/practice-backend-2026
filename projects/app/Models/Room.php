<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "rooms";
    protected $fillable = [
        'number',
        'hotel_id',
        'class_id',
        'floor'
    ];
    protected $hidden = [
        'class_id',
    ];



    public function hotel(){
        return $this->belongsTo(Hotel::class,foreignKey: 'hotel_id');
    }
    public function room_classes(){
        return $this->belongsTo(RoomClasses::class,foreignKey: 'class_id');
    }


}
