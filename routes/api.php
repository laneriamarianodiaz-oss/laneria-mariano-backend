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

    // Productos públicos (solo activos)
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
    Route::get('/productos/tipo/{tipo}', [ProductoController::class, 'porTipo']);
    Route::get('/productos-tipos', [ProductoController::class, 'tipos']);
    Route::get('/productos-colores', [ProductoController::class, 'colores']);
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

    // ✅ PRODUCTOS ADMIN - MOVIDO AQUÍ (SOLO REQUIERE AUTH, NO ROL)
    Route::get('/productos/admin', [ProductoController::class, 'indexAdmin']);
    Route::post('/productos', [ProductoController::class, 'store']);
    Route::post('/productos/subir-imagen', [ProductoController::class, 'subirImagen']);
    Route::put('/productos/{id}', [ProductoController::class, 'update']);
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);

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
    // 👑 RUTAS DE ADMINISTRADOR (CON MIDDLEWARE DE ROL)
    // =========================================
    
    Route::middleware('role:administrador,vendedor')->group(function () {
        
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