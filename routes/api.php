<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\EstadisticasController;

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
    Route::put('/auth/actualizar-perfil', [AuthController::class, 'actualizarPerfil']);
    
    // ===================================
    // 📦 PEDIDOS (CLIENTES)
    // ===================================
    Route::get('/mis-pedidos', [VentaController::class, 'misPedidos']);
    Route::get('/pedidos/{id}', [VentaController::class, 'show']);
    Route::post('/pedidos/{id}/cancelar', [VentaController::class, 'cancelar']);
    Route::post('/pedidos/{id}/comprobante', [VentaController::class, 'subirComprobante']);
    
    // ===================================
    // 🛒 CARRITO DE COMPRAS
    // ===================================
    Route::get('/carrito', [CarritoController::class, 'obtenerCarrito']);
    Route::post('/carrito/agregar', [CarritoController::class, 'agregarProducto']);
    Route::put('/carrito/actualizar/{detalleId}', [CarritoController::class, 'actualizarCantidad']);
    Route::delete('/carrito/eliminar/{detalleId}', [CarritoController::class, 'eliminarProducto']);
    Route::delete('/carrito/vaciar', [CarritoController::class, 'vaciarCarrito']);
    Route::post('/carrito/checkout', [CarritoController::class, 'crearVentaDesdeCarrito']);
    
    // ===================================
    // 📊 ESTADÍSTICAS Y DASHBOARD (Admin y Vendedor)
    // ⚠️ IMPORTANTE: ESTAS RUTAS DEBEN IR **ANTES** DE LAS RUTAS DE VENTAS
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        // ⭐ RUTAS ESPECÍFICAS DE ESTADÍSTICAS (ANTES DE /ventas/{id})
        Route::get('/ventas/estadisticas', [EstadisticasController::class, 'ventas']);
        Route::get('/ventas/semana', [EstadisticasController::class, 'ventasSemana']);
        Route::get('/ventas/recientes', [EstadisticasController::class, 'ventasRecientes']);
        Route::get('/ventas/top-productos', [EstadisticasController::class, 'topProductos']);
        
        // Dashboard
        Route::get('/dashboard/alertas-stock', [EstadisticasController::class, 'alertasStockDashboard']);
        Route::get('/dashboard/completo', [EstadisticasController::class, 'dashboardCompleto']);
        
        // Otras estadísticas
        Route::get('/estadisticas/clientes', [EstadisticasController::class, 'clientes']);
        
        // Admin pedidos
        Route::get('/admin/pedidos', [VentaController::class, 'listarPedidos']);
        Route::put('/admin/pedidos/{id}/estado', [VentaController::class, 'actualizarEstado']);
    });
    
    // ===================================
    // 💰 VENTAS Y COMPROBANTES
    // ⚠️ ESTAS RUTAS VAN **DESPUÉS** DE LAS ESTADÍSTICAS
    // ===================================
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::post('/ventas/crear', [VentaController::class, 'crearVenta']);
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/{id}', [VentaController::class, 'show']); // ⚠️ Esta ruta dinámica va al final
    Route::put('/ventas/{id}/estado', [VentaController::class, 'actualizarEstado']);
    Route::get('/mis-ventas', [VentaController::class, 'misVentas']);
    Route::post('/ventas/{id}/comprobante', [VentaController::class, 'subirComprobante']);
    
    // ===================================
    // 📦 PRODUCTOS (Admin y Vendedor)
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{id}', [ProductoController::class, 'update']);
        Route::post('/productos/{id}', [ProductoController::class, 'update']);
    });
    
    Route::middleware(['role:administrador'])->group(function () {
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
    });
    
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
        // ⭐ IMPORTANTE: Las rutas específicas ANTES de las dinámicas
        Route::get('/clientes/buscar', [ClienteController::class, 'buscar']);
        Route::get('/clientes/frecuentes', [ClienteController::class, 'clientesFrecuentes']);
        Route::get('/clientes/telefono/{telefono}', [ClienteController::class, 'buscarPorTelefono']);
        
        // Rutas CRUD principales
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::post('/clientes', [ClienteController::class, 'store']);
        Route::get('/clientes/{id}', [ClienteController::class, 'show']);
        Route::put('/clientes/{id}', [ClienteController::class, 'update']);
        
        // ⭐ HISTORIAL
        Route::get('/clientes/{id}/historial', [ClienteController::class, 'obtenerHistorial']);
        
        Route::put('/clientes/{id}/preferencias', [ClienteController::class, 'actualizarPreferencias']);
    });
    
    Route::middleware(['role:administrador'])->group(function () {
        Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
    });
});