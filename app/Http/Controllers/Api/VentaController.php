<?php

namespace App\Http\Controllers\Api;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        \Log::info('📦 Ventas encontradas:', [
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
     * 📸 SUBIR COMPROBANTE DE PAGO A CLOUDINARY
     */
    public function subirComprobante(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'comprobante' => 'required|image|mimes:jpeg,png,jpg,gif,webp,pdf|max:10240', // 10MB
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
                return $this->notFoundResponse('Venta no encontrada');
            }

            if ($request->hasFile('comprobante')) {
                $comprobante = $request->file('comprobante');
                
                // ✅ SUBIR A CLOUDINARY
                $uploadedFile = Cloudinary::upload(
                    $comprobante->getRealPath(),
                    [
                        'folder' => 'laneria-mariano/comprobantes',
                        'resource_type' => 'auto', // Acepta imágenes y PDFs
                        'transformation' => [
                            'width' => 1200,
                            'height' => 1600,
                            'crop' => 'limit',
                            'quality' => 'auto'
                        ]
                    ]
                );

                $url = $uploadedFile->getSecurePath();
                $publicId = $uploadedFile->getPublicId();

                // Actualizar venta con comprobante
                $venta->comprobante_pago = $url;
                if ($request->filled('codigo_operacion')) {
                    $venta->codigo_operacion = $request->codigo_operacion;
                }
                $venta->save();

                Log::info('✅ Comprobante subido a Cloudinary:', [
                    'venta_id' => $venta->venta_id,
                    'url' => $url,
                    'public_id' => $publicId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Comprobante subido exitosamente',
                    'data' => [
                        'comprobante_url' => $url,
                        'public_id' => $publicId,
                        'venta' => $this->mapearVenta($venta)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se recibió ningún comprobante'
            ], 400);

        } catch (\Exception $e) {
            Log::error('❌ Error al subir comprobante: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir comprobante: ' . $e->getMessage()
            ], 500);
        }
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
     * Registrar nueva venta (POS / Carrito)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|exists:clientes,cliente_id',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,producto_id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:Efectivo,Yape,Plin,Transferencia,Tarjeta',
            'canal_venta' => 'nullable|in:Tienda física,WhatsApp,Redes sociales,Web,Teléfono,Otro',
            'direccion_envio' => 'nullable|string|max:255',
            'telefono_contacto' => 'nullable|string|max:20',
            'observaciones' => 'nullable|string|max:500',
            'descuento' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        DB::beginTransaction();
        try {
            // Verificar stock disponible
            foreach ($request->items as $item) {
                $producto = Producto::find($item['producto_id']);
                
                if (!$producto) {
                    return $this->errorResponse("Producto ID {$item['producto_id']} no encontrado", 404);
                }

                if ($producto->stock_disponible < $item['cantidad']) {
                    return $this->errorResponse(
                        "Stock insuficiente para {$producto->nombre_producto}. Disponible: {$producto->stock_disponible}, Solicitado: {$item['cantidad']}",
                        400
                    );
                }
            }

            // Calcular totales
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['precio_unitario'] * $item['cantidad'];
            }

            $descuento = $request->descuento ?? 0;
            $total = $subtotal - $descuento;

            // Crear venta
            $venta = Venta::create([
                'cliente_id' => $request->cliente_id,
                'fecha_venta' => now(),
                'estado_venta' => $request->canal_venta === 'Tienda física' ? 'Completado' : 'Pendiente',
                'total_venta' => $total,
                'metodo_pago' => $request->metodo_pago,
                'canal_venta' => $request->canal_venta ?? 'Web',
                'direccion_envio' => $request->direccion_envio,
                'telefono_contacto' => $request->telefono_contacto,
                'observaciones' => $request->observaciones,
            ]);

            // Crear detalles de venta
            foreach ($request->items as $item) {
                DetalleVenta::create([
                    'venta_id' => $venta->venta_id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['precio_unitario'] * $item['cantidad'],
                ]);

                // Si es venta física, descontar stock inmediatamente
                if ($request->canal_venta === 'Tienda física') {
                    $producto = Producto::find($item['producto_id']);
                    $producto->stock_disponible -= $item['cantidad'];
                    $producto->save();
                }
            }

            DB::commit();

            $venta->load(['cliente', 'detalles.producto']);

            return $this->createdResponse(
                $this->mapearVenta($venta),
                'Venta registrada exitosamente'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al registrar venta: " . $e->getMessage());
            return $this->errorResponse('Error al registrar venta: ' . $e->getMessage(), 500);
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
     * ⭐ MAPEAR VENTA - VERSIÓN CORREGIDA Y COMPLETA
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

        Log::info('🔍 MAPEAR VENTA DEBUG:', [
            'venta_id' => $venta->venta_id,
            'tiene_cliente' => $venta->cliente ? 'SÍ' : 'NO',
            'cliente_nombre' => $venta->cliente->nombre_cliente ?? 'NULL',
            'cliente_email' => $venta->cliente->email ?? 'NULL',
            'cantidad_detalles' => $venta->detalles->count(),
            'detalles_ids' => $venta->detalles->pluck('detalle_venta_id')->toArray(),
        ]);

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