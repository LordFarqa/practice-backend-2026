<?php
namespace App\Services\Admin;

use App\Dto\Hotel\CreateHotelDto;
use App\Dto\Room\RoomsResponseDto;
use App\Dto\Hotel\UpdateHotelDto;
use App\Models\Hotel;
use App\Dto\Hotel\HotelResponseDto;
use App\Dto\Hotel\HotelsResponseDto;


class HotelService {
    public function getHotel($id): array
    {
        $hotel = Hotel::with('rooms')->findOrFail($id);

        return $hotel->toArray();
    }

    public function getHotels(): HotelsResponseDto
    {
        $hotels = Hotel::select('id','name','address','class')->get();

        return new HotelsResponseDto($hotels);
    }

    public function createHotel(CreateHotelDto $dto)
    {
        return Hotel::create($dto->toArray());
    }

    public function updateHotel(UpdateHotelDto $dto)
    {
        $hotel = Hotel::findOrFail($dto->getId());

        $hotel->update(array_filter($dto->toArray()));

        return $hotel;
    }

    public function deleteHotel($id)
    {
        $hotel = Hotel::findOrFail($id);

        $hotel->reviews()->delete();
        $hotel->rooms()->delete();
        $hotel->delete();
    }
public function getAllHotels(int $perPage = 20, ?string $search = null)
{
    $query = Hotel::withCount('rooms', 'reviews');
    
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhere('address', 'like', "%$search%");
        });
    }
    
    return $query->orderBy('id', 'desc')->paginate($perPage);
}

public function getHotelWithDetails(int $id): array
{
    $hotel = Hotel::with([
        'rooms' => function($q) {
            $q->with('room_classes');
        },
        'reviews' => function($q) {
            $q->with('booking.user');
        }
    ])->findOrFail($id);
    
    return [
        'id' => $hotel->id,
        'name' => $hotel->name,
        'address' => $hotel->address,
        'class' => $hotel->class,
        'rooms_count' => $hotel->rooms->count(),
        'reviews_count' => $hotel->reviews->count(),
        'average_rating' => round($hotel->reviews->avg('rating') ?? 0, 1),
        'rooms' => $hotel->rooms->map(function($room) {
            return [
                'id' => $room->id,
                'number' => $room->number,
                'floor' => $room->floor,
                'class' => $room->room_classes->name ?? null,
                'price_per_day' => $room->room_classes->price_per_day ?? null
            ];
        }),
        'reviews' => $hotel->reviews->map(function($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->coment,
                'user_name' => $review->booking->user->name . ' ' . $review->booking->user->surname,
                'created_at' => $review->created_at
            ];
        })
    ];
}
}
?>