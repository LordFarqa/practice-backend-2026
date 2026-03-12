<?php

namespace App\Http\Controllers\Hotel;


use App\Services\Admin\HotelService;

use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class HotelsController extends BaseController
{

    private HotelService $hotelService;
    
    function __construct(HotelService $hotelService){
        $this->hotelService = $hotelService;
    }
    public function show(): JsonResponse{

        $hotels = $this->hotelService->getHotels()->toArray();
        if (!$hotels) {
            return response()->json([
                'message' => 'Bad route'
            ], 404);
        }
        return response()->json($hotels);
    }
    public function showByName(string $hotel_name): JsonResponse{

        $hotel = $this->hotelService->getHotel($hotel_name)->toArray();
        if (!$hotel) {
            return response()->json([
                'message' => 'Bad route'
            ], 404);
        }
        return response()->json($hotel);
    }
    public function showRoomsByHotelName($hotel_name){
        $hotel = $this->hotelService->getRooms($hotel_name)->toArray();
        if (!$hotel) {
            return response()->json([
                'message' => 'Bad route'
            ], 404);
        }
        return response()->json($hotel);
    }
}
