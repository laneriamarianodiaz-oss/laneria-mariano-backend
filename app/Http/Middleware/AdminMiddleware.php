<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || auth()->user()->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Solo administradores.'
            ], 403);
        }

        return $next($request);
    }
}