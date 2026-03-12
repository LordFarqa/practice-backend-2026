<?php
namespace App\Services\Admin;

use App\Models\User;
use App\Dto\User\UsersResponseDto;

class UsersService {
    public function getUsers(): UsersResponseDto
    {
        $clients = User::with('client')->get()->map(function($user){
            $user->login = $user->client->login;
            unset($user->client);
            return $user;
        });
        return new UsersResponseDto($clients);
    }
}
?>