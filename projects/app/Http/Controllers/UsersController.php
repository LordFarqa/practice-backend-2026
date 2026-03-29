<?php
// app/Http/Controllers/UsersController.php

namespace App\Http\Controllers;

use App\Services\Admin\UsersService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UsersController extends Controller
{
    protected UsersService $usersService;
    
    public function __construct(UsersService $usersService)
    {
        $this->usersService = $usersService;
    }
    
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        
        $users = $this->usersService->getAllUsers((int) $perPage, $search);
        
        // Исправлено: проверка на пустую коллекцию, а не массив
        if ($users->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No users found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $users,
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage()
            ]
        ]);
    }
}