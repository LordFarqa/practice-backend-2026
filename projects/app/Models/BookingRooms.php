<?php
// app/Models/BookingRooms.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $room_id
 * @property string $booking_start
 * @property string $booking_end
 * @property int $status_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Room $room
 * @property-read User $user
 * @property-read BookingStatus $status
 * @property-read \Illuminate\Database\Eloquent\Collection|Reviews[] $reviews
 */
class BookingRooms extends Model
{
    use HasFactory;
    
    protected $table = 'booking_rooms';
    
    protected $fillable = [
        'user_id',
        'room_id',
        'booking_start',
        'booking_end',
        'status_id'
    ];
    
    protected $casts = [
        'booking_start' => 'datetime',
        'booking_end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Отношения
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
    
    public function bookingStatus(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class, 'status_id');
    }
    
    public function reviews(): HasMany
    {
        return $this->hasMany(Reviews::class, 'booking_room_id');
    }
    
    public function review(): HasOne
    {
        return $this->hasOne(Reviews::class, 'booking_room_id');
    }
    
    // Аксессор для статуса
    public function getStatusAttribute(): string
    {
        if ($this->status_id) {
            $status = BookingStatus::find($this->status_id);
            return $status?->name ?? 'unknown';
        }
        return 'pending';
    }
        
    // Аксессор для названия отеля
    public function getHotelNameAttribute(): string
    {
        return $this->room?->hotel?->name ?? '';
    }
}