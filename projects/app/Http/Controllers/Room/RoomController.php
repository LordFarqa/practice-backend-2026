<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\RoomService;
use App\Dto\Room\RoomCrearteDto;
use App\Dto\Room\RoomUpdateDto;

class RoomController extends Controller
{
    public function __construct(private RoomService $roomService){}

    public function createRoom($hotelId, Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|integer|min:1',
            'class_id' => 'required|exists:room_classes,id',
            'floor' => 'required|integer|min:1'
        ]);

        $dto = new RoomCrearteDto($hotelId, $validated);

        $room = $this->roomService->createRoom($dto);

        return response()->json($room->toArray(), 201);
    }

    public function updateRoom($id, Request $request)
    {
        $validated = $request->validate([
            'number' => 'sometimes|integer|min:1',
            'hotel_id' => 'sometimes|exists:hotels,id',
            'class_id' => 'sometimes|exists:room_classes,id',
            'floor' => 'sometimes|integer|min:1'
        ]);

        $dto = new RoomUpdateDto($id, $validated);

        $room = $this->roomService->updateRoom($dto);

        return response()->json($room);
    }

    public function deleteRoom($id)
    {
        $this->roomService->deleteRoom($id);

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }
}