<?php
// app/Models/RoomClasses.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property float $price_per_day
 * @property int $rooms_count
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|RoomClasses withCount(string|array $relations)
 * @method static \Illuminate\Database\Eloquent\Builder|RoomClasses findOrFail(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder|RoomClasses find(int $id)
 */
class RoomClasses extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $table = "room_classes";
    
    protected $fillable = [
        'name',
        'price_per_day'
    ];
    
    protected $withCount = ['rooms'];
    
    protected $casts = [
        'price_per_day' => 'float',
        'id' => 'int',
        'rooms_count' => 'int'
    ];
    
    /**
     * Связь с номерами
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'class_id');
    }
    
    /**
     * Аксессор для форматирования цены
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_per_day, 2, '.', ' ') . ' ₽';
    }
    
    /**
     * Проверка, можно ли удалить класс
     */
    public function isDeletable(): bool
    {
        return $this->rooms_count === 0;
    }
}