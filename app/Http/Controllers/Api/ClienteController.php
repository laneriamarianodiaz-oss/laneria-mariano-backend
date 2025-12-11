<?php

namespace App\Http\Controllers\Api;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ClienteController extends BaseController
{
    /**
     * Listar todos los clientes con estadísticas
     */
    public function index(Request $request)
    {
        try {
            $query = Cliente::query();

            // Búsqueda por nombre, teléfono o email
            if ($request->has('buscar') && !empty($request->buscar)) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nombre_cliente', 'LIKE', "%{$buscar}%")
                      ->orWhere('telefono', 'LIKE', "%{$buscar}%")
                      ->orWhere('email', 'LIKE', "%{$buscar}%");
                });
            }

            // Filtro por rango de fechas
            if ($request->has('fecha_desde') && !empty($request->fecha_desde)) {
                $query->where('created_at', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta') && !empty($request->fecha_hasta)) {
                $query->where('created_at', '<=', $request->fecha_hasta . ' 23:59:59');
            }

            $orderBy = $request->get('order_by', 'created_at');
            $orderDir = $request->get('order_dir', 'desc');
            $query->orderBy($orderBy, $orderDir);

            // Obtener clientes
            $clientes = $query->get();
            
            // Procesar con estadísticas usando SQL directo
            $clientesConEstadisticas = $clientes->map(function($cliente) {
                try {
                    $ventas = Venta::where('cliente_id', $cliente->cliente_id)
                        ->where('estado_venta', '!=', 'Cancelado')
                        ->orderBy('fecha_venta', 'desc')
                        ->get();
                    
                    // ⭐ USAR total_venta directamente (columna real)
                    $totalCompras = $ventas->sum('total_venta');
                    
                    return [
                        'cliente_id' => $cliente->cliente_id,
                        'nombre_cliente' => $cliente->nombre_cliente,
                        'contacto_cliente' => $cliente->contacto_cliente,
                        'telefono' => $cliente->telefono,
                        'email' => $cliente->email,
                        'direccion' => $cliente->direccion,
                        'preferencias_clie' => $cliente->preferencias_cliente,
                        'fecha_registro' => $cliente->created_at ? $cliente->created_at->format('Y-m-d H:i:s') : null,
                        'total_compras' => (float) $totalCompras,
                        'cantidad_compras' => (int) $ventas->count(),
                        'ultima_compra' => $ventas->first() ? $ventas->first()->fecha_venta : null,
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error procesando cliente ' . $cliente->cliente_id . ': ' . $e->getMessage());
                    
                    return [
                        'cliente_id' => $cliente->cliente_id,
                        'nombre_cliente' => $cliente->nombre_cliente,
                        'contacto_clie' => $cliente->contacto_cliente,
                        'telefono' => $cliente->telefono,
                        'email' => $cliente->email,
                        'direccion' => $cliente->direccion,
                        'preferencias_clie' => $cliente->preferencias_cliente,
                        'fecha_registro' => $cliente->created_at ? $cliente->created_at->format('Y-m-d H:i:s') : null,
                        'total_compras' => 0,
                        'cantidad_compras' => 0,
                        'ultima_compra' => null,
                    ];
                }
            });

            // Paginación manual
            $perPage = $request->get('per_page', 15);
            $pagina = $request->get('page', 1);
            
            $total = $clientesConEstadisticas->count();
            $clientesPaginados = $clientesConEstadisticas->forPage($pagina, $perPage)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $clientesPaginados,
                    'current_page' => (int) $pagina,
                    'last_page' => (int) ceil($total / $perPage),
                    'per_page' => (int) $perPage,
                    'total' => $total,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error al listar clientes: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⭐ HISTORIAL DE COMPRAS
     */
    public function obtenerHistorial($id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            
            $ventas = Venta::where('cliente_id', $id)
                ->with(['detalles.producto'])
                ->orderBy('fecha_venta', 'desc')
                ->get();
            
            $historial = $ventas->map(function($venta) {
                return [
                    'venta_id' => $venta->venta_id,
                    'numero_venta' => $venta->numero_venta,
                    'fecha_venta' => $venta->fecha_venta,
                    'total' => (float) $venta->total_venta, // ⭐ Usar columna real
                    'estado_venta' => $venta->estado_venta ?? 'Pendiente',
                    'metodo_pago' => $venta->metodo_pago ?? 'Efectivo',
                    'productos' => $venta->detalles->map(function($detalle) {
                        $nombreProducto = 'Producto no disponible';
                        
                        if ($detalle->producto) {
                            $nombreProducto = $detalle->producto->nombre_producto ?? 'Sin nombre';
                        } else if ($detalle->producto_id) {
                            $nombreProducto = 'Producto #' . $detalle->producto_id . ' (eliminado)';
                        }
                        
                        return [
                            'producto_id' => $detalle->producto_id,
                            'nombre' => $nombreProducto,
                            'cantidad' => (int) $detalle->cantidad,
                            'precio_unitario' => (float) $detalle->precio_unitario,
                            'subtotal' => (float) $detalle->subtotal,
                        ];
                    })
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $historial
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error al obtener historial: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un cliente específico
     */
    public function show($id)
    {
        try {
            $cliente = Cliente::find($id);

            if (!$cliente) {
                return $this->notFoundResponse('Cliente no encontrado');
            }

            $ventas = Venta::where('cliente_id', $id)
                ->where('estado_venta', '!=', 'Cancelado')
                ->get();
            
            $totalCompras = $ventas->count();
            $totalGastado = $ventas->sum('total_venta'); // ⭐ Usar columna real

            $cliente->estadisticas = [
                'total_compras' => $totalCompras,
                'total_gastado' => round($totalGastado, 2),
            ];

            return $this->successResponse($cliente);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error al obtener cliente: ' . $e->getMessage());
            return $this->notFoundResponse('Cliente no encontrado');
        }
    }

    /**
     * Crear nuevo cliente
     */
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nombre' => 'required|string|max:100',
        'dni' => 'nullable|string|max:20',
        'telefono' => 'required|string|max:9',
        'email' => 'nullable|email|max:100',
        'direccion' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $cliente = Cliente::create([
            'nombre_cliente' => $request->nombre,
            'dni' => $request->dni,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'direccion' => $request->direccion,
        ]);

        // ✅ Devolver directamente el modelo creado
        return response()->json([
            'success' => true,
            'data' => $cliente,
            'message' => 'Cliente registrado exitosamente'
        ], 201);

    } catch (\Exception $e) {
        \Log::error('❌ Error al crear cliente: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al crear cliente',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Actualizar cliente
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return $this->notFoundResponse('Cliente no encontrado');
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:100',
            'dni' => 'nullable|string|max:20',
            'telefono' => 'string|max:9',
            'email' => 'nullable|email|max:50|unique:clientes,email,' . $id . ',cliente_id',
            'direccion' => 'nullable|string',
            'contacto' => 'nullable|string|max:50',
            'preferencias' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $datosActualizar = [];
        
        if ($request->has('nombre')) {
            $datosActualizar['nombre_cliente'] = $request->nombre;
        }
        if ($request->has('dni')) {
            $datosActualizar['dni'] = $request->dni;
        }
        if ($request->has('telefono')) {
            $datosActualizar['telefono'] = $request->telefono;
        }
        if ($request->has('email')) {
            $datosActualizar['email'] = $request->email;
        }
        if ($request->has('direccion')) {
            $datosActualizar['direccion'] = $request->direccion;
        }
        if ($request->has('contacto')) {
            $datosActualizar['contacto_cliente'] = $request->contacto;
        }
        if ($request->has('preferencias')) {
            $datosActualizar['preferencias_cliente'] = $request->preferencias;
        }

        $cliente->update($datosActualizar);

        return $this->successResponse($cliente, 'Cliente actualizado exitosamente');
    }

    /**
     * Eliminar cliente
     */
    public function destroy($id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return $this->notFoundResponse('Cliente no encontrado');
        }

        if ($cliente->ventas()->count() > 0) {
            return $this->errorResponse(
                'No se puede eliminar un cliente con ventas asociadas',
                400
            );
        }

        $cliente->delete();
        return $this->successResponse(null, 'Cliente eliminado exitosamente');
    }

    /**
     * Buscar cliente por teléfono
     */
    public function buscarPorTelefono($telefono)
    {
        try {
            $cliente = Cliente::where('telefono', $telefono)->first();

            if ($cliente) {
                return response()->json([
                    'success' => true,
                    'data' => $cliente,
                    'message' => 'Cliente encontrado'
                ]);
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Cliente no encontrado'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('❌ Error al buscar cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar cliente por nombre, DNI o teléfono
     */
    public function buscar(Request $request)
{
    try {
        $busqueda = $request->get('q');

        if (!$busqueda) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar un término de búsqueda',
                'data' => []
            ], 400);
        }

        $clientes = Cliente::where(function($query) use ($busqueda) {
            $query->where('nombre_cliente', 'LIKE', "%{$busqueda}%")
                  ->orWhere('dni', 'LIKE', "%{$busqueda}%")
                  ->orWhere('telefono', 'LIKE', "%{$busqueda}%");
        })
        ->limit(10)
        ->get();

        if ($clientes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron clientes',
                'data' => []
            ], 404);
        }

        // ✅ Devolver directamente lo que hay en la base de datos
        return response()->json([
            'success' => true,
            'message' => 'Clientes encontrados',
            'data' => $clientes
        ]);

    } catch (\Exception $e) {
        \Log::error('❌ Error al buscar cliente: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al buscar cliente',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Obtener clientes frecuentes
     */
    public function clientesFrecuentes()
    {
        $clientes = Cliente::withCount(['ventas' => function($query) {
            $query->where('estado_venta', '!=', 'Cancelado');
        }])
        ->having('ventas_count', '>=', 3)
        ->orderBy('ventas_count', 'desc')
        ->limit(10)
        ->get();

        $clientesConTotal = $clientes->map(function($cliente) {
            $ventas = Venta::where('cliente_id', $cliente->cliente_id)
                ->where('estado_venta', '!=', 'Cancelado')
                ->get();
            
            $totalGastado = $ventas->sum('total_venta'); // ⭐ Usar columna real
            
            return [
                'cliente_id' => $cliente->cliente_id,
                'nombre_cliente' => $cliente->nombre_cliente,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
                'total_compras' => $cliente->ventas_count,
                'total_gastado' => round($totalGastado, 2),
            ];
        });

        return $this->successResponse($clientesConTotal);
    }

    /**
     * Actualizar preferencias del cliente
     */
    public function actualizarPreferencias(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return $this->notFoundResponse('Cliente no encontrado');
        }

        $validator = Validator::make($request->all(), [
            'preferencias' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $cliente->preferencias_cliente = $request->preferencias;
        $cliente->save();

        return $this->successResponse($cliente, 'Preferencias actualizadas exitosamente');
    }
    
}