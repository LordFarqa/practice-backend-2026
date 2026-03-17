<?php
    namespace App\Dto\Hotel;

    use Illuminate\Database\Eloquent\Collection;
    class HotelsResponseDto{
        private readonly Collection $hotels_data;


        function __construct(Collection $hotels_data){
            $this->hotels_data = $hotels_data;
        }
        public function toArray(){
            return $this->hotels_data->toArray();
        }
    }


?>