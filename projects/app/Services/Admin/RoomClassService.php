<?php
// app/Services/Admin/RoomClassService.php

namespace App\Services\Admin;

use App\Models\RoomClasses;
use App\Dto\Room\RoomClassCreateDto;
use App\Dto\Room\RoomClassUpdateDto;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoomClassService
{
    public function getAllRoomClasses(int $perPage = 20)
    {
        return RoomClasses::paginate($perPage);
    }

    public function getRoomClassById(int $id): ?RoomClasses
    {
        try {
            return RoomClasses::withCount('rooms')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    public function createRoomClass(RoomClassCreateDto $dto): RoomClasses
    {
        return RoomClasses::create($dto->toArray());
    }

    public function updateRoomClass(RoomClassUpdateDto $dto): ?RoomClasses
    {
        $class = RoomClasses::find($dto->getId());
        
        if (!$class) {
            return null;
        }
        
        $class->update($dto->toArray());
        
        return $class;
    }

    public function deleteRoomClass(int $id): bool
    {
        $class = RoomClasses::withCount('rooms')->find($id);
        
        if (!$class || $class->rooms_count > 0) {
            return false;
        }
        
        return $class->delete();
    }
}