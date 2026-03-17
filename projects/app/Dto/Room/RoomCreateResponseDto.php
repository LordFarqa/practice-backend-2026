<?php
    namespace App\Dto\Room;

    use App\Models\Room;
    class RoomCreateResponseDto{

        private readonly int $number;
        private readonly int $hotel_id;
        private readonly string $class;
        private readonly int $floor;

        function __construct(array $room_data){
            $this->number = $room_data['number'];
            $this->hotel_id = $room_data['hotel_id'];
            $this->class = $room_data['class'];
            $this->floor = $room_data['floor'];

        }
        public function toArray(){
            return [
                'number' => $this->number,
                'hotel_id' => $this->hotel_id,
                'class' => $this->class,
                'floor' => $this->floor
            ];
        }
    }


?>