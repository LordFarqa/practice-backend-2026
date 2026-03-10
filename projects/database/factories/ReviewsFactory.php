<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Client;

use Illuminate\Database\Eloquent\Factories\Factory;



class ReviewsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hotel_id'=>fake()->numberBetween(1,100),
            'booking_room_id'=>fake()->numberBetween(1,100),
            'coment'=>fake()->text(100)	
        ];
    }


}
