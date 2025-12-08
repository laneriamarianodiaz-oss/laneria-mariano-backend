<?php

namespace App\Http\Controllers\Api;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\Comprobante;
use App\Models\Carrito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class VentaController extends BaseController
{
    /**
     * Listar todas las ventas (ADMIN)
     */
    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'detalles.producto']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado_venta', $request->estado);
        }

        if ($request->has('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('venta_id', 'like', "%{$buscar}%")
                  ->orWhereHas('cliente', function($cq) use ($buscar) {
                      $cq->where('nombre_cliente', 'like', "%{$buscar}%")
                         ->orWhere('email', 'like', "%{$buscar}%");
                  });
            });
        }

        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha_venta', [
                $request->fecha_inicio,
                $request->fecha_fin
            ]);
        }

        // Ordenamiento
        $orderBy = $request->get('order_by', 'fecha_venta');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $ventas = $query->paginate($perPage);

        // Mapear datos
        $ventas->getCollection()->transform(function ($venta) {
            return $this->mapearVenta($venta);
        });

        return $this->successResponse($ventas);
    }

    /**
     * ⭐ LISTAR PEDIDOS ONLINE (Para admin/punto de venta)
     */
    public function listarPedidos(Request $request)
    {
        $query = Venta::with(['cliente', 'detalles.producto', 'comprobante']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado_venta', $request->estado);
        }

        if ($request->has('venta_id')) {
            $query->where('venta_id', $request->venta_id);
        }

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        // Ordenar por más recientes
        $ventas = $query->orderBy('fecha_venta', 'desc')->get();

        Log::info('📦 Ventas encontradas:', [
            'cantidad' => $ventas->count(),
            'primera_venta_id' => $ventas->first()?->venta_id,
            'tiene_cliente' => $ventas->first()?->cliente ? 'SÍ' : 'NO',
            'email_cliente' => $ventas->first()?->cliente?->email,
            'cantidad_detalles' => $ventas->first()?->detalles?->count()
        ]);

        $ventasMapeadas = $ventas->map(function ($venta) {
            return $this->mapearVenta($venta);
        });

        return $this->successResponse($ventasMapeadas);
    }

    /**
     * Obtener MIS PEDIDOS (CLIENTE AUTENTICADO)
     */
    public function misPedidos(Request $request)
    {
        $cliente = $request->user()->cliente;
        
        if (!$cliente) {
            return $this->errorResponse('Usuario no tiene perfil de cliente', 404);
        }

        $query = Venta::with(['detalles.producto'])
            ->where('cliente_id', $cliente->cliente_id);

        // Filtro por estado (opcional)
        if ($request->has('estado')) {
            $query->where('estado_venta', $request->estado);
        }

        $ventas = $query->orderBy('fecha_venta', 'desc')->get();

        $ventasMapeadas = $ventas->map(function ($venta) {
            return $this->mapearVenta($venta);
        });

        return $this->successResponse($ventasMapeadas);
    }

    /**
     * Obtener una venta específica
     */
    public function show($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'comprobante'])
            ->find($id);

        if (!$venta) {
            return $this->notFoundResponse('Venta no encontrada');
        }

        return $this->successResponse($this->mapearVenta($venta));
    }

    /**
     * ACTUALIZAR ESTADO DE PEDIDO (ADMIN)
     */
    public function actualizarEstado(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:Pendiente,Confirmado,En Proceso,Enviado,Entregado,Completado,Cancelado',
            'observaciones' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $venta = Venta::with('detalles.producto')->find($id);

        if (!$venta) {
            return $this->notFoundResponse('Venta no encontrada');
        }

        DB::beginTransaction();
        try {
            $estadoAnterior = $venta->estado_venta;
            $nuevoEstado = $request->estado;

            // Validar transiciones de estado
            if (!$this->validarTransicionEstado($estadoAnterior, $nuevoEstado)) {
                return $this->errorResponse("No se puede cambiar de '{$estadoAnterior}' a '{$nuevoEstado}'", 400);
            }

            // Si se confirma el pedido, verificar y descontar stock
            if ($estadoAnterior === 'Pendiente' && $nuevoEstado === 'Confirmado') {
                foreach ($venta->detalles as $detalle) {
                    $producto = $detalle->producto;
                    
                    if ($producto->stock_disponible < $detalle->cantidad) {
                        DB::rollBack();
                        return $this->errorResponse(
                            "Stock insuficiente para {$producto->nombre_producto}. Disponible: {$producto->stock_disponible}",
                            400
                        );
                    }

                    $producto->stock_disponible -= $detalle->cantidad;
                    $producto->save();
                }
            }

            // Si se cancela un pedido confirmado, devolver stock
            if (in_array($estadoAnterior, ['Confirmado', 'En Proceso']) && $nuevoEstado === 'Cancelado') {
                foreach ($venta->detalles as $detalle) {
                    $producto = $detalle->producto;
                    $producto->stock_disponible += $detalle->cantidad;
                    $producto->save();
                }
            }

            // Actualizar estado
            $venta->estado_venta = $nuevoEstado;
            
            // Registrar cambio en observaciones
            $observacionesActuales = $venta->observaciones ?? '';
            $timestamp = now()->format('d/m/Y H:i');
            $nuevaObservacion = "\n[{$timestamp}] {$estadoAnterior} → {$nuevoEstado}";
            
            if ($request->filled('observaciones')) {
                $nuevaObservacion .= ": {$request->observaciones}";
            }
            
            $venta->observaciones = $observacionesActuales . $nuevaObservacion;
            $venta->save();

            DB::commit();

            Log::info("✅ Estado de venta actualizado", [
                'venta_id' => $venta->venta_id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado
            ]);

            return $this->successResponse([
                'venta' => $this->mapearVenta($venta->fresh(['cliente', 'detalles.producto'])),
                'estado_anterior' => $estadoAnterior,
                'estado_actual' => $venta->estado_venta,
            ], 'Estado actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Error al actualizar estado: " . $e->getMessage());
            return $this->errorResponse('Error al cambiar estado: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ⭐ GUARDAR URL DE COMPROBANTE (CORREGIDO PARA CLOUDINARY)
     */
    public function subirComprobante(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comprobante_pago' => 'required|string|max:1000', // Aumentado para URLs largas
            'codigo_operacion' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $venta = Venta::find($id);
        
        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        // ⭐ LIMPIAR Y VALIDAR URL DE CLOUDINARY
        $comprobanteUrl = $request->comprobante_pago;
        
        Log::info('📸 Actualizando comprobante:', [
            'venta_id' => $venta->venta_id,
            'url_recibida' => $comprobanteUrl
        ]);
        
        // Asegurarse de que tenga el protocolo correcto
        if (!str_starts_with($comprobanteUrl, 'http://') && 
            !str_starts_with($comprobanteUrl, 'https://')) {
            $comprobanteUrl = 'https://' . $comprobanteUrl;
            Log::info('⚠️ Se agregó https:// al comprobante');
        }
        
        // Validar que sea una URL de Cloudinary válida
        if (!str_contains($comprobanteUrl, 'cloudinary.com')) {
            return response()->json([
                'success' => false,
                'message' => 'La URL debe ser de Cloudinary'
            ], 400);
        }

        // Guardar URL limpia
        $venta->comprobante_pago = $comprobanteUrl;
        
        if ($request->filled('codigo_operacion')) {
            $venta->codigo_operacion = $request->codigo_operacion;
        }
        
        $venta->save();

        Log::info('✅ Comprobante actualizado:', [
            'venta_id' => $venta->venta_id,
            'comprobante_guardado' => $venta->comprobante_pago,
            'codigo_operacion' => $venta->codigo_operacion
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante guardado exitosamente',
            'data' => [
                'venta_id' => $venta->venta_id,
                'comprobante_pago' => $venta->comprobante_pago,
                'codigo_operacion' => $venta->codigo_operacion
            ]
        ]);
    }

    /**
     * CANCELAR PEDIDO (CLIENTE o ADMIN)
     */
    public function cancelar(Request $request, $id)
    {
        $venta = Venta::with('detalles.producto')->find($id);

        if (!$venta) {
            return $this->notFoundResponse('Venta no encontrada');
        }

        // Verificar permisos
        $usuario = $request->user();
        $cliente = $usuario->cliente;
        
        if ($cliente && $venta->cliente_id !== $cliente->cliente_id) {
            return $this->errorResponse('No tienes permiso para cancelar este pedido', 403);
        }

        // Solo se pueden cancelar pedidos Pendientes o Confirmados
        if (!in_array($venta->estado_venta, ['Pendiente', 'Confirmado'])) {
            return $this->errorResponse(
                "No se puede cancelar un pedido en estado '{$venta->estado_venta}'", 
                400
            );
        }

        DB::beginTransaction();
        try {
            $estadoAnterior = $venta->estado_venta;

            // Si estaba confirmado, devolver stock
            if ($estadoAnterior === 'Confirmado') {
                foreach ($venta->detalles as $detalle) {
                    $producto = $detalle->producto;
                    $producto->stock_disponible += $detalle->cantidad;
                    $producto->save();
                }
            }

            // Actualizar estado
            $venta->estado_venta = 'Cancelado';
            
            $motivo = $request->filled('motivo') ? $request->motivo : 'Cancelado por el cliente';
            $timestamp = now()->format('d/m/Y H:i');
            $venta->observaciones = ($venta->observaciones ?? '') . "\n[{$timestamp}] Cancelado: {$motivo}";
            $venta->save();

            DB::commit();

            return $this->successResponse(
                $this->mapearVenta($venta->fresh(['cliente', 'detalles.producto'])), 
                'Pedido cancelado exitosamente'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al cancelar pedido: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ⭐ REGISTRAR NUEVA VENTA (CORREGIDO PARA CLOUDINARY)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'metodo_pago' => 'required|string',
                'direccion_envio' => 'nullable|string|max:255',
                'telefono_contacto' => 'nullable|string|max:20',
                'observaciones' => 'nullable|string',
                'comprobante_pago' => 'nullable|string|max:1000',  // ⭐ Aumentado
                'codigo_operacion' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $cliente = $user->cliente;

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene perfil de cliente asociado'
                ], 403);
            }

            // Obtener carrito del usuario
            $carrito = Carrito::where('cliente_id', $cliente->cliente_id)->first();

            if (!$carrito || $carrito->detalles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Calcular totales
                $subtotal = 0;
                foreach ($carrito->detalles as $detalle) {
                    $subtotal += $detalle->cantidad * $detalle->precio_unitario;
                }

                // ⭐ LIMPIAR URL DE COMPROBANTE SI VIENE
                $comprobanteUrl = null;
                if ($request->filled('comprobante_pago')) {
                    $comprobanteUrl = $request->comprobante_pago;
                    
                    // Log para debug
                    Log::info('📸 Comprobante recibido (original):', [
                        'url_original' => $comprobanteUrl,
                        'longitud' => strlen($comprobanteUrl),
                        'empieza_con_http' => str_starts_with($comprobanteUrl, 'http'),
                    ]);
                    
                    // Asegurarse de que tenga el protocolo correcto
                    if (!str_starts_with($comprobanteUrl, 'http://') && 
                        !str_starts_with($comprobanteUrl, 'https://')) {
                        $comprobanteUrl = 'https://' . $comprobanteUrl;
                        Log::info('⚠️ Se agregó https:// al comprobante');
                    }
                    
                    // Validar que sea una URL de Cloudinary
                    if (!str_contains($comprobanteUrl, 'cloudinary.com')) {
                        DB::rollBack();
                        Log::error('❌ URL no es de Cloudinary:', ['url' => $comprobanteUrl]);
                        return response()->json([
                            'success' => false,
                            'message' => 'La URL del comprobante debe ser de Cloudinary'
                        ], 400);
                    }
                    
                    Log::info('✅ URL de comprobante limpia:', ['url_limpia' => $comprobanteUrl]);
                }

                // Crear venta
                $venta = new Venta();
                $venta->cliente_id = $cliente->cliente_id;
                $venta->user_id = $user->id;
                $venta->subtotal = $subtotal;
                $venta->descuento = 0;
                $venta->total_venta = $subtotal;
                $venta->metodo_pago = $request->metodo_pago;
                $venta->estado_venta = 'Pendiente';
                $venta->canal_venta = 'Web';
                $venta->direccion_envio = $request->direccion_envio;
                $venta->telefono_contacto = $request->telefono_contacto;
                $venta->observaciones = $request->observaciones;
                
                // ⭐ GUARDAR URL LIMPIA DE CLOUDINARY
                $venta->comprobante_pago = $comprobanteUrl;
                
                if ($request->filled('codigo_operacion')) {
                    $venta->codigo_operacion = $request->codigo_operacion;
                }

                $venta->save();

                // Generar número de venta
                $venta->numero_venta = 'V-' . str_pad($venta->venta_id, 6, '0', STR_PAD_LEFT);
                $venta->save();

                // Crear detalles de venta desde el carrito
                foreach ($carrito->detalles as $detalleCarrito) {
                    $detalleVenta = new DetalleVenta();
                    $detalleVenta->venta_id = $venta->venta_id;
                    $detalleVenta->producto_id = $detalleCarrito->producto_id;
                    $detalleVenta->cantidad = $detalleCarrito->cantidad;
                    $detalleVenta->precio_unitario = $detalleCarrito->precio_unitario;
                    $detalleVenta->subtotal = $detalleCarrito->cantidad * $detalleCarrito->precio_unitario;
                    $detalleVenta->save();
                }

                // Vaciar carrito
                $carrito->detalles()->delete();

                DB::commit();

                Log::info('✅ Venta creada exitosamente:', [
                    'venta_id' => $venta->venta_id,
                    'cliente_id' => $cliente->cliente_id,
                    'total' => $venta->total_venta,
                    'comprobante_url_guardada' => $venta->comprobante_pago,
                    'codigo_operacion' => $venta->codigo_operacion,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Venta creada exitosamente',
                    'data' => [
                        'venta_id' => $venta->venta_id,
                        'numero_venta' => $venta->numero_venta,
                        'total' => $venta->total_venta,
                        'estado' => $venta->estado_venta,
                        'comprobante_pago' => $venta->comprobante_pago,
                        'codigo_operacion' => $venta->codigo_operacion
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('❌ Error en transacción de venta:', [
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('❌ Error al crear venta:', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar transiciones de estado
     */
    private function validarTransicionEstado($estadoActual, $nuevoEstado)
    {
        $transicionesValidas = [
            'Pendiente' => ['Confirmado', 'Cancelado'],
            'Confirmado' => ['En Proceso', 'Cancelado'],
            'En Proceso' => ['Enviado', 'Cancelado'],
            'Enviado' => ['Entregado', 'Cancelado'],
            'Entregado' => ['Completado'],
            'Completado' => [],
            'Cancelado' => [],
        ];

        if (!isset($transicionesValidas[$estadoActual])) {
            return false;
        }

        return in_array($nuevoEstado, $transicionesValidas[$estadoActual]);
    }

    /**
     * ⭐ MAPEAR VENTA - VERSIÓN COMPLETA
     */
    private function mapearVenta($venta)
    {
        // Forzar carga de relaciones si no están presentes
        if (!$venta->relationLoaded('cliente')) {
            $venta->load('cliente');
        }
        if (!$venta->relationLoaded('detalles')) {
            $venta->load('detalles.producto');
        }

        return [
            'venta_id' => $venta->venta_id,
            'numero_venta' => $venta->numero_venta,
            'cliente_id' => $venta->cliente_id,
            
            // Campos directos del cliente
            'cliente_nombre' => $venta->cliente->nombre_cliente ?? 'Cliente',
            'cliente_telefono' => $venta->cliente->telefono ?? $venta->telefono_contacto ?? 'No especificado',
            
            // Objeto cliente completo
            'cliente' => [
                'cliente_id' => $venta->cliente->cliente_id ?? null,
                'nombre_cliente' => $venta->cliente->nombre_cliente ?? 'Cliente',
                'correo' => $venta->cliente->email ?? 'No especificado',
                'email' => $venta->cliente->email ?? 'No especificado',
                'telefono' => $venta->cliente->telefono ?? 'No especificado',
            ],
            
            'fecha_venta' => $venta->fecha_venta,
            'estado_venta' => $venta->estado_venta,
            'subtotal' => (float) ($venta->subtotal ?? $venta->total_venta),
            'descuento' => (float) ($venta->descuento ?? 0),
            'total' => (float) $venta->total_venta,
            'total_venta' => (float) $venta->total_venta,
            'metodo_pago' => $venta->metodo_pago ?? 'No especificado',
            'canal_venta' => $venta->canal_venta ?? 'Web',
            'direccion_envio' => $venta->direccion_envio,
            'telefono_contacto' => $venta->telefono_contacto,
            'observaciones' => $venta->observaciones,
            
            // ✅ Comprobante de pago (URL de Cloudinary)
            'comprobante_pago' => $venta->comprobante_pago,
            'codigo_operacion' => $venta->codigo_operacion,
            
            // Items con imágenes
            'items' => $venta->detalles->map(function ($detalle) {
                $producto = $detalle->producto;
                return [
                    'producto_id' => $detalle->producto_id,
                    'nombre_producto' => $producto->nombre_producto ?? 'Producto eliminado',
                    'imagen_url' => $producto->imagen_url ?? null,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => (float) $detalle->precio_unitario,
                    'subtotal' => (float) $detalle->subtotal,
                ];
            })->toArray(),

            // Alias de items para compatibilidad
            'productos' => $venta->detalles->map(function ($detalle) {
                $producto = $detalle->producto;
                return [
                    'producto_id' => $detalle->producto_id,
                    'nombre' => $producto->nombre_producto ?? 'Producto eliminado',
                    'nombre_producto' => $producto->nombre_producto ?? 'Producto eliminado',
                    'imagen_url' => $producto->imagen_url ?? null,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => (float) $detalle->precio_unitario,
                    'subtotal' => (float) $detalle->subtotal,
                ];
            })->toArray(),
        ];
    }
}