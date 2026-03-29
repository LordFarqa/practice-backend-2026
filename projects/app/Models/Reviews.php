<?php
// app/Models/Reviews.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $hotel_id
 * @property int $user_id
 * @property int|null $booking_room_id
 * @property string|null $coment
 * @property int|null $rating
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Reviews extends Model
{
    use HasFactory;
    
    protected $table = 'reviews';
    
    protected $fillable = [
        'hotel_id',
        'user_id',
        'booking_room_id',  // Исправлено: booking_room_id вместо booking_id
        'coment',           // Исправлено: coment вместо comment
        'rating',
    ];
    
    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingRooms::class, 'booking_room_id'); // Исправлено
    }
    
    // Аксессор для комментария (исправлено поле coment)
    public function getCommentAttribute($value): ?string
    {
        // Используем поле 'coment' из БД
        if ($this->coment) {
            return $this->coment;
        }
        
        return null;
    }
    
    // Сеттер для комментария
    public function setCommentAttribute($value)
    {
        $this->attributes['coment'] = $value;
    }
}