<?php
// app/Services/Admin/RoomService.php

namespace App\Services\Admin;

use App\Dto\Room\RoomCreateDto;
use App\Dto\Room\RoomCreateResponseDto;
use App\Dto\Room\RoomUpdateDto;
use App\Models\Room;
use App\Models\BookingRooms;
use App\Models\BookingStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoomService
{
    /**
     * Создать новый номер
     *
     * @param RoomCreateDto $dto
     * @return RoomCreateResponseDto
     */
    public function createRoom(RoomCreateDto $dto): RoomCreateResponseDto
    {
        /** @var Room $room */
        $room = Room::create($dto->toArray());
        $room->load('room_classes');

        return new RoomCreateResponseDto([
            'number' => $room->number,
            'hotel_id' => $room->hotel_id,
            'class' => $room->room_classes?->name ?? '',
            'floor' => $room->floor
        ]);
    }

    /**
     * Обновить номер
     *
     * @param RoomUpdateDto $dto
     * @return Room
     * @throws ModelNotFoundException
     */
    public function updateRoom(RoomUpdateDto $dto): Room
    {
        /** @var Room $room */
        $room = Room::findOrFail($dto->getId());
        $room->update(array_filter($dto->toArray()));
        
        /** @var Room $loadedRoom */
        $loadedRoom = $room->load('room_classes');
        
        return $loadedRoom;
    }

    /**
     * Получить список всех номеров с пагинацией и фильтрацией
     *
     * @param int $perPage
     * @param int|null $hotelId
     * @param int|null $classId
     * @return LengthAwarePaginator
     */
    public function getAllRooms(int $perPage = 20, ?int $hotelId = null, ?int $classId = null): LengthAwarePaginator
    {
        $query = Room::with(['hotel', 'room_classes']);
        
        if ($hotelId !== null) {
            $query->where('hotel_id', $hotelId);
        }
        
        if ($classId !== null) {
            $query->where('class_id', $classId);
        }
        
        return $query->orderBy('hotel_id')->orderBy('number')->paginate($perPage);
    }

    /**
     * Получить номер по ID с дополнительной информацией
     *
     * @param int $id
     * @return array|null
     */
    public function getRoomById(int $id): ?array
    {
        /** @var Room|null $room */
        $room = Room::with(['hotel', 'room_classes'])
            ->withCount('bookings')
            ->find($id);
        
        if ($room === null) {
            return null;
        }
        
        return [
            'id' => $room->id,
            'number' => $room->number,
            'floor' => $room->floor,
            'hotel_id' => $room->hotel_id,
            'hotel_name' => $room->hotel?->name ?? '',
            'hotel_address' => $room->hotel?->address ?? '',
            'class_id' => $room->class_id,
            'class_name' => $room->room_classes?->name ?? '',
            'price_per_day' => (float) ($room->room_classes?->price_per_day ?? 0),
            'bookings_count' => (int) ($room->bookings_count ?? 0)
        ];
    }

    /**
     * Удалить номер
     *
     * @param int $id
     * @return bool
     */
    public function deleteRoom(int $id): bool
    {
        /** @var Room|null $room */
        $room = Room::find($id);
        
        if ($room === null) {
            return false;
        }
        
        // Получаем ID статусов для проверки
        $activeStatusId = $this->getBookingStatusId('active');
        $completedStatusId = $this->getBookingStatusId('completed');
        
        // Проверяем наличие активных или завершенных бронирований
        $hasActiveOrCompletedBookings = BookingRooms::where('room_id', $id)
            ->when($activeStatusId, function ($query, $statusId) {
                return $query->where('status_id', $statusId);
            })
            ->when($completedStatusId, function ($query, $statusId) {
                return $query->orWhere('status_id', $statusId);
            })
            ->exists();
        
        if ($hasActiveOrCompletedBookings) {
            return false;
        }
        
        // Удаляем связанные бронирования
        BookingRooms::where('room_id', $id)->delete();
        
        // Удаляем номер
        return (bool) $room->delete();
    }

    /**
     * Получить ID статуса бронирования по имени
     *
     * @param string $statusName
     * @return int|null
     */
    private function getBookingStatusId(string $statusName): ?int
    {
        /** @var BookingStatus|null $status */
        $status = BookingStatus::where('name', $statusName)->first();
        
        return $status?->id;
    }

    /**
     * Проверить, доступен ли номер для бронирования
     *
     * @param int $roomId
     * @param string $checkIn
     * @param string $checkOut
     * @return bool
     */
    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut): bool
    {
        $activeStatusId = $this->getBookingStatusId('active');
        $confirmedStatusId = $this->getBookingStatusId('confirmed');
        
        $conflictingBookings = BookingRooms::where('room_id', $roomId)
            ->whereIn('status_id', [$activeStatusId, $confirmedStatusId])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->exists();
        
        return !$conflictingBookings;
    }

    /**
     * Получить количество бронирований для номера
     *
     * @param int $roomId
     * @return int
     */
    public function getBookingsCount(int $roomId): int
    {
        /** @var Room|null $room */
        $room = Room::withCount('bookings')->find($roomId);
        
        return $room?->bookings_count ?? 0;
    }

    /**
     * Получить номера по отелю
     *
     * @param int $hotelId
     * @return array
     */
    public function getRoomsByHotel(int $hotelId): array
{
    /** @var \Illuminate\Database\Eloquent\Collection<int, Room> $rooms */
    $rooms = Room::with('room_classes')
        ->where('hotel_id', $hotelId)
        ->orderBy('number')
        ->get();
    
    $result = [];
    foreach ($rooms as $room) {
        $result[] = [
            'id' => $room->id,
            'number' => (string)$room->number,
            'floor' => $room->floor,
            'class_name' => $room->room_classes?->name ?? '',
            'price_per_day' => (float) ($room->room_classes?->price_per_day ?? 0)
        ];
    }
    
    return $result;
}

public function getAvailableRooms(string $checkIn, string $checkOut, ?int $hotelId = null): array
{
    $query = Room::with(['hotel', 'room_classes']);
    
    if ($hotelId !== null) {
        $query->where('hotel_id', $hotelId);
    }
    
    /** @var \Illuminate\Database\Eloquent\Collection<int, Room> $rooms */
    $rooms = $query->get();
    
    $result = [];
    foreach ($rooms as $room) {
        if ($this->isRoomAvailable($room->id, $checkIn, $checkOut)) {
            $result[] = [
                'id' => $room->id,
                'number' => (string)$room->number,
                'floor' => $room->floor,
                'hotel_name' => $room->hotel?->name ?? '',
                'class_name' => $room->room_classes?->name ?? '',
                'price_per_day' => (float) ($room->room_classes?->price_per_day ?? 0)
            ];
        }
    }
    
    return $result;
}
}