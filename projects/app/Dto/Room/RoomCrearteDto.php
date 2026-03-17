<?php
    namespace App\Dto\Room;

    use Illuminate\Support\Collection;
    class RoomCrearteDto{

        private readonly int $number;
        private readonly int $hotel_id;
        private readonly int $class_id;
        private readonly int $floor;

        function __construct(int $id,array $room_data){
            $this->number = $room_data['number'];
            $this->hotel_id = $id;
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
    }


?>