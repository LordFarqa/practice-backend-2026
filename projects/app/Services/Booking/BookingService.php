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
}