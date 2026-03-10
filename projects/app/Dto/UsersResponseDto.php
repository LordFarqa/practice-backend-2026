<?php
    namespace App\Dto;

    use Illuminate\Database\Eloquent\Collection;
    class UsersResponseDto{
        private readonly array $users_info;

        function __construct($users_info){
            $this->users_info = $users_info;
        }
        public function toArray(){
            return[
                'users_info' => $this->users_info
            ];
        }
        // public function setUsersInfo(array $var){
        //     $this->users_info
        // }
    }


?>