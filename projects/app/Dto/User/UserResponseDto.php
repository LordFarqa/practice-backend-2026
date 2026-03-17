<?php
    namespace  App\Dto\User;

    use Illuminate\Database\Eloquent\Collection;
    class UserResponseDto{
        private readonly object $user_data;
        function __construct(object $user_data){
            $this->user_data = $user_data;
        }
        public function toArray(){
            return $this->user_data->toArray();

        }
    }


?>