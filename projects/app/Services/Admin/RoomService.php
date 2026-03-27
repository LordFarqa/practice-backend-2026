<?php
// app/Services/Admin/RoomService.php

namespace App\Services\Admin;

use App\Dto\Room\RoomCreateDto;
use App\Dto\Room\RoomCreateResponseDto;
use App\Dto\Room\RoomUpdateDto;
use App\Models\Room;
use App\Models\BookingRooms;

class RoomService
{
    public function createRoom(RoomCreateDto $dto)
    {
        $room = Room::create($dto->toArray());
        $room->load('room_classes');

        return new RoomCreateResponseDto([
            'number' => $room->number,
            'hotel_id' => $room->hotel_id,
            'class' => $room->room_classes->name,
            'floor' => $room->floor
        ]);
    }

    public function updateRoom(RoomUpdateDto $dto)
    {
        $room = Room::findOrFail($dto->getId());
        $room->update(array_filter($dto->toArray()));
        return $room->load('room_classes');
    }

    public function getAllRooms(int $perPage = 20, ?int $hotelId = null, ?int $classId = null)
    {
        $query = Room::with(['hotel', 'room_classes']);
        
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        return $query->orderBy('hotel_id')->orderBy('number')->paginate($perPage);
    }

    public function getRoomById(int $id): ?array
    {
        $room = Room::with(['hotel', 'room_classes'])
            ->withCount('bookings')
            ->find($id);
        
        if (!$room) {
            return null;
        }
        
        return [
            'id' => $room->id,
            'number' => $room->number,
            'floor' => $room->floor,
            'hotel_id' => $room->hotel_id,
            'hotel_name' => $room->hotel->name,
            'hotel_address' => $room->hotel->address,
            'class_id' => $room->class_id,
            'class_name' => $room->room_classes->name ?? null,
            'price_per_day' => $room->room_classes->price_per_day ?? null,
            'bookings_count' => $room->bookings_count ?? 0
        ];
    }

    public function deleteRoom(int $id): bool
    {
        $room = Room::find($id);
        
        if (!$room) {
            return false;
        }
        
        $hasActiveOrCompletedBookings = BookingRooms::where('room_id', $id)
            ->whereIn('status_id', [1, 4])
            ->exists();
        
        if ($hasActiveOrCompletedBookings) {
            return false;
        }

        BookingRooms::where('room_id', $id)->delete();
        
        return $room->delete();
    }
}