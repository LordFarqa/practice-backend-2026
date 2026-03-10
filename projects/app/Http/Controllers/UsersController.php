<?php

namespace App\Http\Controllers;

use App\Services\Admin\UsersService;
use Illuminate\Http\Request;

use Illuminate\Routing\Controller as BaseController;

class UsersController extends BaseController
{
    private UsersService $usersService;
    
    function __construct(UsersService $usersService){
        $this->usersService = $usersService;
    }
    public function show(){
        $user = $this->usersService->getUsers()->toArray();
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        return response()->json($user);
    }
}
