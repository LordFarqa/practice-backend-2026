<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Validation\Rules\Unique;
class RoomFactory extends Factory
{

    public function definition(): array
    {
        return [
            'class_id'=>rand(1,7),
            'floor'=>rand(1,5),
            'number'=>fake()->numberBetween(1,1000)
        ];
    }

    

}
