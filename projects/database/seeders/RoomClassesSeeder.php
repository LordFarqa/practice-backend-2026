<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BookingStatus;
use App\Models\RoomClasses;
use Illuminate\Database\Seeder;
use Database\Factories\BookingStatusesFactory;

class RoomClassesSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Standart',
                'price_per_day' => 2500
            ],
            [
                'name' => 'Update',
                'price_per_day' => 3500
            ],
            [
                'name'=> 'With a sleeping road',
                'price_per_day' => 5500
            ],
            [
                'name'=> 'Apartments',
                'price_per_day' => 8500
            ],
            [
                'name'=> 'Studio',
                'price_per_day' => 4500
            ],
            [
                'name'=> 'Lux',
                'price_per_day' => 10000
            ],
            [
                'name'=> 'Presidential',
                'price_per_day' => 30000
            ]
        ];
        foreach($statuses as $status){
            RoomClasses::firstOrCreate($status);
        }
    }
}
