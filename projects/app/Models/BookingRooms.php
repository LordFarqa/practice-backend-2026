<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRooms extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "booking_rooms";
    protected $fillable = [
        'room_id',
        'booking_start',
        'booking_end',
        'user_id',
        'status_id'
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    public function room(){
        return $this->belongsTo(Room::class,'room_id');

    }
    public function status(){
        return $this->belongsTo(BookingStatus::class,"status_id");
    }
    public function reviews(){
        return $this->hasMany(Reviews::class,"booking_room_id");
    }

    public function scopeActive($query)
{
    return $query->where('status_id', 1);
}

public function scopeCompleted($query)
{
    return $query->where('status_id', 4);
}


}
