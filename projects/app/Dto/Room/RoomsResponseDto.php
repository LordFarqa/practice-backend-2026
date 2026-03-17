<?php
    namespace App\Dto\Room;

    use Illuminate\Support\Collection;
    class RoomsResponseDto{

        private readonly Collection $rooms_data;

        function __construct(Collection $rooms_data){
            $this->rooms_data = $rooms_data;

        }
        public function toArray(){
            return $this->rooms_data->toArray();
        }
    }


?>