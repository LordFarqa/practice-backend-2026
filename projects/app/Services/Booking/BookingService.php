<?php
namespace App\Services\Booking;

use App\Models\BookingRooms;
use App\Models\Room;
use App\Dto\Booking\CreateBookingDto;
use App\Dto\Booking\BookingResponseDto;
use App\Dto\Booking\BookingsResponseDto;
use App\Dto\Booking\SearchCriteriaDto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{

    private function checkOverlap(int $room_id, string $start, string $end, ?int $excludeBookingId = null): bool
    {
        $query = BookingRooms::where('room_id', $room_id)
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

    public function createBooking(CreateBookingDto $dto): BookingResponseDto
    {
        // Проверка на пересечение
        if ($this->checkOverlap($dto->toArray()['room_id'], $dto->toArray()['booking_start'], $dto->toArray()['booking_end'])) {
            throw ValidationException::withMessages([
                'time' => 'This room is already booked for the selected time period'
            ]);
        }

        $booking = BookingRooms::create($dto->toArray());
        $booking->load(['room.hotel', 'room.room_classes', 'user', 'status']);

        return new BookingResponseDto($booking);
    }

    public function cancelByUser(int $bookingId, int $userId): bool
    {
        $booking = BookingRooms::where('id', $bookingId)
            ->where('user_id', $userId)
            ->where('status_id', 1) // Только активные
            ->firstOrFail();

        $booking->status_id = 3; // Отменено пользователем
        return $booking->save();
    }

    public function cancelByAdmin(int $bookingId): bool
    {
        $booking = BookingRooms::where('id', $bookingId)
            ->where('status_id', 1) // Только активные
            ->firstOrFail();

        $booking->status_id = 2; // Отменено администратором
        return $booking->save();
    }

    public function getUserBookings(int $userId): BookingsResponseDto
    {
        $bookings = BookingRooms::with(['room.hotel', 'room.room_classes', 'user', 'status'])
            ->where('user_id', $userId)
            ->orderBy('booking_start', 'desc')
            ->get();

        return new BookingsResponseDto($bookings);
    }

    public function getRoomSchedule(int $roomId, string $startDate, string $endDate): array
    {
        $bookings = BookingRooms::with(['user', 'status'])
            ->where('room_id', $roomId)
            ->whereBetween('booking_start', [$startDate, $endDate])
            ->orderBy('booking_start')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'start' => $booking->booking_start,
                    'end' => $booking->booking_end,
                    'user_name' => $booking->user->name . ' ' . $booking->user->surname,
                    'status' => $booking->status->name
                ];
            });

        return $bookings->toArray();
    }
    public function findAvailableRooms(SearchCriteriaDto $criteria)
    {
        $query = Room::with(['hotel', 'room_classes']);

        if ($criteria->getFilters()) {
            if (isset($criteria->getFilters()['hotel_id'])) {
                $query->where('hotel_id', $criteria->getFilters()['hotel_id']);
            }
            if (isset($criteria->getFilters()['class_id'])) {
                $query->where('class_id', $criteria->getFilters()['class_id']);
            }
            if (isset($criteria->getFilters()['floor'])) {
                $query->where('floor', $criteria->getFilters()['floor']);
            }
        }

        if ($criteria->getDate() && $criteria->getStartTime() && $criteria->getEndTime()) {
            $startDateTime = $criteria->getDate() . ' ' . $criteria->getStartTime();
            $endDateTime = $criteria->getDate() . ' ' . $criteria->getEndTime();

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

        // Пагинация
        return $query->paginate($criteria->getPerPage(), ['*'], 'page', $criteria->getPage());
    }

    public function getCompletedUserBookings(int $userId)
    {
        return BookingRooms::with(['room.hotel', 'room.room_classes'])
            ->where('user_id', $userId)
            ->where('status_id', 4) // Завершено
            ->whereDoesntHave('reviews') // У которых нет отзыва
            ->get();
    }
public function getAllBookings(int $perPage = 20, ?string $status = null, ?int $userId = null)
{
    $query = BookingRooms::with(['room.hotel', 'room.room_classes', 'user', 'status']);
    
    if ($status) {
        $query->whereHas('status', function($q) use ($status) {
            $q->where('name', 'like', "%$status%");
        });
    }
    
    if ($userId) {
        $query->where('user_id', $userId);
    }
    
    return $query->orderBy('created_at', 'desc')->paginate($perPage);
}

public function getBookingById(int $id): ?array
{
    $booking = BookingRooms::with(['room.hotel', 'room.room_classes', 'user', 'status'])
        ->find($id);
    
    if (!$booking) {
        return null;
    }
    
    return [
        'id' => $booking->id,
        'room' => [
            'id' => $booking->room->id,
            'number' => $booking->room->number,
            'floor' => $booking->room->floor,
            'class' => $booking->room->room_classes->name ?? null,
            'price_per_day' => $booking->room->room_classes->price_per_day ?? null,
            'hotel' => [
                'id' => $booking->room->hotel->id,
                'name' => $booking->room->hotel->name,
                'address' => $booking->room->hotel->address
            ]
        ],
        'user' => [
            'id' => $booking->user->id,
            'name' => $booking->user->name,
            'surname' => $booking->user->surname,
            'email' => $booking->user->email,
            'phone' => $booking->user->phone_number
        ],
        'booking_start' => $booking->booking_start,
        'booking_end' => $booking->booking_end,
        'status' => $booking->status->name,
        'status_id' => $booking->status_id,
        'created_at' => $booking->created_at
    ];
}

public function deleteBooking(int $id): bool
{
    $booking = BookingRooms::find($id);
    
    if (!$booking) {
        return false;
    }
    
    // Удаляем связанные отзывы
    $booking->reviews()->delete();
    
    return $booking->delete();
}

public function getSystemStats(): array
{
    return [
        'total_users' => User::count(),
        'total_hotels' => Hotel::count(),
        'total_rooms' => Room::count(),
        'total_bookings' => BookingRooms::count(),
        'active_bookings' => BookingRooms::where('status_id', 1)->count(),
        'completed_bookings' => BookingRooms::where('status_id', 4)->count(),
        'cancelled_bookings' => BookingRooms::whereIn('status_id', [2, 3])->count(),
        'total_reviews' => Reviews::count(),
        'average_rating' => round(Reviews::avg('rating') ?? 0, 1)
    ];
}

public function getBookingStats(?string $startDate = null, ?string $endDate = null): array
{
    $query = BookingRooms::query();
    
    if ($startDate) {
        $query->whereDate('booking_start', '>=', $startDate);
    }
    
    if ($endDate) {
        $query->whereDate('booking_end', '<=', $endDate);
    }
    
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
    
    return $query->sum('room_classes.price_per_day') ?? 0;
}
}