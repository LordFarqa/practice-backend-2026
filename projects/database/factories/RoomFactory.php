<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_id' => rand(1, 7),
            'floor' => rand(1, 5),
            'number' => $this->faker->unique()->numberBetween(1, 500),
        ];
    }
}