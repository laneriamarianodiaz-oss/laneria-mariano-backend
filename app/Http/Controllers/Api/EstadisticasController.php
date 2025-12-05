<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Producto;
use Carbon\Carbon;

class EstadisticasController extends Controller
{
    /**
     * Estadísticas de ventas para el tablero
     */
    public function ventas()
    {
        try {
            // Fechas
            $hoy = Carbon::today();
            $ayer = Carbon::yesterday();
            $inicioMes = Carbon::now()->startOfMonth();
            $mesAnterior = Carbon::now()->subMonth();

            // Ventas de hoy
            $ventasHoy = Venta::whereDate('fecha_venta', $hoy)
                ->where('estado_venta', 'Completada')
                ->sum('total_venta') ?? 0;

            // Ventas de ayer
            $ventasAyer = Venta::whereDate('fecha_venta', $ayer)
                ->where('estado_venta', 'Completada')
                ->sum('total_venta') ?? 0;

            // Ventas del mes actual
            $ventasMes = Venta::whereDate('fecha_venta', '>=', $inicioMes)
                ->where('estado_venta', 'Completada')
                ->sum('total_venta') ?? 0;

            // Ventas del mes anterior
            $ventasMesAnterior = Venta::whereYear('fecha_venta', $mesAnterior->year)
                ->whereMonth('fecha_venta', $mesAnterior->month)
                ->where('estado_venta', 'Completada')
                ->sum('total_venta') ?? 0;

            // Ticket promedio del mes
            $cantidadVentasMes = Venta::whereDate('fecha_venta', '>=', $inicioMes)
                ->where('estado_venta', 'Completada')
                ->count();
            
            $ticketPromedio = $cantidadVentasMes > 0 ? $ventasMes / $cantidadVentasMes : 0;

            // Productos con stock bajo (comparar stock_disponible con stock_minimo)
// DESPUÉS (CORRECTO):
$productosStockBajo = Producto::whereColumn('stock_disponible', '<=', 'stock_minimo')
    ->where('estado_producto', 'Activo')
    ->count();
            // Calcular cambios porcentuales
            $cambioVentasHoy = $ventasAyer > 0 
                ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1)
                : 0;

            $cambioVentasMes = $ventasMesAnterior > 0
                ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
                : 0;

            return response()->json([
                'ventasHoy' => (float) $ventasHoy,
                'ventasMes' => (float) $ventasMes,
                'ticketPromedio' => (float) round($ticketPromedio, 2),
                'productosStockBajo' => (int) $productosStockBajo,
                'cambioVentasHoy' => (float) $cambioVentasHoy,
                'cambioVentasMes' => (float) $cambioVentasMes,
                'cambioTicket' => 0
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener estadísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de clientes
     */
    public function clientes()
    {
        try {
            $totalClientes = Cliente::count();
            $clientesActivos = Cliente::whereHas('ventas', function($query) {
                $query->where('fecha_venta', '>=', Carbon::now()->subMonths(3));
            })->count();
            
            return response()->json([
                'totalClientes' => $totalClientes,
                'clientesActivos' => $clientesActivos,
                'nuevosEsteMes' => Cliente::whereMonth('fecha_registro', Carbon::now()->month)->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener estadísticas de clientes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de proveedores
     */
    public function proveedores()
    {
        try {
            $totalProveedores = Proveedor::count();
            
            return response()->json([
                'totalProveedores' => $totalProveedores,
                'proveedoresActivos' => $totalProveedores
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener estadísticas de proveedores',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pedidos activos
     */
    public function pedidosActivos()
    {
        try {
            return response()->json([
                'pedidosPendientes' => 0,
                'pedidosEnProceso' => 0,
                'pedidosListos' => 0
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener pedidos activos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}