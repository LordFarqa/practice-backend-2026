<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelsController extends Controller
{

    public function show(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $hotels = Hotel::select('id', 'name', 'address', 'class')
            ->withCount('rooms')
            ->get();
        
        $formattedHotels = $hotels->map(function ($hotel) {
            $averageRating = $hotel->reviews()->avg('rating') ?? 0;
            $totalReviews = $hotel->reviews()->count();
            
            return [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'address' => $hotel->address,
                'class' => $hotel->class,
                'rooms_count' => $hotel->rooms_count,
                'rating' => [
                    'average' => round($averageRating, 1),
                    'total' => $totalReviews
                ]
            ];
        });
        
        $total = $formattedHotels->count();
        $items = $formattedHotels->forPage($page, $perPage)->values();
        
        return response()->json([
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'next_page_url' => $page < ceil($total / $perPage) 
                    ? url("/api/hotels?page=" . ($page + 1) . "&per_page=$perPage") 
                    : null,
                'prev_page_url' => $page > 1 
                    ? url("/api/hotels?page=" . ($page - 1) . "&per_page=$perPage") 
                    : null
            ]
        ]);
    }


    public function showHotelById($id)
    {
        $hotel = Hotel::with(['rooms' => function($query) {
            $query->with('room_classes');
        }])->withAvg('reviews', 'rating')
          ->withCount('reviews')
          ->findOrFail($id);

        return response()->json([
            'data' => $hotel,
            'average_rating' => round($hotel->reviews_avg_rating ?? 0, 1),
            'total_reviews' => $hotel->reviews_count
        ]);
    }

    public function showRooms($id, Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $hotel = Hotel::findOrFail($id);
        
        $rooms = $hotel->rooms()
            ->with('room_classes')
            ->paginate($perPage, ['*'], 'page', $page);
            
        return response()->json([
            'hotel_id' => $id,
            'hotel_name' => $hotel->name,
            'data' => $rooms->items(),
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'next_page_url' => $rooms->nextPageUrl(),
                'prev_page_url' => $rooms->previousPageUrl()
            ]
        ]);
    }
}