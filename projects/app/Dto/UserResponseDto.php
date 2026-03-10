<?php
    namespace App\Dto;
    class UserResponseDto{
        private readonly string $name;
        private readonly string $surname;
        private readonly string $last_name;
        private readonly string $email;
        private readonly string $login;

        function __construct($name,$surname,$last_name,$email,$login){
            $this->name = $name;
            $this->surname = $surname;
            $this->last_name = $last_name;
            $this->email = $email;
            $this->login = $login;
        }
        public function toArray(){
            return[
                'name'=>$this->name,
                'surname'=>$this->surname,
                'last_name'=>$this->last_name,
                'email'=>$this->email,
                'login'=>$this->login
            ];
        }
    }


?>