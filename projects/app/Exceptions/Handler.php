<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        // Для всех API запросов возвращаем JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            
            // Обработка ошибок валидации
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $exception->errors()
                ], 422);
            }
            
            // Обработка неаутентифицированных запросов
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Please provide a valid authentication token'
                    ]
                ], 401);
            }
            
            // Обработка всех остальных ошибок
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => method_exists($exception, 'getCode') ? $exception->getCode() : 500,
                    'type' => class_basename($exception)
                ]
            ], method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500);
        }
        
        return parent::render($request, $exception);
    }
}