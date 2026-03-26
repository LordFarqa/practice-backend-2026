<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\HotelService;
use App\Services\Admin\RoomService;
use App\Services\Admin\RoomClassService;
use App\Services\Admin\UsersService;
use App\Services\Booking\BookingService;
use App\Dto\Hotel\CreateHotelDto;
use App\Dto\Hotel\UpdateHotelDto;

use App\Dto\Room\RoomUpdateDto;
use App\Dto\Room\RoomClassCreateDto;
use App\Dto\Room\RoomClassUpdateDto;
use App\Dto\User\UserCreateDto;
use App\Dto\User\UserUpdateDto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    private HotelService $hotelService;
    private RoomService $roomService;
    private RoomClassService $roomClassService;
    private UsersService $usersService;
    private BookingService $bookingService;

    public function __construct(
        HotelService $hotelService,
        RoomService $roomService,
        RoomClassService $roomClassService,
        UsersService $usersService,
        BookingService $bookingService
    ) {
        $this->hotelService = $hotelService;
        $this->roomService = $roomService;
        $this->roomClassService = $roomClassService;
        $this->usersService = $usersService;
        $this->bookingService = $bookingService;
    }

    // ==================== УПРАВЛЕНИЕ БРОНИРОВАНИЯМИ ====================

    public function getAllBookings(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');
        $userId = $request->get('user_id');
        
        $bookings = $this->bookingService->getAllBookings($perPage, $status, $userId);
        
        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    public function getBookingById($id)
    {
        $booking = $this->bookingService->getBookingById($id);
        
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    public function deleteBooking($id)
    {
        $result = $this->bookingService->deleteBooking($id);
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    }

    // ==================== УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ ====================

    public function getAllUsers(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        
        $users = $this->usersService->getAllUsers($perPage, $search);
        
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function getUserById($id)
    {
        $user = $this->usersService->getUserById($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'login' => 'required|string|max:255|unique:clients,login',
            'password' => 'required|string|min:6',
            'role_id' => 'sometimes|integer|exists:roles,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $this->usersService->createUser($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone_number' => 'sometimes|string|max:20',
            'login' => 'sometimes|string|max:255|unique:clients,login,' . $id . ',user_id',
            'password' => 'sometimes|string|min:6',
            'role_id' => 'sometimes|integer|exists:roles,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $this->usersService->updateUser($id, $request->all());
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function deleteUser($id)
    {
        $result = $this->usersService->deleteUser($id);
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    // ==================== УПРАВЛЕНИЕ ОТЕЛЯМИ ====================

    public function getAllHotels(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        
        $hotels = $this->hotelService->getAllHotels($perPage, $search);
        
        return response()->json([
            'success' => true,
            'data' => $hotels
        ]);
    }

    public function getHotelById($id)
    {
        $hotel = $this->hotelService->getHotelWithDetails($id);
        
        return response()->json([
            'success' => true,
            'data' => $hotel
        ]);
    }

    public function createHotel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'class' => 'required|integer|min:1|max:5'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $dto = new CreateHotelDto($request->all());
        $hotel = $this->hotelService->createHotel($dto);
        
        return response()->json([
            'success' => true,
            'message' => 'Hotel created successfully',
            'data' => $hotel
        ], 201);
    }

    public function updateHotel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'class' => 'sometimes|integer|min:1|max:5'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $request->all();
        $data['id'] = $id;
        $dto = new UpdateHotelDto($data);
        
        $hotel = $this->hotelService->updateHotel($dto);
        
        return response()->json([
            'success' => true,
            'message' => 'Hotel updated successfully',
            'data' => $hotel
        ]);
    }

    public function deleteHotel($id)
    {
        $this->hotelService->deleteHotel($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Hotel deleted successfully'
        ]);
    }

    // ==================== УПРАВЛЕНИЕ НОМЕРАМИ ====================

    public function getAllRooms(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $hotelId = $request->get('hotel_id');
        $classId = $request->get('class_id');
        
        $rooms = $this->roomService->getAllRooms($perPage, $hotelId, $classId);
        
        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    public function getRoomById($id)
    {
        $room = $this->roomService->getRoomById($id);
        
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    public function createRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:10',
            'hotel_id' => 'required|exists:hotels,id',
            'class_id' => 'required|exists:room_classes,id',
            'floor' => 'required|integer|min:1'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $dto = new RoomCreateDto($request->all());
        $room = $this->roomService->createRoom($dto);
        
        return response()->json([
            'success' => true,
            'message' => 'Room created successfully',
            'data' => $room
        ], 201);
    }

    public function updateRoom(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'sometimes|string|max:10',
            'hotel_id' => 'sometimes|exists:hotels,id',
            'class_id' => 'sometimes|exists:room_classes,id',
            'floor' => 'sometimes|integer|min:1'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $request->all();
        $data['id'] = $id;
        $dto = new RoomUpdateDto($data);
        
        $room = $this->roomService->updateRoom($dto);
        
        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    public function deleteRoom($id)
    {
        $result = $this->roomService->deleteRoom($id);
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    }

    // ==================== УПРАВЛЕНИЕ КЛАССАМИ НОМЕРОВ ====================

    public function getAllRoomClasses(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        
        $classes = $this->roomClassService->getAllRoomClasses($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    public function getRoomClassById($id)
    {
        $class = $this->roomClassService->getRoomClassById($id);
        
        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Room class not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $class
        ]);
    }

    public function createRoomClass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:room_classes,name',
            'price_per_day' => 'required|numeric|min:0'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $dto = new RoomClassCreateDto($request->all());
        $class = $this->roomClassService->createRoomClass($dto);
        
        return response()->json([
            'success' => true,
            'message' => 'Room class created successfully',
            'data' => $class
        ], 201);
    }

    public function updateRoomClass(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:room_classes,name,' . $id,
            'price_per_day' => 'sometimes|numeric|min:0'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $request->all();
        $data['id'] = $id;
        $dto = new RoomClassUpdateDto($data);
        
        $class = $this->roomClassService->updateRoomClass($dto);
        
        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Room class not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Room class updated successfully',
            'data' => $class
        ]);
    }

    public function deleteRoomClass($id)
    {
        $result = $this->roomClassService->deleteRoomClass($id);
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Room class not found or has associated rooms'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Room class deleted successfully'
        ]);
    }

    // ==================== СТАТИСТИКА ====================

    public function getStats()
    {
        $stats = $this->bookingService->getSystemStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getBookingStats(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $stats = $this->bookingService->getBookingStats($startDate, $endDate);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}