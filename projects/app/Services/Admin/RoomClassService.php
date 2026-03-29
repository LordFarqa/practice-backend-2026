<?php
// app/Services/Admin/RoomClassService.php

namespace App\Services\Admin;

use App\Models\RoomClasses;
use App\Dto\Room\RoomClassCreateDto;
use App\Dto\Room\RoomClassUpdateDto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoomClassService
{
    /**
     * Получить список всех классов номеров с пагинацией
     * 
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllRoomClasses(int $perPage = 20): LengthAwarePaginator
    {
        return RoomClasses::withCount('rooms')->paginate($perPage);
    }

    /**
     * Получить класс номера по ID с количеством комнат
     * 
     * @param int $id
     * @return RoomClasses|null
     */
    public function getRoomClassById(int $id): ?RoomClasses
    {
        try {
            /** @var RoomClasses $roomClass */
            $roomClass = RoomClasses::withCount('rooms')->findOrFail($id);
            return $roomClass;
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * Создать новый класс номера
     * 
     * @param RoomClassCreateDto $dto
     * @return RoomClasses
     */
    public function createRoomClass(RoomClassCreateDto $dto): RoomClasses
    {
        /** @var RoomClasses $roomClass */
        $roomClass = RoomClasses::create($dto->toArray());
        return $roomClass;
    }

    /**
     * Обновить класс номера
     * 
     * @param RoomClassUpdateDto $dto
     * @return RoomClasses|null
     */
    public function updateRoomClass(RoomClassUpdateDto $dto): ?RoomClasses
    {
        /** @var RoomClasses|null $class */
        $class = RoomClasses::find($dto->getId());
        
        if (!$class) {
            return null;
        }
        
        $class->update($dto->toArray());
        
        // Обновляем объект с актуальными данными
        /** @var RoomClasses $updatedClass */
        $updatedClass = $class->fresh();
        
        return $updatedClass;
    }

    /**
     * Удалить класс номера
     * 
     * @param int $id
     * @return bool
     */
    public function deleteRoomClass(int $id): bool
    {
        /** @var RoomClasses|null $class */
        $class = RoomClasses::withCount('rooms')->find($id);
        
        // Проверяем существование класса и наличие связанных номеров
        if (!$class || $class->rooms_count > 0) {
            return false;
        }
        
        /** @var bool $result */
        $result = $class->delete();
        
        return $result;
    }

    /**
     * Получить все классы номеров без пагинации (для выпадающих списков)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllRoomClassesList(): \Illuminate\Database\Eloquent\Collection
    {
        return RoomClasses::withCount('rooms')->get();
    }

    /**
     * Проверить, есть ли у класса номера
     * 
     * @param int $id
     * @return bool
     */
    public function hasRooms(int $id): bool
    {
        /** @var RoomClasses|null $class */
        $class = RoomClasses::withCount('rooms')->find($id);
        
        if (!$class) {
            return false;
        }
        
        return $class->rooms_count > 0;
    }
}