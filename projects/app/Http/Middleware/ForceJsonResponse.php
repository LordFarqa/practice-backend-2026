<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
{
    $request->headers->set('Accept', 'application/json');
    $response = $next($request);
    
    if (!$response->headers->get('Content-Type') || 
        strpos($response->headers->get('Content-Type'), 'application/json') === false) {
        
        if (method_exists($response, 'getContent') && 
            !empty($response->getContent()) && 
            strpos($response->getContent(), '<!DOCTYPE') !== false) {
            
            $statusCode = method_exists($response, 'getStatusCode') 
                ? $response->getStatusCode() 
                : 500;
            
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Invalid request format',
                    'code' => 'invalid_response'
                ]
            ], $statusCode);
        }
        
        $response->headers->set('Content-Type', 'application/json');
    }
    
    return $response;
}
}