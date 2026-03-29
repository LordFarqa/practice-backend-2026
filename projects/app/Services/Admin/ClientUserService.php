<?php


namespace App\Services\Admin;

use App\Dto\User\UserResponseDto;
use App\Models\User;

class ClientUserService
{
    public function getUser(int $id): UserResponseDto
    {
        $user = User::with('client')->findOrFail($id);
        
        return new UserResponseDto([
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'login' => $user->client->login
        ]);
    }
}
?>
