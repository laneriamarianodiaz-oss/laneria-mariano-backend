<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\ReporteController;

// ============================================
// 🔐 AUTENTICACIÓN (PÚBLICAS)
// ============================================
Route::prefix('v1/auth')->group(function () {
    // Registro y Login
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Verificación de Email
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-code', [AuthController::class, 'resendVerificationCode']);
    
    // Recuperación de Contraseña
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// ============================================
// 📦 RUTAS PÚBLICAS
// ============================================
Route::prefix('v1')->group(function () {
    
    // ✅ PRODUCTOS PÚBLICOS
    Route::get('/productos/admin', [ProductoController::class, 'indexAdmin']);
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
    Route::get('/productos/tipo/{tipo}', [ProductoController::class, 'porTipo']);
    Route::get('/productos-tipos', [ProductoController::class, 'tipos']);
    Route::get('/productos-colores', [ProductoController::class, 'colores']);
    
    // ✅ CLIENTES PÚBLICOS (Búsqueda y Registro)
    Route::get('/clientes/buscar', [ClienteController::class, 'buscar']);
    Route::get('/clientes/buscar/telefono/{telefono}', [ClienteController::class, 'buscarPorTelefono']);
    Route::post('/clientes', [ClienteController::class, 'store']);
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
    // 💰 VENTAS (ACCESO GENERAL AUTENTICADO)
    // ===================================
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::get('/ventas/{id}', [VentaController::class, 'show']);
    Route::put('/ventas/{id}/estado', [VentaController::class, 'actualizarEstado']);
    Route::post('/ventas/{id}/comprobante', [VentaController::class, 'subirComprobante']);
    
    // ===================================
    // 🛒 CARRITO DE COMPRAS
    // ===================================
    Route::get('/carrito', [CarritoController::class, 'obtenerCarrito']);
    Route::post('/carrito/agregar', [CarritoController::class, 'agregarProducto']);
    Route::put('/carrito/detalle/{detalleId}', [CarritoController::class, 'actualizarCantidad']);
    Route::delete('/carrito/detalle/{detalleId}', [CarritoController::class, 'eliminarProducto']);
    Route::delete('/carrito/vaciar', [CarritoController::class, 'vaciarCarrito']);
    Route::post('/carrito/checkout', [CarritoController::class, 'crearVentaDesdeCarrito']);
    
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
    // 💰 VENTAS ADMINISTRATIVAS (Admin y Vendedor)
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        Route::get('/ventas/estadisticas/general', [VentaController::class, 'estadisticas']);
        Route::get('/ventas/cliente/{clienteId}', [VentaController::class, 'ventasPorCliente']);
    });
    
    // ===================================
    // 👥 CLIENTES (Admin y Vendedor)
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::get('/clientes/estadisticas', [ClienteController::class, 'obtenerEstadisticas']);
        Route::get('/clientes/exportar', [ClienteController::class, 'exportarExcel']);
        Route::get('/clientes/{id}/historial', [ClienteController::class, 'obtenerHistorial']);
        Route::get('/clientes/{id}', [ClienteController::class, 'show']);
        Route::put('/clientes/{id}', [ClienteController::class, 'update']);
        Route::get('/clientes/frecuentes/lista', [ClienteController::class, 'clientesFrecuentes']);
        Route::put('/clientes/{id}/preferencias', [ClienteController::class, 'actualizarPreferencias']);
    });
    
    Route::middleware(['role:administrador'])->group(function () {
        Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
    });
    
    // ===================================
    // 🏭 PROVEEDORES (Solo Admin)
    // ===================================
    Route::middleware(['role:administrador'])->group(function () {
        Route::get('/proveedores', [ProveedorController::class, 'index']);
        Route::get('/proveedores/{id}', [ProveedorController::class, 'show']);
        Route::post('/proveedores', [ProveedorController::class, 'store']);
        Route::put('/proveedores/{id}', [ProveedorController::class, 'update']);
        Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy']);
        Route::get('/proveedores/{id}/productos', [ProveedorController::class, 'productos']);
    });
    
    // ===================================
    // 📈 REPORTES (Admin y Vendedor)
    // ===================================
    Route::middleware(['role:administrador,vendedor'])->group(function () {
        Route::get('/reportes/dashboard', [ReporteController::class, 'dashboard']);
        Route::get('/reportes/ventas-periodo', [ReporteController::class, 'ventasPorPeriodo']);
        Route::get('/reportes/productos-mas-vendidos', [ReporteController::class, 'productosMasVendidos']);
        Route::get('/reportes/inventario', [ReporteController::class, 'reporteInventario']);
        Route::get('/reportes/clientes-frecuentes', [ReporteController::class, 'clientesFrecuentes']);
        Route::get('/reportes/ventas-metodo-pago', [ReporteController::class, 'ventasPorMetodoPago']);
    });
});