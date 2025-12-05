<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ✅ RUTA LOGIN PARA EVITAR ERROR (retorna JSON)
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'error' => 'No autenticado',
        'message' => 'Esta es una API. Use /api/v1/auth/login para autenticarse'
    ], 401);
})->name('login');
