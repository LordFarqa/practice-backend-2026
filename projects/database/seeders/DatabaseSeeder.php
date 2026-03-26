<?php

namespace Database\Seeders;

use App\Models\BookingRooms;
use App\Models\Hotel;
use App\Models\Reviews;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Сначала заливаем базовые справочники
        $this->call([
            BookingStatusesSeeder::class,
            RoomClassesSeeder::class,
            UserRoleSeeder::class,
        ]);
        
        // 2. Создаем администратора
        $admin = User::factory()->admin()->create();
        $this->command->info('Admin user created: admin / password');
        
        // 3. Создаем тестовых пользователей
        User::factory()->count(10)->create();
        $this->command->info('10 regular users created');
        
        // 4. Создаем отели с номерами
        Hotel::factory()->count(5)->create();
        $this->command->info('5 hotels with rooms created');
        
        // 5. Ждем немного, чтобы убедиться, что все данные созданы
        sleep(1);
        
        // 6. Создаем бронирования (используя существующие ID)
        try {
            BookingRooms::factory()->count(20)->create();
            $this->command->info('20 bookings created');
        } catch (\Exception $e) {
            $this->command->error('Error creating bookings: ' . $e->getMessage());
        }
        
        // 7. Создаем отзывы
        try {
            Reviews::factory()->count(15)->create();
            $this->command->info('15 reviews created');
        } catch (\Exception $e) {
            $this->command->error('Error creating reviews: ' . $e->getMessage());
        }
        
        $this->command->info('====================================');
        $this->command->info('Database seeding completed!');
        $this->command->info('Admin login: admin');
        $this->command->info('Admin password: password');
        
        // Выводим статистику
        $this->command->info('====================================');
        $this->command->info('Statistics:');
        $this->command->info('Users: ' . User::count());
        $this->command->info('Hotels: ' . Hotel::count());
        $this->command->info('Rooms: ' . \App\Models\Room::count());
        $this->command->info('Bookings: ' . BookingRooms::count());
        $this->command->info('Reviews: ' . Reviews::count());
    }
}