<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BookingRooms;
use App\Models\BookingStatus;
use App\Models\Hotel;
use App\Models\Reviews;
use Illuminate\Database\Seeder;

use App\Models\User;
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
        Hotel::factory()->count(100)->create();
        User::factory()->count(1000)->create();
        BookingRooms::factory()->count(100)->create();
        Reviews::factory()->count(100)->create();

    }
}
