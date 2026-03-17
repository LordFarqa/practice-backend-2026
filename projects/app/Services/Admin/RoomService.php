<?php
namespace App\Services\Admin;

use App\Dto\Room\RoomCrearteDto;
use App\Dto\Room\RoomCreateResponseDto;
use App\Dto\Room\RoomUpdateDto;
use App\Models\Room;



class RoomService{
    public function createRoom(RoomCrearteDto $dto){
    $room = Room::create($dto->toArray());

    $room->load('room_classes');

    return new RoomCreateResponseDto([
        'number' => $room->number,
        'hotel_id' => $room->hotel_id,
        'class' => $room->room_classes->name,
        'floor' => $room->floor
    ]);
}

public function updateRoom(RoomUpdateDto $dto){
        $room = Room::findOrFail($dto->getId());

        $room->update(array_filter($dto->toArray()));

        return $room->load('room_classes');
    }
    
}
?>