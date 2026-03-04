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
            'name'=>fake()->monthName().' '.fake()->unique()->company(),
            'adress'=>json_encode([
                'Страна'=>fake()->country(),
                'Город'=>fake()->city(),
                'Улица'=>fake()->streetAddress()
            ])
        ];
    }
    public function configure(){

        return $this->afterCreating(function (Hotel $hotel){
            Room::factory()->count(rand(40,100))->create([
                'number'=>$hotel->id + fake()->unique()->numberBetween(1,500),
                'hotel_id'=>$hotel->id,
            ]);

        });
    }
    

}
