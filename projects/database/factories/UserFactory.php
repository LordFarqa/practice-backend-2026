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
            'name' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'last_name' => $this->faker->optional()->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->phoneNumber(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            Client::factory()->create([
                "user_id" => $user->id,
                "role_id" => 2
            ]);
        });
    }
    
    // Создание администратора
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Admin',
                'surname' => 'Super',
                'email' => 'admin@example.com',
                'phone_number' => '+79990000000',
            ];
        })->afterCreating(function (User $user) {
            Client::factory()->create([
                "user_id" => $user->id,
                "login" => 'admin',
                "password" => bcrypt('password'),
                "role_id" => 1
            ]);
        });
    }
}