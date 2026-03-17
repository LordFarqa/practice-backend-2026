<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\BookingStatus;
use Illuminate\Database\Seeder;

class BookingStatusesSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'active'],
            ['id' => 2, 'name' => 'cancelled_by_admin'],
            ['id' => 3, 'name' => 'cancelled_by_user'],
            ['id' => 4, 'name' => 'completed'],
            ['id' => 5, 'name' => 'completed'],
        ];
        
        foreach ($statuses as $status) {
            BookingStatus::updateOrCreate(
                ['id' => $status['id']],
                ['name' => $status['name']] 
            );
        }
        
    }
}