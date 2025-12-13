<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\DetalleVenta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            // ✅ CORRECCIÓN: Usar 'Completado' (sin 'a')
            // Ventas de hoy
            $ventasHoy = Venta::whereDate('fecha_venta', $hoy)
                ->where('estado_venta', 'Completado')
                ->sum('total_venta') ?? 0;

            // Ventas de ayer
            $ventasAyer = Venta::whereDate('fecha_venta', $ayer)
                ->where('estado_venta', 'Completado')
                ->sum('total_venta') ?? 0;

            // Ventas del mes actual
            $ventasMes = Venta::whereDate('fecha_venta', '>=', $inicioMes)
                ->where('estado_venta', 'Completado')
                ->sum('total_venta') ?? 0;

            // Ventas del mes anterior
            $ventasMesAnterior = Venta::whereYear('fecha_venta', $mesAnterior->year)
                ->whereMonth('fecha_venta', $mesAnterior->month)
                ->where('estado_venta', 'Completado')
                ->sum('total_venta') ?? 0;

            // Ticket promedio del mes
            $cantidadVentasMes = Venta::whereDate('fecha_venta', '>=', $inicioMes)
                ->where('estado_venta', 'Completado')
                ->count();
            
            $ticketPromedio = $cantidadVentasMes > 0 ? $ventasMes / $cantidadVentasMes : 0;

            // ✅ CORRECCIÓN: Productos con stock bajo
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

            Log::info('📊 Estadísticas calculadas:', [
                'ventasHoy' => $ventasHoy,
                'ventasMes' => $ventasMes,
                'productosStockBajo' => $productosStockBajo
            ]);

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
            Log::error('❌ Error en estadísticas de ventas:', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener estadísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐ NUEVO: Ventas de la última semana (para el gráfico)
     */
    public function ventasSemana()
    {
        try {
            $ventasSemana = [];
            $diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            
            for ($i = 6; $i >= 0; $i--) {
                $fecha = Carbon::today()->subDays($i);
                
                $total = Venta::whereDate('fecha_venta', $fecha)
                    ->where('estado_venta', 'Completado')
                    ->sum('total_venta') ?? 0;
                
                $ventasSemana[] = [
                    'fecha' => $diasSemana[$fecha->dayOfWeek === 0 ? 6 : $fecha->dayOfWeek - 1],
                    'total' => (float) $total
                ];
            }

            return response()->json($ventasSemana, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error en ventas semana:', [
                'mensaje' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener ventas de la semana',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐ NUEVO: Últimas ventas recientes
     */
    public function ventasRecientes(Request $request)
    {
        try {
            $limite = $request->get('limite', 5);
            
            $ventas = Venta::with(['cliente'])
                ->orderBy('fecha_venta', 'desc')
                ->limit($limite)
                ->get();

            $ventasMapeadas = $ventas->map(function($venta) {
                return [
                    'id' => $venta->venta_id,
                    'numero' => $venta->numero_venta,
                    'fecha' => $venta->fecha_venta->format('d/m/Y H:i'),
                    'cliente' => $venta->cliente->nombre_cliente ?? 'Cliente',
                    'total' => (float) $venta->total_venta,
                    'estado' => $this->normalizarEstado($venta->estado_venta)
                ];
            });

            return response()->json($ventasMapeadas, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error en ventas recientes:', [
                'mensaje' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener ventas recientes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐ NUEVO: Top 5 productos más vendidos
     */
    public function topProductos(Request $request)
    {
        try {
            $limite = $request->get('limite', 5);
            $diasAtras = $request->get('dias', 30);
            
            $fechaInicio = Carbon::now()->subDays($diasAtras);

            // Verificar si hay ventas primero
            $hayVentas = Venta::where('fecha_venta', '>=', $fechaInicio)
                ->where('estado_venta', 'Completado')
                ->exists();

            if (!$hayVentas) {
                return response()->json([], 200);
            }

            $topProductos = DB::table('detalle_venta')
                ->join('productos', 'detalle_venta.producto_id', '=', 'productos.producto_id')
                ->join('ventas', 'detalle_venta.venta_id', '=', 'ventas.venta_id')
                ->where('ventas.fecha_venta', '>=', $fechaInicio)
                ->where('ventas.estado_venta', 'Completado')
                ->select(
                    'productos.producto_id as id',
                    'productos.nombre_producto as nombre',
                    DB::raw('CAST(SUM(detalle_venta.cantidad) as UNSIGNED) as cantidadVendida')
                )
                ->groupBy('productos.producto_id', 'productos.nombre_producto')
                ->orderBy('cantidadVendida', 'desc')
                ->limit($limite)
                ->get();

            return response()->json($topProductos, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error en top productos:', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            
            // Devolver array vacío en vez de error 500
            return response()->json([], 200);
        }
    }

    /**
     * ⭐ NUEVO: Alertas de stock bajo para el dashboard
     */
    public function alertasStockDashboard()
    {
        try {
            $productos = Producto::whereColumn('stock_disponible', '<=', 'stock_minimo')
                ->where('estado_producto', 'Activo')
                ->orderBy('stock_disponible', 'asc')
                ->limit(5)
                ->get();

            $alertas = $productos->map(function($producto) {
                $porcentaje = $producto->stock_minimo > 0 
                    ? ($producto->stock_disponible / $producto->stock_minimo) * 100 
                    : 0;

                return [
                    'id' => $producto->producto_id,
                    'nombre' => $producto->nombre_producto,
                    'codigo' => $producto->codigo_producto ?? 'N/A',
                    'stock' => $producto->stock_disponible,
                    'stockMinimo' => $producto->stock_minimo,
                    'estado' => $porcentaje <= 50 ? 'critico' : 'bajo'
                ];
            });

            return response()->json($alertas, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error en alertas de stock:', [
                'mensaje' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener alertas de stock',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐ NUEVO: Datos completos del dashboard en una sola llamada
     */
    public function dashboardCompleto()
    {
        try {
            // Obtener todas las estadísticas en una sola respuesta
            $estadisticas = $this->ventas()->getData();
            $ventasSemana = $this->ventasSemana()->getData();
            $ventasRecientes = $this->ventasRecientes(request())->getData();
            $topProductos = $this->topProductos(request())->getData();
            $alertasStock = $this->alertasStockDashboard()->getData();

            return response()->json([
                'estadisticas' => $estadisticas,
                'ventasSemana' => $ventasSemana,
                'ventasRecientes' => $ventasRecientes,
                'topProductos' => $topProductos,
                'alertasStock' => $alertasStock
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error en dashboard completo:', [
                'mensaje' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener datos del dashboard',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalizar estado de venta para el frontend
     */
    private function normalizarEstado($estado)
    {
        $estados = [
            'Pendiente' => 'pendiente',
            'Confirmado' => 'pendiente',
            'En Proceso' => 'pendiente',
            'Enviado' => 'pendiente',
            'Entregado' => 'completada',
            'Completado' => 'completada',
            'Cancelado' => 'cancelada'
        ];

        return $estados[$estado] ?? 'pendiente';
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
}