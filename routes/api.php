<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\ClienteController;

// =========================================
// 🔓 RUTAS PÚBLICAS (SIN AUTENTICACIÓN)
// =========================================

Route::prefix('v1')->group(function () {
    
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Productos (público)
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
});

// =========================================
// 🔐 RUTAS PROTEGIDAS (REQUIEREN AUTH)
// =========================================

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/mi-perfil', [AuthController::class, 'miPerfil']);
    });

    // Carrito
    Route::prefix('carrito')->group(function () {
        Route::get('/', [CarritoController::class, 'miCarrito']);
        Route::post('/agregar', [CarritoController::class, 'agregarProducto']);
        Route::put('/actualizar/{detalleId}', [CarritoController::class, 'actualizarCantidad']);
        Route::delete('/eliminar/{detalleId}', [CarritoController::class, 'eliminarProducto']);
    });

    // Ventas (cliente)
    Route::prefix('ventas')->group(function () {
        Route::post('/crear', [VentaController::class, 'crearVenta']);
        Route::get('/mis-ventas', [VentaController::class, 'misVentas']);
    });

    // =========================================
    // 👑 RUTAS DE ADMINISTRADOR
    // =========================================
    
    Route::middleware('role:administrador,vendedor')->group(function () {
        
        // Productos (admin)
        Route::prefix('productos')->group(function () {
            Route::post('/', [ProductoController::class, 'store']);
            Route::put('/{id}', [ProductoController::class, 'update']);
            Route::delete('/{id}', [ProductoController::class, 'destroy']);
        });

        // Ventas (admin)
        Route::prefix('ventas')->group(function () {
            Route::get('/', [VentaController::class, 'index']);
            Route::get('/{id}', [VentaController::class, 'show']);
            Route::put('/{id}/estado', [VentaController::class, 'actualizarEstado']);
        });

        // Clientes (admin)
        Route::prefix('clientes')->group(function () {
            Route::get('/', [ClienteController::class, 'index']);
            Route::get('/{id}', [ClienteController::class, 'show']);
        });
    });
});