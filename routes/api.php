<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\TestController;

// ============================================
// 🔐 AUTENTICACIÓN (PÚBLICAS)
// ============================================
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ============================================
// 🧪 PRUEBAS (TEMPORAL)
// ============================================
Route::get('v1/test-cloudinary', [TestController::class, 'testCloudinary']);

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
    // 📦 PEDIDOS (CLIENTES)
    // ===================================
    Route::get('/mis-pedidos', [VentaController::class, 'misPedidos']);
    Route::get('/pedidos/{id}', [VentaController::class, 'show']);
    Route::post('/pedidos/{id}/cancelar', [VentaController::class, 'cancelar']);
    
    // ===================================
    // 🛒 CARRITO DE COMPRAS
    // ===================================
    Route::get('/carrito', [CarritoController::class, 'miCarrito']);
    Route::post('/carrito/agregar', [CarritoController::class, 'agregarProducto']);
    Route::put('/carrito/actualizar/{detalleId}', [CarritoController::class, 'actualizarCantidad']);
    Route::delete('/carrito/eliminar/{detalleId}', [CarritoController::class, 'eliminarProducto']);
    Route::delete('/carrito/vaciar', [CarritoController::class, 'vaciarCarrito']);
    Route::post('/carrito/checkout', [CarritoController::class, 'crearVentaDesdeCarrito']);
    
    // ===================================
    // 💰 VENTAS Y COMPROBANTES
    // ===================================
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::post('/ventas/crear', [VentaController::class, 'crearVenta']);
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/{id}', [VentaController::class, 'show']);
    Route::put('/ventas/{id}/estado', [VentaController::class, 'actualizarEstado']);
    Route::get('/mis-ventas', [VentaController::class, 'misVentas']);
    
    // 📸 SUBIR COMPROBANTE (Cliente puede subir después de hacer pedido)
    Route::post('/ventas/{id}/comprobante', [VentaController::class, 'subirComprobante']);
    
    // ===================================
    // 📦 PRODUCTOS (TEMPORAL SIN MIDDLEWARE)
    // ===================================
    // ⚠️ TEMPORAL: Quitamos middleware de roles para probar
    Route::post('/productos/subir-imagen', [ProductoController::class, 'subirImagen']);
    Route::post('/productos/imagen', [ProductoController::class, 'subirImagen']); // ✅ Alias
    Route::post('/productos', [ProductoController::class, 'store']);
    Route::put('/productos/{id}', [ProductoController::class, 'update']);
    Route::post('/productos/{id}', [ProductoController::class, 'update']);
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
    
    // ===================================
    // 📊 INVENTARIO (Admin y Vendedor)
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        Route::get('/inventario', [InventarioController::class, 'index']);
        Route::get('/inventario/{productoId}', [InventarioController::class, 'show']);
        Route::put('/inventario/{productoId}/actualizar-stock', [InventarioController::class, 'actualizarStock']);
        Route::put('/inventario/{productoId}/stock-minimo', [InventarioController::class, 'actualizarStockMinimo']);
        Route::get('/inventario/alertas/stock-bajo', [InventarioController::class, 'alertasStockBajo']);
        Route::get('/inventario/alertas/sin-stock', [InventarioController::class, 'productosSinStock']);
        Route::get('/inventario/resumen/general', [InventarioController::class, 'resumen']);
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