<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $table = "reviews";
    protected $fillable = [
        'hotel_id',
        'booking_room_id',
        'coment'
    ];

    public function hotel(){
        return $this->belongsTo(Hotel::class,'hotel_id');
    }
    public function booking(){
        return $this->belongsTo(Reviews::class,'booking_room_id');
    }


}
