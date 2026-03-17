<?php
    namespace App\Dto\Room;

    use Illuminate\Support\Collection;
    class RoomUpdateDto{

        private readonly int $number;
        private readonly int $hotel_id;
        private readonly int $class_id;
        private readonly int $floor;
        private readonly int $id;

        function __construct(int $id,array $room_data){
            $this->id = $id;
            $this->number = $room_data['number'];
            $this->hotel_id = $room_data['hotel_id'];
            $this->class_id = $room_data['class_id'];
            $this->floor = $room_data['floor'];

        }
        public function toArray(){
            return [
                'number'=> $this->number,
                'hotel_id'=> $this->hotel_id,
                'class_id'=> $this->class_id,
                'floor'=> $this->floor 
            ];
        }
        public function getId(){
            return $this->id;
        }
    }


?>