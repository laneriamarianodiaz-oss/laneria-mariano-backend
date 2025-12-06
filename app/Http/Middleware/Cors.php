<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtener los orígenes permitidos desde el .env
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', '*'));
        
        $origin = $request->header('Origin');
        
        // Si el origen está en la lista permitida o es *, permitirlo
        if (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin' => $origin ?: '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ];
        } else {
            $headers = [];
        }

        // Para peticiones OPTIONS (preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200, $headers);
        }

        $response = $next($request);

        // Agregar headers a la respuesta
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}