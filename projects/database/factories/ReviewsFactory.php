<?php

namespace Database\Factories;

use App\Models\Reviews;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewsFactory extends Factory
{
    protected $model = Reviews::class;

    public function definition(): array
    {
        // Получаем реальные ID отелей и бронирований
        $hotelIds = \App\Models\Hotel::pluck('id')->toArray();
        $bookingIds = \App\Models\BookingRooms::pluck('id')->toArray();
        
        // Если нет данных, возвращаем заглушку
        if (empty($hotelIds) || empty($bookingIds)) {
            return [
                'hotel_id' => 1,
                'booking_room_id' => 1,
                'coment' => $this->faker->paragraph(2),
                'rating' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        return [
            'hotel_id' => $this->faker->randomElement($hotelIds),
            'booking_room_id' => $this->faker->randomElement($bookingIds),
            'coment' => $this->faker->paragraph(2),
            'rating' => rand(1, 5),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}