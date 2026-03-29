<?php
// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Роль администратора в системе
     */
    private const ADMIN_ROLE_ID = 1;
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Проверка наличия токена аутентификации
        if (!$request->user()) {
            return $this->errorResponse(
                'Unauthenticated. Please provide a valid authentication token.',
                'unauthenticated',
                401
            );
        }

        $user = $request->user();
        
        // 2. Проверка существования связи с клиентом
        if (!$user->client) {
            return $this->errorResponse(
                'User account is not fully configured. Client record not found.',
                'client_not_found',
                403
            );
        }
        
        // 3. Проверка наличия роли у клиента
        if (!$user->client->role_id) {
            return $this->errorResponse(
                'User role is not assigned. Please contact system administrator.',
                'role_not_assigned',
                403
            );
        }
        
        // 4. Проверка наличия связи с ролью (опционально)
        if (!$user->client->role) {
            return $this->errorResponse(
                'Role configuration is missing. Please contact system administrator.',
                'role_configuration_missing',
                403
            );
        }
        
        // 5. Проверка прав администратора
        if ($user->client->role_id === self::ADMIN_ROLE_ID) {
            // Добавляем информацию о пользователе в запрос для удобства
            $request->merge([
                'admin_user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                    'login' => $user->client->login,
                    'role' => $user->client->role->name ?? 'admin'
                ]
            ]);
            
            return $next($request);
        }
        
        // 6. Если пользователь аутентифицирован, но не является администратором
        return $this->errorResponse(
            'Access denied. Admin privileges required.',
            'access_denied',
            403,
            [
                'current_role_id' => $user->client->role_id,
                'current_role_name' => $user->client->role->name ?? 'unknown',
                'required_role_id' => self::ADMIN_ROLE_ID,
                'required_role_name' => 'admin'
            ]
        );
    }
    
    /**
     * Формирование стандартизированного ответа с ошибкой
     *
     * @param string $message
     * @param string $code
     * @param int $statusCode
     * @param array $additional
     * @return JsonResponse
     */
    private function errorResponse(
        string $message, 
        string $code, 
        int $statusCode = 403,
        array $additional = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'status_code' => $statusCode
            ]
        ];
        
        if (!empty($additional)) {
            $response['error']['details'] = $additional;
        }
        
        return response()->json($response, $statusCode);
    }
    
}