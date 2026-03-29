<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingStatus extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "booking_statuses";
    protected $fillable = [
        'name'
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingRooms::class, 'status_id');
    }
}