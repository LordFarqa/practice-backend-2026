<?php
namespace App\Services\Admin;
use App\Dto\User\UserResponseDto;
use App\Models\Client;



class ClientUserService{
    public function getUserByLogin(string $login):?UserResponseDto{

        $client = Client::firstWhere('login',$login);

        if(empty($client)){
            return null;
        }
        $user = $client->user;

        return new UserResponseDto(
            $user->name,
            $user->surname,
            $user->last_name,
            $user->email,
            $client->login);
    }
}
?>