<?php

namespace Database\Factories;

use App\Models\BookingRooms;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingRoomsFactory extends Factory
{
    protected $model = BookingRooms::class;

    public function definition()
    {
        return [
            'room_id' => rand(1, 10),
            'user_id' => 1006,
            'booking_start' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'booking_end' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status_id' => 1,
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => 4,
                'booking_start' => now()->subDays(rand(1, 30)),
                'booking_end' => now()->subDays(rand(1, 30))->addHours(2),
            ];
        });
    }
}