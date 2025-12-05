<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Inventario;

class ReporteController extends Controller
{
    /**
     * Dashboard principal con métricas generales
     */
    public function dashboard()
    {
        try {
            $hoy = now()->startOfDay();
            $mesActual = now()->startOfMonth();
            
            // Ventas del día
            $ventasHoy = Venta::whereDate('fecha_venta', $hoy)
                ->where('estado_venta', 'Completada')
                ->sum('total_venta') ?? 0;
                
            // Ventas del mes
            $ventasMes = Venta::whereMonth('fecha_venta', now()->month)
                ->whereYear('fecha_venta', now()->year)
                ->where('estado_venta', 'Completada')
                ->sum('total_venta') ?? 0;
                
            // Ventas pendientes
            $ventasPendientes = Venta::where('estado_venta', 'Pendiente')->count();
            
            // Total productos activos
            $totalProductos = Producto::where('estado_producto', 'Activo')->count();
            
            // Productos con stock bajo
            $stockBajo = Inventario::whereRaw('stock_actual <= stock_minimo')
                ->count();
            
            // Productos sin stock
            $sinStock = Inventario::where('stock_actual', 0)->count();
            
            // Total clientes
            $totalClientes = Cliente::count();
            
            // Top 5 productos más vendidos del mes
            $topProductosMes = DB::table('detalle_ventas as dv')
                ->join('ventas as v', 'dv.venta_id', '=', 'v.venta_id')
                ->join('productos as p', 'dv.producto_id', '=', 'p.producto_id')
                ->whereMonth('v.fecha_venta', now()->month)
                ->whereYear('v.fecha_venta', now()->year)
                ->where('v.estado_venta', 'Completada')
                ->select(
                    'p.producto_id',
                    'p.nombre_producto',
                    DB::raw('SUM(dv.cantidad) as total_vendido'),
                    DB::raw('SUM(dv.subtotal) as total_ingresos')
                )
                ->groupBy('p.producto_id', 'p.nombre_producto')
                ->orderByDesc('total_vendido')
                ->limit(5)
                ->get();
            
            $data = [
                'ventas' => [
                    'hoy' => (float) $ventasHoy,
                    'mes' => (float) $ventasMes,
                    'pendientes' => $ventasPendientes
                ],
                'inventario' => [
                    'total_productos' => $totalProductos,
                    'stock_bajo' => $stockBajo,
                    'sin_stock' => $sinStock
                ],
                'clientes' => [
                    'total' => $totalClientes
                ],
                'top_productos_mes' => $topProductosMes
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error en dashboard: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}