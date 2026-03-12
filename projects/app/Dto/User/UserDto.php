<?php
    namespace  App\Dto\User;
    class UserDto{
        private readonly string $login;


        function __construct($login){
            $this->login = $login;
        }
        public function toArray(){
            return[
                'login'=>$this->login
            ];
        }
    }


?>