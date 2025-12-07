<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CarritoController;

// ============================================
// 🔐 AUTENTICACIÓN (PÚBLICAS)
// ============================================
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ============================================
// 📦 RUTAS PÚBLICAS
// ============================================
Route::prefix('v1')->group(function () {
    
    // ✅ PRODUCTOS PÚBLICOS (incluye /admin SIN middleware)
    Route::get('/productos/admin', [ProductoController::class, 'indexAdmin']);
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
    Route::get('/productos/tipo/{tipo}', [ProductoController::class, 'porTipo']);
    Route::get('/productos-tipos', [ProductoController::class, 'tipos']);
    Route::get('/productos-colores', [ProductoController::class, 'colores']);
});

// ============================================
// 🔒 RUTAS PROTEGIDAS (REQUIEREN AUTENTICACIÓN)
// ============================================
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // ===================================
    // 👤 PERFIL Y AUTENTICACIÓN
    // ===================================
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/mi-perfil', [AuthController::class, 'miPerfil']);
    
    // ===================================
    // 🛒 CARRITO DE COMPRAS (COMPLETO)
    // ===================================
    Route::get('/carrito', [CarritoController::class, 'miCarrito']);
    Route::post('/carrito/agregar', [CarritoController::class, 'agregarProducto']);
    Route::put('/carrito/actualizar/{detalleId}', [CarritoController::class, 'actualizarCantidad']);
    Route::delete('/carrito/eliminar/{detalleId}', [CarritoController::class, 'eliminarProducto']);
    Route::delete('/carrito/vaciar', [CarritoController::class, 'vaciarCarrito']);
    Route::post('/carrito/checkout', [CarritoController::class, 'crearVentaDesdeCarrito']); // ✅ AGREGADO
    
    // ===================================
    // 💰 VENTAS
    // ===================================
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::post('/ventas/crear', [VentaController::class, 'crearVenta']); // Alias
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/{id}', [VentaController::class, 'show']);
    Route::put('/ventas/{id}/estado', [VentaController::class, 'actualizarEstado']);
    Route::get('/mis-ventas', [VentaController::class, 'misVentas']);
    
    // ===================================
    // 📦 PRODUCTOS (Admin y Vendedor)
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        Route::post('/productos/subir-imagen', [ProductoController::class, 'subirImagen']);
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{id}', [ProductoController::class, 'update']);
        Route::post('/productos/{id}', [ProductoController::class, 'update']); // Para FormData
    });
    
    Route::middleware(['role:administrador'])->group(function () {
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
    });
    
    // ===================================
    // 👥 CLIENTES (Admin y Vendedor)
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::get('/clientes/{id}', [ClienteController::class, 'show']);
        Route::put('/clientes/{id}', [ClienteController::class, 'update']);
    });
    
    Route::middleware(['role:administrador'])->group(function () {
        Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
    });
});