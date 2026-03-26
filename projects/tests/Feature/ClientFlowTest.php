<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomClasses;
use App\Models\BookingRooms;
use App\Models\BookingStatus;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ClientFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Полный сценарий клиента
     */
    public function test_full_client_journey()
    {
        echo "\n\033[35m══════════════════════════════════════════════════════════════════════\033[0m\n";
        echo "\033[36m📌 ПОЛНЫЙ СЦЕНАРИЙ КЛИЕНТА\033[0m\n";
        echo "\033[33m────────────────────────────────────────────────────────────────────\033[0m\n";

        // ==================== ПОДГОТОВКА ТЕСТОВЫХ ДАННЫХ ====================
        $this->prepareTestData();

        // ==================== 1. РЕГИСТРАЦИЯ ====================
        echo "\n\033[36m📌 1. РЕГИСТРАЦИЯ НОВОГО КЛИЕНТА\033[0m\n";
        
        $uniqueId = uniqid();
        $testEmail = "ivan.test_{$uniqueId}@example.com";
        $testLogin = "ivan_{$uniqueId}";
        
        $registerResponse = $this->postJson('/api/register', [
            'name' => 'Иван',
            'surname' => 'Петров',
            'last_name' => 'Иванович',
            'email' => $testEmail,
            'phone_number' => '+79991112233',
            'login' => $testLogin,
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $registerResponse->assertStatus(201);
        $token = $registerResponse->json('token');
        $userId = $registerResponse->json('user.id');
        
        echo "\033[32m✅ Клиент успешно зарегистрирован\033[0m\n";
        echo "\033[34mℹ️  ID пользователя: {$userId}\033[0m\n";
        echo "\033[34mℹ️  Логин: {$testLogin}\033[0m\n";

        // ==================== 2. ПРОСМОТР ПРОФИЛЯ ====================
        echo "\n\033[36m📌 2. ПРОСМОТР ПРОФИЛЯ\033[0m\n";
        
        $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');
        
        $profileResponse->assertStatus(200);
        $profileResponse->assertJson([
            'id' => $userId,
            'name' => 'Иван',
            'surname' => 'Петров',
            'email' => $testEmail,
            'login' => $testLogin
        ]);
        
        echo "\033[32m✅ Профиль успешно получен\033[0m\n";

        // ==================== 3. ПРОСМОТР СПИСКА ОТЕЛЕЙ ====================
        echo "\n\033[36m📌 3. ПРОСМОТР СПИСКА ОТЕЛЕЙ\033[0m\n";
        
        $hotelsResponse = $this->getJson('/api/hotels?per_page=10');
        $hotelsResponse->assertStatus(200);
        
        $hotels = $hotelsResponse->json('data');
        echo "\033[32m✅ Найдено отелей: " . count($hotels) . "\033[0m\n";

        // ==================== 4. ДЕТАЛЬНАЯ ИНФОРМАЦИЯ ОБ ОТЕЛЕ ====================
        echo "\n\033[36m📌 4. ПРОСМОТР ДЕТАЛЬНОЙ ИНФОРМАЦИИ ОБ ОТЕЛЕ\033[0m\n";
        
        $hotel = Hotel::first();
        $hotelDetailResponse = $this->getJson("/api/hotels/{$hotel->id}");
        $hotelDetailResponse->assertStatus(200);
        
        echo "\033[32m✅ Информация об отеле получена\033[0m\n";
        echo "\033[34mℹ️  Отель: {$hotel->name}\033[0m\n";

        // ==================== 5. ПОИСК СВОБОДНЫХ КОМНАТ ====================
        echo "\n\033[36m📌 5. ПОИСК СВОБОДНЫХ КОМНАТ\033[0m\n";
        
        $date = Carbon::now()->addDays(1)->format('Y-m-d');
        $searchResponse = $this->getJson("/api/rooms/available?date={$date}&start_time=10:00:00&end_time=12:00:00&per_page=10");
        $searchResponse->assertStatus(200);
        
        $rooms = $searchResponse->json('data');
        echo "\033[32m✅ Найдено свободных комнат: " . count($rooms) . "\033[0m\n";

        // ==================== 6. ПОИСК С ФИЛЬТРАЦИЕЙ ====================
        echo "\n\033[36m📌 6. ПОИСК С ФИЛЬТРАЦИЕЙ\033[0m\n";
        
        $filterResponse = $this->getJson("/api/rooms/available?date={$date}&start_time=10:00:00&end_time=12:00:00&filters[floor]=1&sort_by=price&sort_direction=asc");
        $filterResponse->assertStatus(200);
        
        echo "\033[32m✅ Фильтрация применена успешно\033[0m\n";

        // ==================== 7. СОЗДАНИЕ БРОНИРОВАНИЯ ====================
        echo "\n\033[36m📌 7. СОЗДАНИЕ БРОНИРОВАНИЯ\033[0m\n";
        
        $room = Room::first();
        $bookingDate = Carbon::now()->addDays(2)->format('Y-m-d');
        
        $bookingResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/bookings', [
                'room_id' => $room->id,
                'booking_start' => "{$bookingDate} 14:00:00",
                'booking_end' => "{$bookingDate} 16:00:00"
            ]);
        
        $bookingResponse->assertStatus(201);
        $bookingId = $bookingResponse->json('id');
        
        echo "\033[32m✅ Бронирование создано успешно!\033[0m\n";
        echo "\033[34mℹ️  ID бронирования: {$bookingId}\033[0m\n";

        // ==================== 8. ПРОСМОТР МОИХ БРОНИРОВАНИЙ ====================
        echo "\n\033[36m📌 8. ПРОСМОТР МОИХ БРОНИРОВАНИЙ\033[0m\n";
        
        $myBookingsResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/bookings/my');
        
        $myBookingsResponse->assertStatus(200);
        $myBookings = $myBookingsResponse->json('data');
        
        echo "\033[32m✅ Найдено бронирований: " . count($myBookings) . "\033[0m\n";

        // ==================== 9. ОТМЕНА БРОНИРОВАНИЯ ====================
        echo "\n\033[36m📌 9. ОТМЕНА БРОНИРОВАНИЯ\033[0m\n";
        
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/bookings/{$bookingId}/cancel");
        
        $cancelResponse->assertStatus(200);
        
        echo "\033[32m✅ Бронирование успешно отменено\033[0m\n";

        // ==================== 10. ПРОВЕРКА СТАТУСА ПОСЛЕ ОТМЕНЫ ====================
        echo "\n\033[36m📌 10. ПРОВЕРКА СТАТУСА БРОНИРОВАНИЯ\033[0m\n";
        
        $statusResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/bookings/my');
        
        $statusResponse->assertStatus(200);
        $updatedBooking = collect($statusResponse->json('data'))->firstWhere('id', $bookingId);
        
        $this->assertEquals('cancelled_by_user', $updatedBooking['status']);
        echo "\033[32m✅ Статус бронирования: cancelled_by_user\033[0m\n";

        // ==================== 11. СОЗДАНИЕ ЗАВЕРШЕННОГО БРОНИРОВАНИЯ ====================
        echo "\n\033[36m📌 11. СОЗДАНИЕ ЗАВЕРШЕННОГО БРОНИРОВАНИЯ ДЛЯ ОТЗЫВА\033[0m\n";
        
        // Создаем бронирование на будущую дату
        $futureDate = Carbon::now()->addDays(3)->format('Y-m-d');
        $newBookingResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/bookings', [
                'room_id' => $room->id,
                'booking_start' => "{$futureDate} 10:00:00",
                'booking_end' => "{$futureDate} 12:00:00"
            ]);
        
        $newBookingResponse->assertStatus(201);
        $newBookingId = $newBookingResponse->json('id');
        
        // Вручную меняем статус на завершенный и даты на прошедшие
        BookingRooms::where('id', $newBookingId)->update([
            'status_id' => 4,
            'booking_start' => Carbon::now()->subDays(3)->format('Y-m-d H:i:s'),
            'booking_end' => Carbon::now()->subDays(3)->addHours(2)->format('Y-m-d H:i:s')
        ]);
        
        echo "\033[32m✅ Завершенное бронирование создано\033[0m\n";
        echo "\033[34mℹ️  ID: {$newBookingId}\033[0m\n";

        // ==================== 12. ПОЛУЧЕНИЕ ЗАВЕРШЕННЫХ БРОНИРОВАНИЙ ====================
        echo "\n\033[36m📌 12. ПОЛУЧЕНИЕ ЗАВЕРШЕННЫХ БРОНИРОВАНИЙ ДЛЯ ОТЗЫВОВ\033[0m\n";
        
        $completedResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/bookings/completed');
        
        $completedResponse->assertStatus(200);
        $completedBookings = $completedResponse->json('data.data');
        
        echo "\033[32m✅ Найдено завершенных бронирований: " . count($completedBookings) . "\033[0m\n";

        // ==================== 13. СОЗДАНИЕ ОТЗЫВА ====================
        echo "\n\033[36m📌 13. СОЗДАНИЕ ОТЗЫВА\033[0m\n";
        
        $reviewResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/reviews', [
                'hotel_id' => $hotel->id,
                'booking_room_id' => $newBookingId,
                'coment' => 'Отличный отель! Прекрасный сервис, чистые номера, вежливый персонал!',
                'rating' => 5
            ]);
        
        $reviewResponse->assertStatus(201);
        
        echo "\033[32m✅ Отзыв успешно создан!\033[0m\n";

        // ==================== 14. ПРОСМОТР ОТЗЫВОВ ====================
        echo "\n\033[36m📌 14. ПРОСМОТР ОТЗЫВОВ ОБ ОТЕЛЕ\033[0m\n";
        
        $reviewsResponse = $this->getJson("/api/hotels/{$hotel->id}/reviews?per_page=10");
        $reviewsResponse->assertStatus(200);
        
        $averageRating = $reviewsResponse->json('average_rating');
        $totalReviews = $reviewsResponse->json('total_reviews');
        
        echo "\033[32m✅ Отзывы получены\033[0m\n";
        echo "\033[34mℹ️  Средний рейтинг: {$averageRating}\033[0m\n";
        echo "\033[34mℹ️  Всего отзывов: {$totalReviews}\033[0m\n";

        // ==================== 15. ВЫХОД ИЗ СИСТЕМЫ ====================
        echo "\n\033[36m📌 15. ВЫХОД ИЗ СИСТЕМЫ\033[0m\n";
        
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');
        
        $logoutResponse->assertStatus(200);
        
        echo "\033[32m✅ Выход выполнен успешно\033[0m\n";

        // ==================== ИТОГИ ====================
        echo "\n\033[35m══════════════════════════════════════════════════════════════════════\033[0m\n";
        echo "\033[32m✨ ВСЕ ТЕСТЫ КЛИЕНТСКОГО ФЛОУ УСПЕШНО ЗАВЕРШЕНЫ!\033[0m\n";
        echo "\033[35m══════════════════════════════════════════════════════════════════════\033[0m\n";
        
        echo "\n📊 СТАТИСТИКА ВЫПОЛНЕНИЯ:\n";
        echo "✅ Регистрация\n";
        echo "✅ Просмотр профиля\n";
        echo "✅ Просмотр отелей\n";
        echo "✅ Детали отеля\n";
        echo "✅ Поиск комнат\n";
        echo "✅ Фильтрация\n";
        echo "✅ Создание бронирования\n";
        echo "✅ Просмотр бронирований\n";
        echo "✅ Отмена бронирования\n";
        echo "✅ Проверка статуса\n";
        echo "✅ Создание завершенного бронирования\n";
        echo "✅ Получение завершенных бронирований\n";
        echo "✅ Создание отзыва\n";
        echo "✅ Просмотр отзывов\n";
        echo "✅ Выход из системы\n";
    }

    /**
     * Подготовка тестовых данных
     */
    private function prepareTestData()
    {
        // Создаем роли
        Role::insert([
            ['id' => 1, 'name' => 'Администратор'],
            ['id' => 2, 'name' => 'Пользователь'],
        ]);

        // Создаем статусы бронирований
        BookingStatus::insert([
            ['id' => 1, 'name' => 'active'],
            ['id' => 2, 'name' => 'cancelled_by_admin'],
            ['id' => 3, 'name' => 'cancelled_by_user'],
            ['id' => 4, 'name' => 'completed'],
        ]);

        // Создаем классы номеров
        RoomClasses::insert([
            ['name' => 'Standart', 'price_per_day' => 2500],
            ['name' => 'Comfort', 'price_per_day' => 3500],
            ['name' => 'Business', 'price_per_day' => 5500],
            ['name' => 'Apartments', 'price_per_day' => 8500],
            ['name' => 'Studio', 'price_per_day' => 4500],
            ['name' => 'Lux', 'price_per_day' => 10000],
            ['name' => 'Presidential', 'price_per_day' => 15000],
        ]);

        // Создаем отель
        $hotel = Hotel::create([
            'name' => 'Grand Plaza Hotel',
            'address' => json_encode([
                'Страна' => 'Россия',
                'Город' => 'Москва',
                'Улица' => 'ул. Тверская, 10'
            ]),
            'class' => '5 stars'
        ]);

        // Создаем комнаты
        Room::create([
            'number' => '101',
            'hotel_id' => $hotel->id,
            'class_id' => 1,
            'floor' => 1
        ]);

        Room::create([
            'number' => '102',
            'hotel_id' => $hotel->id,
            'class_id' => 1,
            'floor' => 1
        ]);

        Room::create([
            'number' => '201',
            'hotel_id' => $hotel->id,
            'class_id' => 3,
            'floor' => 2
        ]);
    }
}