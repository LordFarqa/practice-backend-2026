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
            'address'=>json_encode([
                'Страна'=>fake()->country(),
                'Город'=>fake()->city(),
                'Улица'=>fake()->streetAddress()],JSON_UNESCAPED_UNICODE),
            'class'=>fake()->randomElement([
                    '1 star',
                    '2 stars',
                    '3 stars',
                    '4 stars',
                    '5 stars'])
        ];
    }
    public function configure(){

        return $this->afterCreating(function (Hotel $hotel){
            Room::factory()->count(rand(40,100))->create([
                'hotel_id'=>$hotel->id
            ]);

        });
    }
    

}
