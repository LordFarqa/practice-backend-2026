<?php
// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Проверяем, авторизован ли пользователь
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Проверяем, является ли пользователь администратором (role_id = 1)
        // Предполагается, что у пользователя есть связь с client
        if ($request->user()->client && $request->user()->client->role_id == 1) {
            return $next($request);
        }

        // Если не администратор, возвращаем ошибку
        return response()->json([
            'error' => 'Unauthorized. Admin access required.',
            'user_role' => $request->user()->client->role_id ?? 'unknown'
        ], 403);
    }
}