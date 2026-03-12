<?php
    namespace App\Dto\Hotel;

    use Illuminate\Database\Eloquent\Collection;
    class HotelResponseDto{
        // private readonly string $hotel_name;
        // private readonly string $class;
        // private readonly array $adress;
        private readonly Collection $hotel_data;

        function __construct(Collection $hotel_data){
            $this->hotel_data = $hotel_data;
        }
        public function toArray(){
            return $this->hotel_data;
        }
    }


?>