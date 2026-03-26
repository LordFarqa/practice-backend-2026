<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\Room;

class HotelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' ' . $this->faker->word(),
            'address' => json_encode([
                'Страна' => $this->faker->country(),
                'Город' => $this->faker->city(),
                'Улица' => $this->faker->streetAddress()
            ], JSON_UNESCAPED_UNICODE),
            'class' => $this->faker->randomElement([
                '1 star',
                '2 stars',
                '3 stars',
                '4 stars',
                '5 stars'
            ])
        ];
    }
    
    public function configure()
    {
        return $this->afterCreating(function (Hotel $hotel) {
            Room::factory()->count(rand(10, 30))->create([
                'hotel_id' => $hotel->id
            ]);
        });
    }
}