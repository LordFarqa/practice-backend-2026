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



public function getReviewComment(int $reviewId): ?string
{
    try {
        /** @var Reviews|null $review */
        $review = Reviews::find($reviewId);
        
        if (!$review) {
            return null;
        }
        
        // Используем поле 'coment' из БД
        return $review->coment ?? null;
    } catch (\Exception $e) {
        return null;
    }
}

public function formatReview(Reviews $review): array
{
    return [
        'id' => $review->id,
        'comment' => $review->coment ?? '', // Исправлено
        'rating' => $review->rating ?? 0,
        'hotel_id' => $review->hotel_id,
        'user_id' => $review->user_id,
        'booking_id' => $review->booking_room_id, // Исправлено
        'created_at' => $review->created_at?->format('Y-m-d H:i:s')
    ];
}
}
?>