<?php
namespace App\Services\Review;

use App\Models\Reviews;
use App\Models\BookingRooms;
use App\Dto\Review\CreateReviewDto;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function createReview(CreateReviewDto $dto, int $userId): Reviews
    {
        $booking = BookingRooms::with('room')
            ->where('id', $dto->getBookingRoomId())
            ->where('user_id', $userId)
            ->where('status_id', 4) // Завершено
            ->first();

        if (!$booking) {
            throw ValidationException::withMessages([
                'booking' => 'You can only review completed bookings'
            ]);
        }

        $existingReview = Reviews::where('booking_room_id', $dto->getBookingRoomId())->first();
        if ($existingReview) {
            throw ValidationException::withMessages([
                'booking' => 'Review already exists for this booking'
            ]);
        }

        return Reviews::create($dto->toArray());
    }

    public function getHotelReviews(int $hotelId)
    {
        $reviews = Reviews::with(['booking.user'])
            ->where('hotel_id', $hotelId)
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = $reviews->avg('rating');

        return [
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $reviews->count(),
            'reviews' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->booking->user->name . ' ' . $review->booking->user->surname,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->format('Y-m-d H:i:s')
                ];
            })
        ];
    }

    public function updateHotelAverageRating(int $hotelId): void
    {
        $averageRating = Reviews::where('hotel_id', $hotelId)
            ->avg('rating');


    }
}