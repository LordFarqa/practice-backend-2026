<?php
// tests/Unit/Services/Admin/RoomServiceTest.php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Services\Admin\RoomService;
use App\Models\Room;
use App\Models\Hotel;
use App\Models\RoomClasses;
use App\Models\BookingRooms;
use App\Models\User;
use App\Models\BookingStatus;
use App\Dto\Room\RoomCreateDto;
use App\Dto\Room\RoomUpdateDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoomServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoomService $roomService;
    private Hotel $hotel;
    private RoomClasses $roomClass;
    private User $user;
    private BookingStatus $activeStatus;
    private BookingStatus $completedStatus;
    private BookingStatus $cancelledByAdminStatus;
    private BookingStatus $cancelledByUserStatus;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->roomService = new RoomService();
        
        // Создаем статусы бронирований с правильными ID
        $this->activeStatus = BookingStatus::create(['id' => 1, 'name' => 'active']);
        $this->cancelledByAdminStatus = BookingStatus::create(['id' => 2, 'name' => 'cancelled_by_admin']);
        $this->cancelledByUserStatus = BookingStatus::create(['id' => 3, 'name' => 'cancelled_by_user']);
        $this->completedStatus = BookingStatus::create(['id' => 4, 'name' => 'completed']);
        
        // Создаем тестовые данные
        $this->hotel = Hotel::create([
            'name' => 'Test Hotel',
            'address' => '123 Test Street',
            'class' => 4
        ]);
        
        $this->roomClass = RoomClasses::create([
            'name' => 'Deluxe',
            'price_per_day' => 150.00
        ]);
        
        $this->user = User::create([
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@example.com',
            'phone_number' => '123456789',
            'password' => bcrypt('password')
        ]);
    }

    /** @test */
    public function it_can_create_a_room()
    {
        $dto = new RoomCreateDto($this->hotel->id, [
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $result = $this->roomService->createRoom($dto);
        
        $this->assertInstanceOf(\App\Dto\Room\RoomCreateResponseDto::class, $result);
        
        $roomArray = $result->toArray();
        $this->assertEquals(101, $roomArray['number']);
        $this->assertEquals($this->hotel->id, $roomArray['hotel_id']);
        $this->assertEquals('Deluxe', $roomArray['class']);
        $this->assertEquals(1, $roomArray['floor']);
        
        $this->assertDatabaseHas('rooms', [
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
    }

    /** @test */
    public function it_can_update_a_room()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $dto = new RoomUpdateDto($room->id, [
            'number' => 102,
            'floor' => 2
        ]);

        $result = $this->roomService->updateRoom($dto);
        
        $this->assertInstanceOf(Room::class, $result);
        $this->assertEquals(102, $result->number);
        $this->assertEquals(2, $result->floor);
        $this->assertEquals($this->roomClass->id, $result->class_id);
        
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'number' => 102,
            'floor' => 2
        ]);
    }

    /** @test */
    public function it_throws_exception_when_updating_nonexistent_room()
    {
        $this->expectException(ModelNotFoundException::class);
        
        $dto = new RoomUpdateDto(99999, [
            'number' => 102
        ]);
        
        $this->roomService->updateRoom($dto);
    }

    /** @test */
    public function it_can_get_all_rooms_with_pagination()
    {
        for ($i = 1; $i <= 25; $i++) {
            Room::create([
                'hotel_id' => $this->hotel->id,
                'number' => 100 + $i,
                'class_id' => $this->roomClass->id,
                'floor' => ceil($i / 5)
            ]);
        }

        $result = $this->roomService->getAllRooms(10);
        
        $this->assertEquals(10, $result->count());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    /** @test */
    public function it_can_filter_rooms_by_hotel_id()
    {
        $hotel2 = Hotel::create([
            'name' => 'Second Hotel',
            'address' => '456 Other Street',
            'class' => 3
        ]);
        
        Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        Room::create([
            'hotel_id' => $hotel2->id,
            'number' => 201,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $result = $this->roomService->getAllRooms(20, $this->hotel->id);
        
        $this->assertEquals(1, $result->count());
        $this->assertEquals(101, $result->first()->number);
    }

    /** @test */
    public function it_can_filter_rooms_by_class_id()
    {
        $class2 = RoomClasses::create([
            'name' => 'Standard',
            'price_per_day' => 80.00
        ]);
        
        Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 102,
            'class_id' => $class2->id,
            'floor' => 1
        ]);

        $result = $this->roomService->getAllRooms(20, null, $class2->id);
        
        $this->assertEquals(1, $result->count());
        $this->assertEquals(102, $result->first()->number);
    }

    /** @test */
    public function it_can_filter_rooms_by_both_hotel_and_class()
    {
        $hotel2 = Hotel::create([
            'name' => 'Second Hotel',
            'address' => '456 Other Street',
            'class' => 3
        ]);
        
        $class2 = RoomClasses::create([
            'name' => 'Standard',
            'price_per_day' => 80.00
        ]);
        
        Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 102,
            'class_id' => $class2->id,
            'floor' => 1
        ]);
        
        Room::create([
            'hotel_id' => $hotel2->id,
            'number' => 201,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $result = $this->roomService->getAllRooms(20, $this->hotel->id, $this->roomClass->id);
        
        $this->assertEquals(1, $result->count());
        $this->assertEquals(101, $result->first()->number);
    }

    /** @test */
    public function it_can_get_room_by_id()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $result = $this->roomService->getRoomById($room->id);
        
        $this->assertIsArray($result);
        $this->assertEquals($room->id, $result['id']);
        $this->assertEquals(101, $result['number']);
        $this->assertEquals($this->hotel->id, $result['hotel_id']);
        $this->assertEquals($this->hotel->name, $result['hotel_name']);
        $this->assertEquals('Deluxe', $result['class_name']);
        $this->assertEquals(150.00, $result['price_per_day']);
        $this->assertEquals(0, $result['bookings_count']);
    }

    /** @test */
    public function it_returns_null_when_room_not_found_by_id()
    {
        $result = $this->roomService->getRoomById(99999);
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_delete_room_without_bookings()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $result = $this->roomService->deleteRoom($room->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    /** @test */
    public function it_cannot_delete_room_with_existing_active_bookings()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        // Создаем активное бронирование
        BookingRooms::create([
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'booking_start' => now()->addDay(),
            'booking_end' => now()->addDays(2),
            'status_id' => $this->activeStatus->id
        ]);

        $result = $this->roomService->deleteRoom($room->id);
        
        $this->assertFalse($result);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('booking_rooms', ['room_id' => $room->id]);
    }

    /** @test */
    public function it_cannot_delete_room_with_completed_bookings()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        // Создаем завершенное бронирование
        BookingRooms::create([
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'booking_start' => now()->subDays(5),
            'booking_end' => now()->subDays(3),
            'status_id' => $this->completedStatus->id
        ]);

        $result = $this->roomService->deleteRoom($room->id);
        
        $this->assertFalse($result);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('booking_rooms', ['room_id' => $room->id]);
    }

    /** @test */
    public function it_can_delete_room_with_cancelled_bookings()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        // Создаем отмененное бронирование
        $booking = BookingRooms::create([
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'booking_start' => now()->addDay(),
            'booking_end' => now()->addDays(2),
            'status_id' => $this->cancelledByAdminStatus->id
        ]);

        $result = $this->roomService->deleteRoom($room->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseMissing('booking_rooms', ['id' => $booking->id]);
    }

    /** @test */
    public function it_returns_false_when_deleting_nonexistent_room()
    {
        $result = $this->roomService->deleteRoom(99999);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_room_with_bookings_count()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        // Создаем несколько бронирований
        BookingRooms::create([
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'booking_start' => now()->addDay(),
            'booking_end' => now()->addDays(2),
            'status_id' => $this->activeStatus->id
        ]);
        
        BookingRooms::create([
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'booking_start' => now()->addDays(3),
            'booking_end' => now()->addDays(5),
            'status_id' => $this->completedStatus->id
        ]);

        $result = $this->roomService->getRoomById($room->id);
        
        $this->assertEquals(2, $result['bookings_count']);
    }

    /** @test */
    public function it_orders_rooms_by_hotel_and_number()
    {
        $hotel2 = Hotel::create([
            'name' => 'Second Hotel',
            'address' => '456 Other Street',
            'class' => 3
        ]);
        
        Room::create([
            'hotel_id' => $hotel2->id,
            'number' => 105,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 102,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);
        
        Room::create([
            'hotel_id' => $hotel2->id,
            'number' => 104,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $result = $this->roomService->getAllRooms(20);
        
        $rooms = $result->items();
        
        $this->assertEquals($this->hotel->id, $rooms[0]->hotel_id);
        $this->assertEquals(101, $rooms[0]->number);
        $this->assertEquals($this->hotel->id, $rooms[1]->hotel_id);
        $this->assertEquals(102, $rooms[1]->number);
        $this->assertEquals($hotel2->id, $rooms[2]->hotel_id);
        $this->assertEquals(104, $rooms[2]->number);
        $this->assertEquals($hotel2->id, $rooms[3]->hotel_id);
        $this->assertEquals(105, $rooms[3]->number);
    }

    /** @test */
    public function it_returns_empty_pagination_when_no_rooms()
    {
        $result = $this->roomService->getAllRooms(10);
        
        $this->assertEquals(0, $result->count());
        $this->assertEquals(0, $result->total());
    }

    /** @test */
    public function it_handles_update_with_only_some_fields()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $dto = new RoomUpdateDto($room->id, [
            'number' => 102
        ]);

        $result = $this->roomService->updateRoom($dto);
        
        $this->assertEquals(102, $result->number);
        $this->assertEquals(1, $result->floor);
        $this->assertEquals($this->roomClass->id, $result->class_id);
        
        $dto = new RoomUpdateDto($room->id, [
            'floor' => 3
        ]);
        
        $result = $this->roomService->updateRoom($dto);
        
        $this->assertEquals(102, $result->number);
        $this->assertEquals(3, $result->floor);
    }

    /** @test */
    public function it_returns_correct_room_structure_with_all_relations()
    {
        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'number' => 101,
            'class_id' => $this->roomClass->id,
            'floor' => 1
        ]);

        $result = $this->roomService->getRoomById($room->id);
        
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('floor', $result);
        $this->assertArrayHasKey('hotel_id', $result);
        $this->assertArrayHasKey('hotel_name', $result);
        $this->assertArrayHasKey('hotel_address', $result);
        $this->assertArrayHasKey('class_id', $result);
        $this->assertArrayHasKey('class_name', $result);
        $this->assertArrayHasKey('price_per_day', $result);
        $this->assertArrayHasKey('bookings_count', $result);
    }
}