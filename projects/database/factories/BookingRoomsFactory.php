<?php

namespace Database\Factories;

use App\Models\BookingRooms;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingRoomsFactory extends Factory
{
    protected $model = BookingRooms::class;

    public function definition()
    {
        // Получаем реальные ID пользователей из базы
        $userIds = \App\Models\User::pluck('id')->toArray();
        $roomIds = \App\Models\Room::pluck('id')->toArray();
        
        // Если нет пользователей или комнат, возвращаем заглушку
        if (empty($userIds) || empty($roomIds)) {
            return [
                'room_id' => 1,
                'user_id' => 1,
                'booking_start' => now(),
                'booking_end' => now()->addDays(2),
                'status_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end = clone $start;
        $end->modify('+' . rand(1, 5) . ' days');
        
        return [
            'room_id' => $this->faker->randomElement($roomIds),
            'user_id' => $this->faker->randomElement($userIds),
            'booking_start' => $start,
            'booking_end' => $end,
            'status_id' => rand(1, 4),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => 4,
                'booking_start' => now()->subDays(rand(1, 30)),
                'booking_end' => now()->subDays(rand(1, 30))->addHours(rand(2, 24)),
            ];
        });
    }
    
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => 1,
                'booking_start' => now()->addDays(rand(1, 30)),
                'booking_end' => now()->addDays(rand(1, 30))->addHours(rand(2, 24)),
            ];
        });
    }
}