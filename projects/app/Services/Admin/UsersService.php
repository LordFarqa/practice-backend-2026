<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\ValidationException;
class UsersService {

    public function getUsers()
    {
        return User::with('client')
        ->get()
        ->map(function($user){
            return [
                'id'=>$user->id,
                'name'=>$user->name,
                'surname'=>$user->surname,
                'last_name'=>$user->last_name,
                'email'=>$user->email,
                'phone_number'=>$user->phone_number,
                'login'=>$user->client->login
            ];
        });
    }

    public function getUserByLogin($login)
    {
        $user = User::whereHas('client',function($query) use ($login){
            $query->where('login',$login);
        })->with('client')->first();

        if(!$user){
            return null;
        }

        return [
            'id'=>$user->id,
            'name'=>$user->name,
            'surname'=>$user->surname,
            'last_name'=>$user->last_name,
            'email'=>$user->email,
            'phone_number'=>$user->phone_number,
            'login'=>$user->client->login
        ];
    }



    public function createUser(array $data)
    {
        if(Client::where('login',$data['login'])->exists()){
            throw ValidationException::withMessages([
                'login'=>'Login already exists'
            ]);
        }

        $user = User::create([
            'name'=>$data['name'],
            'surname'=>$data['surname'],
            'last_name'=>$data['last_name'],
            'email'=>$data['email'],
            'phone_number'=>$data['phone_number']
        ]);

        Client::create([
            'user_id'=>$user->id,
            'login'=>$data['login'],
            'password'=>Hash::make($data['password']),
            'role_id'=>$data['role_id'] ?? 2
        ]);

        return $this->getUserByLogin($data['login']);
    }

    public function updateUser(int $id,array $data)
    {
        $user = User::with('client')->find($id);

        if(!$user){
            return null;
        }

        $user->update([
            'name'=>$data['name'] ?? $user->name,
            'surname'=>$data['surname'] ?? $user->surname,
            'last_name'=>$data['last_name'] ?? $user->last_name,
            'email'=>$data['email'] ?? $user->email,
            'phone_number'=>$data['phone_number'] ?? $user->phone_number
        ]);

        if(isset($data['login'])){
            $user->client->login = $data['login'];
        }

        if(isset($data['password'])){
            $user->client->password = Hash::make($data['password']);
        }

        $user->client->save();

        return $this->getUserByLogin($user->client->login);
    }

    public function deleteUser(int $id)
    {
        $user = User::with('client')->find($id);

        if(!$user){
            return false;
        }

        $user->client()->delete();
        $user->delete();

        return true;
    }
    public function findUser($id){
        return User::with('client')->find($id);
    }
}