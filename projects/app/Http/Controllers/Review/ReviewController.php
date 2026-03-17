<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Models\Reviews;
use App\Models\BookingRooms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'hotel_id' => 'required|exists:hotels,id',
        'booking_room_id' => 'required|exists:booking_rooms,id',
        'coment' => 'required|string',
        'rating' => 'required|integer|min:1|max:5',
    ]);

    // Проверяем, что бронирование принадлежит пользователю и завершено
    $booking = BookingRooms::where('id', $validated['booking_room_id'])
        ->where('user_id', Auth::id())
        ->where('status_id', 4)
        ->first();

    if (!$booking) {
        return response()->json([
            'error' => 'You can only review completed bookings that belong to you'
        ], 422);
    }

    // Проверяем, что отзыв еще не создан
    $existingReview = Reviews::where('booking_room_id', $validated['booking_room_id'])->first();
    if ($existingReview) {
        return response()->json([
            'error' => 'Review already exists for this booking'
        ], 422);
    }

    $review = Reviews::create($validated);

    return response()->json([
        'message' => 'Review created successfully',
        'review' => $review
    ], 201);
}

    public function hotelReviews($hotelId, Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $sort = $request->get('sort', 'newest');
        
        $query = Reviews::with(['booking.user'])
            ->where('hotel_id', $hotelId);
        
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'highest':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
        
        $reviews = $query->paginate($perPage, ['*'], 'page', $page);
        
        $averageRating = Reviews::where('hotel_id', $hotelId)->avg('rating');
        $ratingDistribution = [
            1 => Reviews::where('hotel_id', $hotelId)->where('rating', 1)->count(),
            2 => Reviews::where('hotel_id', $hotelId)->where('rating', 2)->count(),
            3 => Reviews::where('hotel_id', $hotelId)->where('rating', 3)->count(),
            4 => Reviews::where('hotel_id', $hotelId)->where('rating', 4)->count(),
            5 => Reviews::where('hotel_id', $hotelId)->where('rating', 5)->count(),
        ];
        
        $formattedReviews = collect($reviews->items())->map(function ($review) {
            return [
                    'id' => $review->id,
                    'user_name' => $review->booking->user->name . ' ' . $review->booking->user->surname,
                    'rating' => $review->rating,
                    'coment' => $review->coment,  // ИСПОЛЬЗУЙТЕ coment, НЕ comment
                    'created_at' => $review->created_at->format('Y-m-d H:i:s')
                ];
        });
        
        return response()->json([
            'hotel_id' => $hotelId,
            'average_rating' => round($averageRating ?? 0, 1),
            'total_reviews' => Reviews::where('hotel_id', $hotelId)->count(),
            'rating_distribution' => $ratingDistribution,
            'data' => $formattedReviews,
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'next_page_url' => $reviews->nextPageUrl(),
                'prev_page_url' => $reviews->previousPageUrl()
            ]
        ]);
    }
}