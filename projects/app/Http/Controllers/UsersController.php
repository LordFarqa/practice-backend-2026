<?php

namespace App\Http\Controllers;

use App\Services\Admin\UsersService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class UsersController extends BaseController
{
    private UsersService $usersService;

    function __construct(UsersService $usersService){
        $this->usersService = $usersService;
    }

    public function show(){
        $users = $this->usersService->getUsers();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'Users not found'
            ],404);
        }

        return response()->json($users);
    }

    public function showUserByLogin(string $login){
        $user = $this->usersService->getUserByLogin($login);

        if(!$user){
            return response()->json([
                'message' => 'User not found'
            ],404);
        }

        return response()->json($user);
    }


    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'=>'required|string',
            'surname'=>'required|string',
            'last_name'=>'nullable|string',
            'email'=>'required|email',
            'phone_number'=>'required|string',

            'login'=>'required|string|unique:clients,login',
            'password'=>'required|min:6'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors'=>$validator->errors()
            ],422);
        }

        $user = $this->usersService->createUser($request->all());

        return response()->json([
            'message'=>'User created',
            'data'=>$user
        ],201);
    }


public function updateUser(Request $request, int $id)
    {
        $user = $this->usersService->findUser($id);

        if(!$user){
            return response()->json([
                'message'=>'User not found'
            ],404);
        }

        $validator = Validator::make($request->all(),[
            'name'=>'sometimes|string',
            'surname'=>'sometimes|string',
            'last_name'=>'sometimes|string',
            'email'=>'sometimes|email',
            'phone_number'=>'sometimes|string',

            'login'=>'sometimes|string|unique:clients,login,'.$user->client->id,
            'password'=>'sometimes|min:6'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors'=>$validator->errors()
            ],422);
        }

        $updatedUser = $this->usersService->updateUser($id,$request->all());

        return response()->json([
            'message'=>'User updated',
            'data'=>$updatedUser
        ]);
    }

    public function deleteUser(int $id){
        $result = $this->usersService->deleteUser($id);

        if(!$result){
            return response()->json([
                'message'=>'User not found'
            ],404);
        }

        return response()->json([
            'message'=>'User deleted'
        ]);
    }
}