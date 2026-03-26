<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'password' => Hash::make('password123'),
            'login' => $this->faker->unique()->userName(), // unique() гарантирует уникальность
            'role_id' => 2 // По умолчанию обычный пользователь
        ];
    }
}