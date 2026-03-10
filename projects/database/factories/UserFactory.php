<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Client;

use Illuminate\Database\Eloquent\Factories\Factory;



class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'surname' => fake()->name().fake()->lastName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->email(),
            'phone_number' => fake()->phoneNumber(),
        ];
    }
    public function configure(){

        return $this->afterCreating(function (User $user){
            Client::factory()->create([
                "user_id"=>$user->id,
                "role_id"=>1
            ]);
        });
    }

}
