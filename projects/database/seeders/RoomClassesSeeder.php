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
                'name' => 'Стандартный',
                'price_per_day' => 2500
            ],
            [
                'name' => 'Улучшенный',
                'price_per_day' => 3500
            ],
            [
                'name'=> 'Со спальной комнатой',
                'price_per_day' => 5500
            ],
            [
                'name'=> 'Апартаменты',
                'price_per_day' => 8500
            ],
            [
                'name'=> 'Студия',
                'price_per_day' => 4500
            ],
            [
                'name'=> 'Люкс',
                'price_per_day' => 10000
            ],
            [
                'name'=> 'Президентский',
                'price_per_day' => 30000
            ]
        ];
        foreach($statuses as $status){
            RoomClasses::firstOrCreate($status);
        }
    }
}
