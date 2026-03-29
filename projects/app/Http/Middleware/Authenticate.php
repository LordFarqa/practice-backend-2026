<?php
// app/Http/Middleware/Authenticate.php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Для API запросов возвращаем null и обрабатываем в render методе Exception Handler
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }
        
        return route('login');
    }
}