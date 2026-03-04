<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BookingStatus;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Seeder;

use Database\Factories\BookingStatusesFactory;
use Database\Factories\HotelFactory;

use Database\Seeders\BookingStatusesSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                BookingStatusesSeeder::class,
                RoomClassesSeeder::class,
                UserRoleSeeder::class
            ]);
        Hotel::factory()->count(10)->create();
        User::factory()->count(100)->create();

    }
}
