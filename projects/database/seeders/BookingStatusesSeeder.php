<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BookingStatus;
use Illuminate\Database\Seeder;
use Database\Factories\BookingStatusesFactory;

class BookingStatusesSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Номер свободен'],
            ['name' => 'Номер Занят'],
            ['name'=> 'Заявка отменена']
        ];
        foreach($statuses as $status){
            BookingStatus::firstOrCreate($status);
        }
    }
}
