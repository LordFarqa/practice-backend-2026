<?php
// app/Services/Booking/BookingService.php

namespace App\Services\Booking;

use App\Models\User;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Reviews;
use App\Models\BookingRooms;
use App\Models\BookingStatus;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BookingService
{
    /**
     * Проверка пересечения бронирований
     *
     * @param int $roomId
     * @param string $start
     * @param string $end
     * @param int|null $excludeBookingId
     * @return bool
     */
    private function checkOverlap(int $roomId, string $start, string $end, ?int $excludeBookingId = null): bool
    {
        $query = BookingRooms::where('room_id', $roomId)
            ->where('status_id', 1) // Только активные бронирования
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('booking_start', [$start, $end])
                    ->orWhereBetween('booking_end', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('booking_start', '<=', $start)
                            ->where('booking_end', '>=', $end);
                    });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Создание нового бронирования
     *
     * @param array $data
     * @return BookingRooms
     * @throws ValidationException
     */
    public function createBooking(array $data): BookingRooms
{
    // Проверка на пересечение
    if ($this->checkOverlap((int)$data['room_id'], $data['booking_start'], $data['booking_end'])) {
        throw ValidationException::withMessages([
            'time' => 'This room is already booked for the selected time period'
        ]);
    }

    // ИСПРАВЛЕНО: убедитесь, что передаются все поля
    $booking = BookingRooms::create([
        'room_id' => $data['room_id'],
        'booking_start' => $data['booking_start'],  // ДОБАВЛЕНО
        'booking_end' => $data['booking_end'],      // ДОБАВЛЕНО
        'user_id' => $data['user_id'] ?? auth()->id(),
        'status_id' => $data['status_id'] ?? 1
    ]);
    
    $booking->load(['room.hotel', 'room.room_classes', 'user']);
    
    return $booking;
}

    /**
     * Отмена бронирования пользователем
     *
     * @param int $bookingId
     * @param int $userId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function cancelByUser(int $bookingId, int $userId): bool
    {
        /** @var BookingRooms $booking */
        $booking = BookingRooms::where('id', $bookingId)
            ->where('user_id', $userId)
            ->where('status_id', 1) // Только активные
            ->firstOrFail();

        $booking->status_id = 3; // Отменено пользователем
        return $booking->save();
    }

    /**
     * Отмена бронирования администратором
     *
     * @param int $bookingId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function cancelByAdmin(int $bookingId): bool
    {
        /** @var BookingRooms $booking */
        $booking = BookingRooms::where('id', $bookingId)
            ->where('status_id', 1) // Только активные
            ->firstOrFail();

        $booking->status_id = 2; // Отменено администратором
        return $booking->save();
    }

    /**
     * Получение бронирований пользователя
     *
     * @param int $userId
     * @return array
     */
    public function getUserBookings(int $userId): array
    {
        /** @var Collection $bookings */
        $bookings = BookingRooms::with(['room.hotel', 'room.room_classes', 'user'])
            ->where('user_id', $userId)
            ->orderBy('booking_start', 'desc')
            ->get();

        return $bookings->map(function (BookingRooms $booking) {
            $room = $booking->room;
            $hotel = $room ? $room->hotel : null;
            $roomClasses = $room ? $room->room_classes : null;

            return [
                'id' => $booking->id,
                'room_id' => $booking->room_id,
                'room_number' => $room ? (string)$room->number : '',
                'hotel_name' => $hotel ? ($hotel->name ?? '') : '',
                'room_class' => $roomClasses ? ($roomClasses->name ?? null) : null,
                'booking_start' => $booking->booking_start,
                'booking_end' => $booking->booking_end,
                'status' => $booking->status,
                'status_id' => $booking->status_id,
                'created_at' => $booking->created_at?->format('Y-m-d H:i:s')
            ];
        })->toArray();
    }

    /**
     * Получение расписания номера
     *
     * @param int $roomId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getRoomSchedule(int $roomId, string $startDate, string $endDate): array
    {
        /** @var Collection $bookings */
        $bookings = BookingRooms::with(['user'])
            ->where('room_id', $roomId)
            ->whereBetween('booking_start', [$startDate, $endDate])
            ->orderBy('booking_start')
            ->get();

        return $bookings->map(function (BookingRooms $booking) {
            $user = $booking->user;
            $userName = $user ? ($user->name . ' ' . $user->surname) : 'Unknown User';

            return [
                'id' => $booking->id,
                'start' => $booking->booking_start,
                'end' => $booking->booking_end,
                'user_name' => $userName,
                'status' => $booking->status,
                'status_id' => $booking->status_id
            ];
        })->toArray();
    }

    /**
     * Поиск доступных номеров
     *
     * @param array $criteria
     * @return LengthAwarePaginator
     */
    public function findAvailableRooms(array $criteria): LengthAwarePaginator
    {
        $query = Room::with(['hotel', 'room_classes']);

        // Применение фильтров
        $filters = $criteria['filters'] ?? [];
        
        if (isset($filters['hotel_id'])) {
            $query->where('hotel_id', (int)$filters['hotel_id']);
        }
        if (isset($filters['class_id'])) {
            $query->where('class_id', (int)$filters['class_id']);
        }
        if (isset($filters['floor'])) {
            $query->where('floor', (int)$filters['floor']);
        }
        if (isset($filters['min_price'])) {
            $query->whereHas('room_classes', function($q) use ($filters) {
                $q->where('price_per_day', '>=', (float)$filters['min_price']);
            });
        }
        if (isset($filters['max_price'])) {
            $query->whereHas('room_classes', function($q) use ($filters) {
                $q->where('price_per_day', '<=', (float)$filters['max_price']);
            });
        }

        // Фильтр по дате и времени
        if (isset($criteria['date']) && isset($criteria['start_time']) && isset($criteria['end_time'])) {
            $startDateTime = $criteria['date'] . ' ' . $criteria['start_time'];
            $endDateTime = $criteria['date'] . ' ' . $criteria['end_time'];

            $bookedRoomIds = BookingRooms::where('status_id', 1)
                ->where(function ($q) use ($startDateTime, $endDateTime) {
                    $q->whereBetween('booking_start', [$startDateTime, $endDateTime])
                        ->orWhereBetween('booking_end', [$startDateTime, $endDateTime])
                        ->orWhere(function ($q2) use ($startDateTime, $endDateTime) {
                            $q2->where('booking_start', '<=', $startDateTime)
                                ->where('booking_end', '>=', $endDateTime);
                        });
                })
                ->pluck('room_id')
                ->toArray();

            $query->whereNotIn('id', $bookedRoomIds);
        }

        $perPage = (int)($criteria['per_page'] ?? 15);
        return $query->paginate($perPage);
    }

    /**
     * Получение завершенных бронирований пользователя без отзывов
     *
     * @param int $userId
     * @return Collection
     */
    public function getCompletedUserBookings(int $userId): Collection
    {
        /** @var Collection $bookings */
        $bookings = BookingRooms::with(['room.hotel', 'room.room_classes'])
            ->where('user_id', $userId)
            ->where('status_id', 4) // Завершено
            ->whereDoesntHave('reviews') // У которых нет отзыва
            ->get();

        return $bookings;
    }

    /**
     * Получение всех бронирований (админ)
     *
     * @param int $perPage
     * @param string|null $status
     * @param int|null $userId
     * @return LengthAwarePaginator
     */
    public function getAllBookings(int $perPage = 20, ?string $status = null, ?int $userId = null): LengthAwarePaginator
    {
        $query = BookingRooms::with(['room.hotel', 'room.room_classes', 'user']);

        if ($status && is_string($status)) {
            $query->where('status', 'like', "%$status%");
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Получение бронирования по ID
     *
     * @param int $id
     * @return array|null
     */
    public function getBookingById(int $id): ?array
    {
        /** @var BookingRooms|null $booking */
        $booking = BookingRooms::with(['room.hotel', 'room.room_classes', 'user'])
            ->find($id);

        if (!$booking) {
            return null;
        }

        $room = $booking->room;
        $hotel = $room ? $room->hotel : null;
        $roomClasses = $room ? $room->room_classes : null;
        $user = $booking->user;

        return [
            'id' => $booking->id,
            'room' => [
                'id' => $room ? $room->id : null,
                'number' => $room ? (string)$room->number : '',
                'floor' => $room ? $room->floor : null,
                'class' => $roomClasses ? ($roomClasses->name ?? null) : null,
                'price_per_day' => $roomClasses ? ($roomClasses->price_per_day ?? null) : null,
                'hotel' => [
                    'id' => $hotel ? $hotel->id : null,
                    'name' => $hotel ? ($hotel->name ?? '') : '',
                    'address' => $hotel ? ($hotel->address ?? '') : ''
                ]
            ],
            'user' => [
                'id' => $user ? $user->id : null,
                'name' => $user ? ($user->name ?? '') : '',
                'surname' => $user ? ($user->surname ?? '') : '',
                'email' => $user ? ($user->email ?? '') : '',
                'phone' => $user ? ($user->phone_number ?? '') : ''
            ],
            'booking_start' => $booking->booking_start,
            'booking_end' => $booking->booking_end,
            'status' => $booking->status,
            'status_id' => $booking->status_id,
            'created_at' => $booking->created_at?->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Удаление бронирования
     *
     * @param int $id
     * @return bool
     */
    public function deleteBooking(int $id): bool
    {
        /** @var BookingRooms|null $booking */
        $booking = BookingRooms::find($id);

        if (!$booking) {
            return false;
        }

        // Удаляем связанные отзывы
        if (method_exists($booking, 'reviews')) {
            $booking->reviews()->delete();
        }

        return (bool)$booking->delete();
    }

    /**
     * Получение системной статистики
     *
     * @return array
     */
    public function getSystemStats(): array
    {
        $averageRating = Reviews::avg('rating');
        
        return [
            'total_users' => User::count(),
            'total_hotels' => Hotel::count(),
            'total_rooms' => Room::count(),
            'total_bookings' => BookingRooms::count(),
            'active_bookings' => BookingRooms::where('status_id', 1)->count(),
            'completed_bookings' => BookingRooms::where('status_id', 4)->count(),
            'cancelled_bookings' => BookingRooms::whereIn('status_id', [2, 3])->count(),
            'total_reviews' => Reviews::count(),
            'average_rating' => $averageRating ? round((float)$averageRating, 1) : 0
        ];
    }

    /**
     * Получение статистики по бронированиям
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getBookingStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = BookingRooms::query();

        if ($startDate) {
            $query->whereDate('booking_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('booking_end', '<=', $endDate);
        }

        /** @var Collection $bookings */
        $bookings = $query->get();

        return [
            'total' => $bookings->count(),
            'by_status' => [
                'active' => $bookings->where('status_id', 1)->count(),
                'completed' => $bookings->where('status_id', 4)->count(),
                'cancelled_by_admin' => $bookings->where('status_id', 2)->count(),
                'cancelled_by_user' => $bookings->where('status_id', 3)->count()
            ],
            'revenue' => $this->calculateRevenue($startDate, $endDate)
        ];
    }

    /**
     * Расчет выручки
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return float
     */
    private function calculateRevenue(?string $startDate = null, ?string $endDate = null): float
    {
        $query = BookingRooms::where('status_id', 4)
            ->join('rooms', 'booking_rooms.room_id', '=', 'rooms.id')
            ->join('room_classes', 'rooms.class_id', '=', 'room_classes.id');

        if ($startDate) {
            $query->whereDate('booking_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('booking_end', '<=', $endDate);
        }

        $total = $query->sum('room_classes.price_per_day');
        
        return (float)($total ?? 0);
    }

    /**
     * Проверка доступности номера на даты
     *
     * @param int $roomId
     * @param string $start
     * @param string $end
     * @return bool
     */
    public function isRoomAvailable(int $roomId, string $start, string $end): bool
    {
        return !$this->checkOverlap($roomId, $start, $end);
    }

    /**
     * Получение активных бронирований пользователя
     *
     * @param int $userId
     * @return Collection
     */
    public function getActiveUserBookings(int $userId): Collection
    {
        /** @var Collection $bookings */
        $bookings = BookingRooms::with(['room.hotel', 'room.room_classes'])
            ->where('user_id', $userId)
            ->where('status_id', 1)
            ->where('booking_start', '>', now())
            ->orderBy('booking_start', 'asc')
            ->get();

        return $bookings;
    }

    /**
     * Получение истории бронирований пользователя
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getUserBookingHistory(int $userId, int $limit = 10): Collection
    {
        /** @var Collection $bookings */
        $bookings = BookingRooms::with(['room.hotel', 'room.room_classes'])
            ->where('user_id', $userId)
            ->whereIn('status_id', [2, 3, 4])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $bookings;
    }
}