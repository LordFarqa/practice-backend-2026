<?php

namespace Database\Factories;

use App\Models\Client;

use Illuminate\Database\Eloquent\Factories\Factory;



class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'password' => fake()->password(),
            'login' => fake()->userName()
        ];
    }


}
