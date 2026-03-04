<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class UserFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name'=>fake()->name(),
            'surname'=>fake()->lastName(),
            'last_name'=>fake()->lastName(),
            'email'=>fake()->unique()->email(),
            'phone_number'=>fake()->phoneNumber()
        ];
        
    }
    public function configure(){

        return $this->afterCreating(function (User $user){
            Client::factory()->create([
                'user_id'=>$user->id,
                'role_id'=>fake()->numberBetween(1,2)
            ]);

        });
    }


}
