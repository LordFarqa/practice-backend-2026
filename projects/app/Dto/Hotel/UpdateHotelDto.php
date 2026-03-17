<?php
    namespace App\Dto\Hotel;



;
    class UpdateHotelDto{

        private readonly string $name;
        private readonly array $address;
        private readonly string $class;
        private readonly int $id;

        function __construct($id,array $hotel_data){
            $this->id = $id;
            $this->name = $hotel_data['name'];
            $this->address = $hotel_data['address'];
            $this->class = $hotel_data['class'];
        }
        public function toArray(){
            return [
                'name'=>$this->name,
                'address'=>$this->address,
                'class'=>$this->class
            ];
        }
        public function getId(){
            return $this->id;
        }
    }


?>