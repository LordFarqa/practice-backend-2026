<?php

namespace App\Http\Controllers;

use App\Services\Admin\ClientUserService;
use Illuminate\Http\Request;

use Illuminate\Routing\Controller as BaseController;

class AdminController extends BaseController
{
    private ClientUserService $clientUserService;
    
    function __construct(ClientUserService $clientUserService){
        $this->clientUserService = $clientUserService;
    }
    public function show(string $login){
        $user = $this->clientUserService->getUserByLogin($login)->toArray();
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        return response()->json($user);
    }
}
