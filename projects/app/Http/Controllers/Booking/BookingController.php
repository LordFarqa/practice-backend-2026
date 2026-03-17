<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingRooms;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingController extends Controller
{

    private function checkOverlap($room_id, $start, $end, $excludeBookingId = null)
    {
        $query = BookingRooms::where('room_id', $room_id)
            ->whereIn('status_id', [1, 4])
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


    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_start' => 'required|date_format:Y-m-d H:i:s|after:now',
            'booking_end' => 'required|date_format:Y-m-d H:i:s|after:booking_start',
        ]);


        if ($this->checkOverlap($validated['room_id'], $validated['booking_start'], $validated['booking_end'])) {
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
            'status_id' => 1 // Активное
        ]);


        $booking->load(['room.hotel', 'room.room_classes', 'status']);

        return response()->json([
            'id' => $booking->id,
            'room_id' => $booking->room_id,
            'room_number' => $booking->room->number,
            'hotel_id' => $booking->room->hotel_id,
            'hotel_name' => $booking->room->hotel->name,
            'room_class' => $booking->room->room_classes->name ?? null,
            'booking_start' => $booking->booking_start,
            'booking_end' => $booking->booking_end,
            'status' => $booking->status->name,
            'status_id' => $booking->status_id,
            'created_at' => $booking->created_at
        ], 201);
    }

    public function myBookings(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $status = $request->get('status');
        
        $query = BookingRooms::with(['room.hotel', 'room.room_classes', 'status'])
            ->where('user_id', Auth::id());
        
        if ($status) {
            $query->whereHas('status', function($q) use ($status) {
                $q->where('name', 'like', "%$status%");
            });
        }
        
        $bookings = $query->orderBy('booking_start', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        $formattedBookings = collect($bookings->items())->map(function ($booking) {
            return [
                'id' => $booking->id,
                'room_id' => $booking->room_id,
                'room_number' => $booking->room->number,
                'hotel_id' => $booking->room->hotel_id,
                'hotel_name' => $booking->room->hotel->name,
                'hotel_address' => $booking->room->hotel->address,
                'room_class' => $booking->room->room_classes->name ?? null,
                'price_per_day' => $booking->room->room_classes->price_per_day ?? null,
                'booking_start' => $booking->booking_start,
                'booking_end' => $booking->booking_end,
                'status' => $booking->status->name,
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


    public function cancelByUser($id)
    {
        $booking = BookingRooms::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status_id', 1) 
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found or already cancelled'
            ], 404);
        }

        $booking->status_id = 3; 
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking_id' => $booking->id,
            'status' => 'cancelled_by_user'
        ]);
    }

    public function cancelByAdmin($id)
    {
        $booking = BookingRooms::where('id', $id)
            ->where('status_id', 1) 
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found or already cancelled'
            ], 404);
        }

        $booking->status_id = 2; 
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled by admin successfully',
            'booking_id' => $booking->id,
            'status' => 'cancelled_by_admin'
        ]);
    }

    public function getCompletedBookings(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $bookings = BookingRooms::with(['room.hotel', 'room.room_classes'])
            ->where('user_id', Auth::id())
            ->where('status_id', 4)
            ->whereDoesntHave('reviews')
            ->orderBy('booking_start', 'desc')
            ->paginate($perPage);
        
        return response()->json([
            'data' => $bookings->through(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->id,
                    'hotel_id' => $booking->room->hotel_id,
                    'hotel_name' => $booking->room->hotel->name,
                    'room_id' => $booking->room_id,
                    'room_number' => $booking->room->number,
                    'room_class' => $booking->room->room_classes->name ?? null,
                    'booking_start' => $booking->booking_start,
                    'booking_end' => $booking->booking_end
                ];
            }),
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

    public function searchAvailable(Request $request)
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


        if (isset($validated['filters'])) {
            if (isset($validated['filters']['hotel_id'])) {
                $query->where('hotel_id', $validated['filters']['hotel_id']);
            }
            if (isset($validated['filters']['class_id'])) {
                $query->where('class_id', $validated['filters']['class_id']);
            }
            if (isset($validated['filters']['floor'])) {
                $query->where('floor', $validated['filters']['floor']);
            }
            if (isset($validated['filters']['min_price'])) {
                $query->whereHas('room_classes', function($q) use ($validated) {
                    $q->where('price_per_day', '>=', $validated['filters']['min_price']);
                });
            }
            if (isset($validated['filters']['max_price'])) {
                $query->whereHas('room_classes', function($q) use ($validated) {
                    $q->where('price_per_day', '<=', $validated['filters']['max_price']);
                });
            }
        }

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

        if (isset($validated['sort_by'])) {
            if ($validated['sort_by'] === 'price') {
                $query->join('room_classes', 'rooms.class_id', '=', 'room_classes.id')
                      ->orderBy('room_classes.price_per_day', $validated['sort_direction'] ?? 'asc')
                      ->select('rooms.*');
            } else {
                $query->orderBy($validated['sort_by'], $validated['sort_direction'] ?? 'asc');
            }
        }

        $perPage = $validated['per_page'] ?? 15;
        $rooms = $query->paginate($perPage);

        $formattedRooms = collect($rooms->items())->map(function ($room) {
            return [
                'id' => $room->id,
                'number' => $room->number,
                'floor' => $room->floor,
                'hotel_id' => $room->hotel_id,
                'hotel_name' => $room->hotel->name,
                'hotel_address' => $room->hotel->address,
                'class_id' => $room->class_id,
                'class_name' => $room->room_classes->name ?? null,
                'price_per_day' => $room->room_classes->price_per_day ?? null
            ];
        });

        return response()->json([
            'data' => $formattedRooms,
            'filters' => $validated['filters'] ?? [],
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


    public function roomSchedule($roomId, Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $bookings = BookingRooms::with(['user', 'status'])
            ->where('room_id', $roomId)
            ->whereBetween('booking_start', [$validated['start_date'], $validated['end_date']])
            ->orderBy('booking_start')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'start' => $booking->booking_start,
                    'end' => $booking->booking_end,
                    'user_name' => $booking->user->name . ' ' . $booking->user->surname,
                    'user_id' => $booking->user_id,
                    'status' => $booking->status->name,
                    'status_id' => $booking->status_id
                ];
            });

        return response()->json($bookings);
    }
    public function createCompletedBooking(Request $request)
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