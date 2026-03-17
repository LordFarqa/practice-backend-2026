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

}
?>