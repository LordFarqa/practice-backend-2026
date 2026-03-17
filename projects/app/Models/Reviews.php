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
        'coment',  // Оставляем как в базе данных
        'rating'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    // Добавим accessor для удобства (если хотим использовать 'comment' в коде)
    public function getCommentAttribute()
    {
        return $this->coment;
    }

    public function setCommentAttribute($value)
    {
        $this->coment = $value;
    }

    public function hotel(){
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function booking(){
        return $this->belongsTo(BookingRooms::class, 'booking_room_id');
    }
}