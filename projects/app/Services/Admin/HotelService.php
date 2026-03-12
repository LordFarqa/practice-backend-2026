<?php
namespace App\Services\Admin;

use App\Dto\Hotel\RoomsResponseDto;
use App\Models\Hotel;
use App\Dto\Hotel\HotelResponseDto;
use App\Dto\Hotel\HotelsResponseDto;

use App\Models\Room;
class HotelService {
    public function getHotel(string $hotel_name): ?HotelResponseDto
    {
        $hotel = Hotel::where('name','=',$hotel_name)->get()->map(function($hotel){
            $hotel->adress =json_decode($hotel->adress,true);
            return $hotel;
        });
        return new HotelResponseDto($hotel);
    }

    public function getHotels(): ?HotelsResponseDto
    {
        $hotels_data = Hotel::select('name','adress','class')->get()->map(function($hotel){
            $hotel->adress = json_decode($hotel->adress,true);
            return $hotel;
        });
        return new HotelsResponseDto($hotels_data);
    }

    public function getRooms($hotel_name):?RoomsResponseDto{
        $hotel_id = Hotel::where('name','=',$hotel_name)->value('id');

        $rooms_data = Room::where('hotel_id','=',$hotel_id)->get();

        return new RoomsResponseDto($rooms_data);

    }

}
?>