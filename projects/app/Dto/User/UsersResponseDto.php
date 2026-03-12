<?php
    namespace  App\Dto\User;

    use Illuminate\Database\Eloquent\Collection;

    class UsersResponseDto{
        private readonly Collection $users_info;

        function __construct(Collection $users_info){
            $this->users_info = $users_info;
        }
        public function toArray(){
            return 
                $this->users_info;
        }
    }


?>