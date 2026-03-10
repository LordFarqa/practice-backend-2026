<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\Room;
class BookingRoomsFactory extends Factory
{

    public function definition(): array
    {
        return [
            'status_id'=>fake()->numberBetween(1,3),
            'user_id'=>fake()->numberBetween(1,1000),
            'room_id'=>fake()->numberBetween(1,50),
            'booking_start'=>now(),
            'booking_end'=>now()->addDays(rand(1,20))
        ];
    }
    

}
