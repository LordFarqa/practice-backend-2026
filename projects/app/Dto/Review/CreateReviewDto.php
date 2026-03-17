<?php
namespace App\Dto\Review;

class CreateReviewDto
{
    private readonly int $hotel_id;
    private readonly int $booking_room_id;
    private readonly string $comment;
    private readonly int $rating;

    public function __construct(array $data)
    {
        $this->hotel_id = $data['hotel_id'];
        $this->booking_room_id = $data['booking_room_id'];
        $this->comment = $data['comment'];
        $this->rating = $data['rating'];
    }

    public function toArray(): array
    {
        return [
            'hotel_id' => $this->hotel_id,
            'booking_room_id' => $this->booking_room_id,
            'comment' => $this->comment,
            'rating' => $this->rating
        ];
    }

    public function getBookingRoomId(): int
    {
        return $this->booking_room_id;
    }
}