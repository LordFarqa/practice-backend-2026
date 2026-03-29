<?php
// app/Http/Controllers/Room/RoomController.php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Services\Admin\RoomService;
use App\Dto\Room\RoomCreateDto;
use App\Dto\Room\RoomUpdateDto;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class RoomController extends Controller
{
    public function __construct(private RoomService $roomService) {}

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $hotelId = $request->get('hotel_id');
        $classId = $request->get('class_id');
        
        $rooms = $this->roomService->getAllRooms((int)$perPage, $hotelId ? (int)$hotelId : null, $classId ? (int)$classId : null);
        
        return response()->json($rooms);
    }

    public function show(int $id)
    {
        $room = $this->roomService->getRoomById($id);
        
        if ($room === null) {
            return response()->json(['message' => 'Room not found'], 404);
        }
        
        return response()->json($room);
    }

    public function store(Request $request, int $hotelId)
    {
        $validated = $request->validate([
            'number' => 'required|integer|min:1',
            'class_id' => 'required|exists:room_classes,id',
            'floor' => 'required|integer|min:1'
        ]);

        $dto = new RoomCreateDto($hotelId, $validated);
        
        try {
            $room = $this->roomService->createRoom($dto);
            return response()->json($room->toArray(), 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'number' => 'sometimes|integer|min:1',
            'hotel_id' => 'sometimes|exists:hotels,id',
            'class_id' => 'sometimes|exists:room_classes,id',
            'floor' => 'sometimes|integer|min:1'
        ]);

        $dto = new RoomUpdateDto($id, $validated);
        
        try {
            $room = $this->roomService->updateRoom($dto);
            return response()->json($room);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Room not found'], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id)
    {
        $result = $this->roomService->deleteRoom($id);
        
        if (!$result) {
            return response()->json(['message' => 'Room not found or cannot be deleted'], 404);
        }
        
        return response()->json(['message' => 'Room deleted successfully']);
    }
}