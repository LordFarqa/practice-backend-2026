<?php
namespace App\Services\Admin;

use App\Models\User;
use App\Models\Client;
use App\Dto\UsersResponseDto;

class UsersService {
    public function getUsers(): UsersResponseDto
    {
        $users = User::select('id','name','surname','last_name','email')
            ->take(10)
            ->get();

        // Получаем клиентов, связанных с этими пользователями
        $clients = Client::with('user')
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->map(function($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->user->name ?? null,
                    'surname' => $client->user->surname ?? null,
                    'last_name' => $client->user->last_name ?? null,
                    'email' => $client->user->email ?? null,
                    'login' => $client->login ?? null
                ];
            });

        $data = [

            'clientdata' => $clients
        ];

        return new UsersResponseDto($data);
    }
}
?>