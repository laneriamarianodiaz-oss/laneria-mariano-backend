<?php

namespace App\Http\Controllers\Api;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InventarioController extends BaseController
{
    /**
     * Listar todo el inventario
     */
    public function index(Request $request)
    {
        try {
            $query = Producto::select(
                'producto_id as inventario_id',
                'codigo_producto as codigo_lana',
                'nombre_producto',
                'tipo_de_producto as producto_tipo',
                'categoria',  // ⭐ CATEGORÍA
                'color_producto as producto_color',
                'precio_producto as producto_precio',
                'stock_disponible as stock_actual',
                'stock_minimo',
                'proveedor_id',
                'updated_at as ultima_actualizacion'
            )->where('estado_producto', 'Activo');

            // Filtros
            if ($request->has('busqueda')) {
                $busqueda = $request->busqueda;
                $query->where(function($q) use ($busqueda) {
                    $q->where('nombre_producto', 'LIKE', "%{$busqueda}%")
                      ->orWhere('codigo_producto', 'LIKE', "%{$busqueda}%");
                });
            }

            if ($request->has('categoria')) {
                $query->where('categoria', $request->categoria);
            }

            if ($request->has('estado')) {
                $estado = $request->estado;
                if ($estado === 'critico') {
                    $query->whereRaw('stock_disponible <= (stock_minimo * 0.5)');
                } elseif ($estado === 'bajo') {
                    $query->whereRaw('stock_disponible <= stock_minimo AND stock_disponible > (stock_minimo * 0.5)');
                } elseif ($estado === 'normal') {
                    $query->whereRaw('stock_disponible > stock_minimo AND stock_disponible <= (stock_minimo * 2)');
                } elseif ($estado === 'exceso') {
                    $query->whereRaw('stock_disponible > (stock_minimo * 2)');
                }
            }

            // Ordenamiento
            $orden = $request->get('orden', 'nombre');
            $direccion = $request->get('direccion', 'asc');

            switch ($orden) {
                case 'stock':
                    $query->orderBy('stock_disponible', $direccion);
                    break;
                case 'valor':
                    $query->orderByRaw("(stock_disponible * precio_producto) {$direccion}");
                    break;
                default:
                    $query->orderBy('nombre_producto', $direccion);
            }

            $inventario = $query->get();

            // 🔍 DEBUG: Ver qué devuelve la consulta
            Log::info('🔍 Primer producto del inventario:', [
                'data' => $inventario->first()
            ]);

            return $this->successResponse($inventario);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener inventario: ' . $e->getMessage());
            return $this->errorResponse('Error al obtener inventario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener inventario de un producto específico
     */
    public function show($productoId)
    {
        $producto = Producto::select(
            'producto_id as inventario_id',
            'codigo_producto as codigo_lana',
            'nombre_producto',
            'tipo_de_producto as producto_tipo',
            'categoria',  // ⭐ CATEGORÍA
            'color_producto as producto_color',
            'precio_producto as producto_precio',
            'stock_disponible as stock_actual',
            'stock_minimo',
            'updated_at as ultima_actualizacion'
        )->where('producto_id', $productoId)
          ->where('estado_producto', 'Activo')
          ->first();

        if (!$producto) {
            return $this->notFoundResponse('Producto no encontrado');
        }

        return $this->successResponse($producto);
    }

    /**
     * Actualizar stock de un producto
     */
    public function actualizarStock(Request $request, $productoId)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|integer',
            'tipo' => 'required|in:entrada,salida,ajuste',
            'motivo' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $producto = Producto::find($productoId);

        if (!$producto) {
            return $this->notFoundResponse('Producto no encontrado');
        }

        $cantidad = $request->cantidad;
        $stockAnterior = $producto->stock_disponible;

        switch ($request->tipo) {
            case 'entrada':
                $producto->stock_disponible += $cantidad;
                break;

            case 'salida':
                if ($producto->stock_disponible < $cantidad) {
                    return $this->errorResponse('Stock insuficiente', 400);
                }
                $producto->stock_disponible -= $cantidad;
                break;

            case 'ajuste':
                $producto->stock_disponible = $cantidad;
                break;
        }

        $producto->save();

        return $this->successResponse([
            'producto_id' => $producto->producto_id,
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $producto->stock_disponible,
            'diferencia' => $producto->stock_disponible - $stockAnterior,
        ], 'Stock actualizado exitosamente');
    }

    /**
     * Obtener alertas de stock bajo
     */
    public function alertasStockBajo()
    {
        try {
            $alertas = Producto::select(
                'producto_id as inventario_id',
                'codigo_producto as codigo_lana',
                'nombre_producto',
                'categoria',  // ⭐ CATEGORÍA
                'stock_disponible as stock_actual',
                'stock_minimo',
                'precio_producto as producto_precio'
            )
            ->where('estado_producto', 'Activo')
            ->whereRaw('stock_disponible <= stock_minimo')
            ->orderBy('stock_disponible', 'asc')
            ->get();

            $alertasFormateadas = $alertas->map(function($producto) {
                return [
                    'inventario_id' => $producto->inventario_id,
                    'codigo_lana' => $producto->codigo_lana,
                    'producto_nombre' => $producto->nombre_producto,
                    'categoria' => $producto->categoria,  // ⭐ CATEGORÍA
                    'stock_actual' => $producto->stock_actual,
                    'stock_minimo' => $producto->stock_minimo,
                    'diferencia' => $producto->stock_minimo - $producto->stock_actual,
                    'nivel_alerta' => $producto->stock_actual == 0 ? 'crítico' : 'bajo',
                    'producto_precio' => $producto->producto_precio
                ];
            });

            return $this->successResponse([
                'total_alertas' => $alertasFormateadas->count(),
                'alertas' => $alertasFormateadas,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener alertas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener productos sin stock
     */
    public function productosSinStock()
    {
        $productos = Producto::select(
            'producto_id',
            'nombre_producto',
            'tipo_de_producto',
            'categoria',  // ⭐ CATEGORÍA
            'updated_at as ultima_actualizacion'
        )
        ->where('estado_producto', 'Activo')
        ->where('stock_disponible', 0)
        ->get();

        return $this->successResponse([
            'total' => $productos->count(),
            'productos' => $productos,
        ]);
    }

    /**
     * Actualizar stock mínimo de un producto
     */
    public function actualizarStockMinimo(Request $request, $productoId)
    {
        $validator = Validator::make($request->all(), [
            'stock_minimo' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $producto = Producto::find($productoId);

        if (!$producto) {
            return $this->notFoundResponse('Producto no encontrado');
        }

        $producto->stock_minimo = $request->stock_minimo;
        $producto->save();

        return $this->successResponse($producto, 'Stock mínimo actualizado exitosamente');
    }

    /**
     * Obtener resumen del inventario (para estadísticas)
     */
    public function resumen()
    {
        try {
            $totalProductos = Producto::where('estado_producto', 'Activo')->count();
            
            $totalValorInventario = Producto::where('estado_producto', 'Activo')
                ->selectRaw('SUM(stock_disponible * precio_producto) as total')
                ->value('total') ?? 0;
            
            $productosCritico = Producto::where('estado_producto', 'Activo')
                ->whereRaw('stock_disponible <= (stock_minimo * 0.5)')
                ->count();

            $productosBajo = Producto::where('estado_producto', 'Activo')
                ->whereRaw('stock_disponible <= stock_minimo AND stock_disponible > (stock_minimo * 0.5)')
                ->count();

            $productosNormal = Producto::where('estado_producto', 'Activo')
                ->whereRaw('stock_disponible > stock_minimo AND stock_disponible <= (stock_minimo * 2)')
                ->count();

            $productosExceso = Producto::where('estado_producto', 'Activo')
                ->whereRaw('stock_disponible > (stock_minimo * 2)')
                ->count();

            $productosSinStock = Producto::where('estado_producto', 'Activo')
                ->where('stock_disponible', 0)
                ->count();

            return $this->successResponse([
                'total_productos' => $totalProductos,
                'valor_total_inventario' => round($totalValorInventario, 2),
                'productos_stock_critico' => $productosCritico,
                'productos_stock_bajo' => $productosBajo,
                'productos_stock_normal' => $productosNormal,
                'productos_exceso' => $productosExceso,
                'productos_sin_stock' => $productosSinStock,
                'movimientos_hoy' => 0,
                'movimientos_mes' => 0
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener resumen: ' . $e->getMessage(), 500);
        }
    }
}