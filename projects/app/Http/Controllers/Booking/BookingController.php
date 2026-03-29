<?php
// app/Http/Controllers/Booking/BookingController.php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingRooms;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class BookingController extends Controller
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
        ->where('status_id', 1)
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
     * @param Request $request
     * @return JsonResponse
     */
    // app/Http/Controllers/Booking/BookingController.php

// app/Http/Controllers/Booking/BookingController.php

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_start' => 'required|date_format:Y-m-d H:i:s|after:now',
            'booking_end' => 'required|date_format:Y-m-d H:i:s|after:booking_start',
        ]);

        if ($this->checkOverlap((int)$validated['room_id'], $validated['booking_start'], $validated['booking_end'])) {
            return response()->json([
                'errors' => [
                    'time' => ['This room is already booked for the selected time period']
                ]
            ], 422);
        }

        $booking = BookingRooms::create([
            'room_id' => $validated['room_id'],
            'booking_start' => $validated['booking_start'],
            'booking_end' => $validated['booking_end'],
            'user_id' => Auth::id(),
            'status_id' => 1
        ]);

        $booking->load(['room.hotel', 'room.room_classes']);

        return response()->json([
            'id' => $booking->id,
            'room_id' => $booking->room_id,
            'room_number' => $booking->room->number ?? '',
            'hotel_id' => $booking->room->hotel_id ?? null,
            'hotel_name' => $booking->room->hotel->name ?? '',
            'room_class' => $booking->room->room_classes->name ?? null,
            'booking_start' => $booking->booking_start,
            'booking_end' => $booking->booking_end,
            'status' => $booking->status,
            'status_id' => $booking->status_id,
            'created_at' => $booking->created_at
        ], 201);
    }
    public function myBookings(Request $request): JsonResponse
    {
        $perPage = (int)$request->get('per_page', 15);
        $page = (int)$request->get('page', 1);
        $status = $request->get('status');

        $query = BookingRooms::with(['room.hotel', 'room.room_classes'])
            ->where('user_id', Auth::id());

        if ($status && is_string($status)) {
            $query->where('status', 'like', "%$status%");
        }

        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $bookings */
        $bookings = $query->orderBy('booking_start', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedBookings = collect($bookings->items())->map(function ($booking) {
            $room = $booking->room;
            $hotel = $room ? $room->hotel : null;
            $roomClasses = $room ? $room->room_classes : null;

            return [
                'id' => $booking->id,
                'room_id' => $booking->room_id,
                'room_number' => $room ? (string)$room->number : '',
                'hotel_id' => $room ? $room->hotel_id : null,
                'hotel_name' => $hotel ? ($hotel->name ?? '') : '',
                'hotel_address' => $hotel ? ($hotel->address ?? '') : '',
                'room_class' => $roomClasses ? ($roomClasses->name ?? null) : null,
                'price_per_day' => $roomClasses ? ($roomClasses->price_per_day ?? null) : null,
                'booking_start' => $booking->booking_start,
                'booking_end' => $booking->booking_end,
                'status' => $booking->status,
                'status_id' => $booking->status_id,
                'created_at' => $booking->created_at
            ];
        });

        return response()->json([
            'data' => $formattedBookings,
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'next_page_url' => $bookings->nextPageUrl(),
                'prev_page_url' => $bookings->previousPageUrl(),
                'from' => $bookings->firstItem(),
                'to' => $bookings->lastItem()
            ]
        ]);
    }

    /**
     * Отмена бронирования пользователем
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancelByUser(int $id): JsonResponse
    {
        /** @var BookingRooms|null $booking */
        $booking = BookingRooms::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status_id', 1)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found or already cancelled'
            ], 404);
        }

        $booking->status_id = 3; // Отменено пользователем
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking_id' => $booking->id,
            'status' => 'cancelled_by_user'
        ]);
    }

    /**
     * Отмена бронирования администратором
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancelByAdmin(int $id): JsonResponse
    {
        /** @var BookingRooms|null $booking */
        $booking = BookingRooms::where('id', $id)
            ->where('status_id', 1)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found or already cancelled'
            ], 404);
        }

        $booking->status_id = 2; // Отменено администратором
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled by admin successfully',
            'booking_id' => $booking->id,
            'status' => 'cancelled_by_admin'
        ]);
    }

    /**
     * Получение завершенных бронирований без отзывов
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompletedBookings(Request $request): JsonResponse
{
    $perPage = (int)$request->get('per_page', 15);

    $bookings = BookingRooms::with(['room.hotel', 'room.room_classes'])
        ->where('user_id', Auth::id())
        ->where('status_id', 4)
        ->whereDoesntHave('reviews', function($query) {
            // Убеждаемся, что связь работает
            $query->whereNotNull('booking_room_id');
        })
        ->orderBy('booking_start', 'desc')
        ->paginate($perPage);

    $formattedData = collect($bookings->items())->map(function ($booking) {
        $room = $booking->room;
        $hotel = $room ? $room->hotel : null;
        $roomClasses = $room ? $room->room_classes : null;

        return [
            'id' => $booking->id,
            'booking_id' => $booking->id,
            'hotel_id' => $room ? $room->hotel_id : null,
            'hotel_name' => $hotel ? ($hotel->name ?? '') : '',
            'room_id' => $booking->room_id,
            'room_number' => $room ? (string)$room->number : '',
            'room_class' => $roomClasses ? ($roomClasses->name ?? null) : null,
            'booking_start' => $booking->booking_start,
            'booking_end' => $booking->booking_end
        ];
    });

    return response()->json([
        'data' => $formattedData,
        'pagination' => [
            'current_page' => $bookings->currentPage(),
            'last_page' => $bookings->lastPage(),
            'per_page' => $bookings->perPage(),
            'total' => $bookings->total(),
            'next_page_url' => $bookings->nextPageUrl(),
            'prev_page_url' => $bookings->previousPageUrl()
        ]
    ]);
}
    /**
     * Поиск доступных номеров
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchAvailable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'filters' => 'sometimes|array',
            'filters.hotel_id' => 'sometimes|exists:hotels,id',
            'filters.class_id' => 'sometimes|exists:room_classes,id',
            'filters.floor' => 'sometimes|integer|min:1',
            'filters.min_price' => 'sometimes|numeric|min:0',
            'filters.max_price' => 'sometimes|numeric|min:0',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'sort_by' => 'sometimes|in:price,floor,number',
            'sort_direction' => 'sometimes|in:asc,desc'
        ]);

        $startDateTime = $validated['date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['date'] . ' ' . $validated['end_time'];

        $query = Room::with(['hotel', 'room_classes']);

        // Применение фильтров
        $filters = $validated['filters'] ?? [];
        
        if (isset($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }
        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (isset($filters['floor'])) {
            $query->where('floor', $filters['floor']);
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

        // Получение занятых номеров
        $bookedRoomIds = BookingRooms::whereIn('status_id', [1, 4])
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

        // Сортировка
        $sortBy = $validated['sort_by'] ?? null;
        $sortDirection = $validated['sort_direction'] ?? 'asc';
        
        if ($sortBy === 'price') {
            $query->join('room_classes', 'rooms.class_id', '=', 'room_classes.id')
                ->orderBy('room_classes.price_per_day', $sortDirection)
                ->select('rooms.*');
        } elseif ($sortBy && in_array($sortBy, ['floor', 'number'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = (int)($validated['per_page'] ?? 15);
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $rooms */
        $rooms = $query->paginate($perPage);

        $formattedRooms = collect($rooms->items())->map(function ($room) {
            $hotel = $room->hotel;
            $roomClasses = $room->room_classes;

            return [
                'id' => $room->id,
                'number' => (string)$room->number,
                'floor' => $room->floor,
                'hotel_id' => $room->hotel_id,
                'hotel_name' => $hotel ? ($hotel->name ?? '') : '',
                'hotel_address' => $hotel ? ($hotel->address ?? '') : '',
                'class_id' => $room->class_id,
                'class_name' => $roomClasses ? ($roomClasses->name ?? null) : null,
                'price_per_day' => $roomClasses ? ($roomClasses->price_per_day ?? null) : null
            ];
        });

        return response()->json([
            'data' => $formattedRooms,
            'filters' => $filters,
            'date' => $validated['date'],
            'time_range' => [
                'start' => $validated['start_time'],
                'end' => $validated['end_time']
            ],
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'next_page_url' => $rooms->nextPageUrl(),
                'prev_page_url' => $rooms->previousPageUrl(),
                'from' => $rooms->firstItem(),
                'to' => $rooms->lastItem()
            ]
        ]);
    }

    /**
     * Расписание номера
     *
     * @param int $roomId
     * @param Request $request
     * @return JsonResponse
     */
    public function roomSchedule($roomId, Request $request): JsonResponse
{
    $validated = $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $bookings = BookingRooms::with(['user'])
        ->where('room_id', $roomId)
        ->whereBetween('booking_start', [$validated['start_date'], $validated['end_date']])
        ->orderBy('booking_start')
        ->get()
        ->map(function ($booking) {
            $user = $booking->user;
            $userName = $user ? ($user->name . ' ' . $user->surname) : 'Unknown User';
            
            return [
                'id' => $booking->id,
                'start' => $booking->booking_start,
                'end' => $booking->booking_end,
                'user_name' => $userName,
                'user_id' => $booking->user_id,
                'status' => $booking->status, // Исправлено: используем аксессор
                'status_id' => $booking->status_id
            ];
        });

    return response()->json($bookings);
}

    /**
     * Создание завершенного бронирования (только для тестов)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCompletedBooking(Request $request): JsonResponse
    {
        // Только для администраторов или в тестовой среде
        if (app()->environment('production')) {
            return response()->json(['error' => 'Not available in production'], 403);
        }

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_start' => 'required|date_format:Y-m-d H:i:s',
            'booking_end' => 'required|date_format:Y-m-d H:i:s|after:booking_start',
        ]);

        /** @var BookingRooms $booking */
        $booking = BookingRooms::create([
            'room_id' => $validated['room_id'],
            'booking_start' => $validated['booking_start'],
            'booking_end' => $validated['booking_end'],
            'user_id' => Auth::id(),
            'status_id' => 4 // Сразу завершенное
        ]);

        return response()->json($booking, 201);
    }
}