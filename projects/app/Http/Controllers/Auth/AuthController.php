<?php
// app/Http/Controllers/Auth/AuthController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Http\JsonResponse;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->headers->set('Accept', 'application/json');
            
            $validated = $request->validate([
                'login' => 'required|string',
                'password' => 'required|string',
            ]);

            // Ищем клиента по логину
            $client = Client::where('login', $validated['login'])->first();

            if (!$client || !Hash::check($validated['password'], $client->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'login' => ['Invalid login or password']
                    ]
                ], 401);
            }

            // Находим пользователя
            $user = User::find($client->user_id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found'
                ], 404);
            }
            
            // Удаляем старые токены (если метод exists)
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            
            // Создаем новый токен
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'login' => $client->login,
                    'role_id' => $client->role_id
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone_number' => 'required|string|max:20|unique:users',
                'login' => 'required|string|max:255|unique:clients',
                'password' => 'required|string|min:6|confirmed',
            ]);

            // Создаем пользователя (без пароля)
            $user = User::create([
                'name' => $validated['name'],
                'surname' => $validated['surname'],
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number']
            ]);

            // Создаем клиента с паролем
            $client = Client::create([
                'user_id' => $user->id,
                'login' => $validated['login'],
                'password' => Hash::make($validated['password']),
                'role_id' => 2
            ]);

            // Создаем токен
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'login' => $client->login
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $client = $user->client;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'login' => $client->login,
            'role_id' => $client->role_id
        ]);
    }

    public function logout(Request $request): JsonResponse
{
    try {
        $user = $request->user();
        
        if ($user && method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Logout failed',
            'error' => $e->getMessage()
        ], 500);
    }
}
}