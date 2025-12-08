<?php

namespace App\Http\Controllers\Api;

use App\Models\Carrito;
use App\Models\DetalleCarrito;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarritoController extends BaseController
{
    /**
     * Obtener carrito activo del usuario autenticado
     */
    public function obtenerCarrito(Request $request)
    {
        $cliente = $request->user()->cliente;

        if (!$cliente) {
            return $this->errorResponse('Usuario no tiene perfil de cliente', 400);
        }

        $carrito = Carrito::with(['detalles.producto'])
            ->where('cliente_id', $cliente->cliente_id)
            ->where('estado', 'Activo')
            ->first();

        if (!$carrito) {
            // Crear carrito si no existe
            $carrito = Carrito::create([
                'cliente_id' => $cliente->cliente_id,
                'estado' => 'Activo',
                'fecha_creacion' => now(),
            ]);
            $carrito->load('detalles.producto');
        }

        return $this->successResponse([
            'carrito' => $carrito,
            'total_items' => $carrito->cantidad_items ?? 0,
            'total' => $carrito->calcularTotal(),
        ]);
    }

    /**
     * Agregar producto al carrito
     */
    public function agregarProducto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,producto_id',
            'cantidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $cliente = $request->user()->cliente;
        if (!$cliente) {
            return $this->errorResponse('Usuario no tiene perfil de cliente', 400);
        }

        $producto = Producto::find($request->producto_id);

        // Verificar stock disponible
        if ($producto->stock_disponible < $request->cantidad) {
            return $this->errorResponse(
                "Stock insuficiente. Disponible: {$producto->stock_disponible}",
                400
            );
        }

        DB::beginTransaction();
        try {
            // Obtener o crear carrito
            $carrito = Carrito::firstOrCreate(
                [
                    'cliente_id' => $cliente->cliente_id,
                    'estado' => 'Activo'
                ],
                [
                    'fecha_creacion' => now()
                ]
            );

            // Verificar si el producto ya está en el carrito
            $detalleExistente = DetalleCarrito::where('carrito_id', $carrito->carrito_id)
                ->where('producto_id', $request->producto_id)
                ->first();

            if ($detalleExistente) {
                // Actualizar cantidad
                $nuevaCantidad = $detalleExistente->cantidad + $request->cantidad;
                
                if ($producto->stock_disponible < $nuevaCantidad) {
                    DB::rollBack();
                    return $this->errorResponse(
                        "Stock insuficiente para agregar más unidades. Disponible: {$producto->stock_disponible}",
                        400
                    );
                }

                $detalleExistente->cantidad = $nuevaCantidad;
                $detalleExistente->subtotal = $nuevaCantidad * $producto->precio_producto;
                $detalleExistente->save();
            } else {
                // Crear nuevo detalle
                DetalleCarrito::create([
                    'carrito_id' => $carrito->carrito_id,
                    'producto_id' => $request->producto_id,
                    'cantidad' => $request->cantidad,
                    'precio_unitario' => $producto->precio_producto,
                    'subtotal' => $request->cantidad * $producto->precio_producto,
                ]);
            }

            DB::commit();

            $carrito->load('detalles.producto');

            return $this->successResponse([
                'carrito' => $carrito,
                'total_items' => $carrito->cantidad_items ?? 0,
                'total' => $carrito->calcularTotal(),
            ], 'Producto agregado al carrito');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al agregar producto: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar cantidad de un producto en el carrito
     */
    public function actualizarCantidad(Request $request, $detalleId)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $detalle = DetalleCarrito::find($detalleId);

        if (!$detalle) {
            return $this->notFoundResponse('Producto no encontrado en el carrito');
        }

        $producto = $detalle->producto;

        // Verificar stock disponible
        if ($producto->stock_disponible < $request->cantidad) {
            return $this->errorResponse(
                "Stock insuficiente. Disponible: {$producto->stock_disponible}",
                400
            );
        }

        $detalle->cantidad = $request->cantidad;
        $detalle->subtotal = $request->cantidad * $detalle->precio_unitario;
        $detalle->save();

        $carrito = $detalle->carrito;
        $carrito->load('detalles.producto');

        return $this->successResponse([
            'carrito' => $carrito,
            'total_items' => $carrito->cantidad_items ?? 0,
            'total' => $carrito->calcularTotal(),
        ], 'Cantidad actualizada');
    }

    /**
     * Eliminar producto del carrito
     */
    public function eliminarProducto($detalleId)
    {
        $detalle = DetalleCarrito::find($detalleId);

        if (!$detalle) {
            return $this->notFoundResponse('Producto no encontrado en el carrito');
        }

        $carrito = $detalle->carrito;
        $detalle->delete();

        $carrito->load('detalles.producto');

        return $this->successResponse([
            'carrito' => $carrito,
            'total_items' => $carrito->cantidad_items ?? 0,
            'total' => $carrito->calcularTotal(),
        ], 'Producto eliminado del carrito');
    }

    /**
     * Vaciar carrito
     */
    public function vaciarCarrito(Request $request)
    {
        $cliente = $request->user()->cliente;
        
        $carrito = Carrito::where('cliente_id', $cliente->cliente_id)
            ->where('estado', 'Activo')
            ->first();

        if (!$carrito) {
            return $this->notFoundResponse('Carrito no encontrado');
        }

        $carrito->detalles()->delete();
        $carrito->load('detalles.producto');

        return $this->successResponse([
            'carrito' => $carrito,
            'total_items' => 0,
            'total' => 0,
        ], 'Carrito vaciado exitosamente');
    }

    /**
     * ⭐ CREAR VENTA DESDE CARRITO - CLOUDINARY DIRECTO
     */
    public function crearVentaDesdeCarrito(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'metodo_pago' => 'required|in:Efectivo,Transferencia,Yape,Plin',
            'observaciones' => 'nullable|string',
            'direccion_envio' => 'nullable|string|max:255',
            'telefono_contacto' => 'nullable|string|max:20',
            'codigo_operacion' => 'nullable|string|max:50',
            'comprobante_pago' => 'nullable|string|max:1000', // ⭐ URL de Cloudinary
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $cliente = $request->user()->cliente;

        if (!$cliente) {
            return $this->errorResponse('Usuario no tiene perfil de cliente', 404);
        }

        $carrito = Carrito::where('cliente_id', $cliente->cliente_id)
            ->where('estado', 'Activo')
            ->with('detalles.producto')
            ->first();

        if (!$carrito || $carrito->detalles->isEmpty()) {
            return $this->errorResponse('El carrito está vacío', 400);
        }

        DB::beginTransaction();
        try {
            // Verificar stock
            foreach ($carrito->detalles as $detalle) {
                if ($detalle->producto->stock_disponible < $detalle->cantidad) {
                    DB::rollBack();
                    return $this->errorResponse(
                        "Stock insuficiente para {$detalle->producto->nombre_producto}",
                        400
                    );
                }
            }

            // Calcular total
            $total = $carrito->detalles->sum(function ($detalle) {
                return $detalle->precio_unitario * $detalle->cantidad;
            });

            // ⭐ VALIDAR Y LIMPIAR URL DE CLOUDINARY
            $comprobanteUrl = null;
            if ($request->filled('comprobante_pago')) {
                $comprobanteUrl = $request->comprobante_pago;
                
                Log::info('📸 Comprobante recibido:', [
                    'url' => $comprobanteUrl,
                    'longitud' => strlen($comprobanteUrl),
                ]);
                
                // Asegurarse de que tenga el protocolo correcto
                if (!str_starts_with($comprobanteUrl, 'http://') && 
                    !str_starts_with($comprobanteUrl, 'https://')) {
                    $comprobanteUrl = 'https://' . $comprobanteUrl;
                    Log::info('⚠️ Se agregó https:// al comprobante');
                }
                
                // Validar que sea URL de Cloudinary
                if (!str_contains($comprobanteUrl, 'cloudinary.com')) {
                    DB::rollBack();
                    Log::error('❌ URL no es de Cloudinary:', ['url' => $comprobanteUrl]);
                    return $this->errorResponse('El comprobante debe ser una URL de Cloudinary', 400);
                }
                
                Log::info('✅ URL de Cloudinary válida:', ['url' => $comprobanteUrl]);
            }

            // Crear venta
            $venta = Venta::create([
                'cliente_id' => $cliente->cliente_id,
                'fecha_venta' => now(),
                'estado_venta' => 'Pendiente',
                'total_venta' => $total,
                'metodo_pago' => $request->metodo_pago,
                'direccion_envio' => $request->direccion_envio,
                'telefono_contacto' => $request->telefono_contacto,
                'codigo_operacion' => $request->codigo_operacion,
                'comprobante_pago' => $comprobanteUrl, // ⭐ URL COMPLETA DE CLOUDINARY
                'observaciones' => $request->observaciones,
            ]);

            // Crear detalles de venta
            foreach ($carrito->detalles as $detalle) {
                DetalleVenta::create([
                    'venta_id' => $venta->venta_id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->precio_unitario * $detalle->cantidad,
                ]);
            }

            // Vaciar carrito
            $carrito->detalles()->delete();
            $carrito->estado = 'Convertido';
            $carrito->save();

            DB::commit();

            $venta->load(['cliente', 'detalles.producto']);

            Log::info('✅ Pedido creado exitosamente:', [
                'venta_id' => $venta->venta_id,
                'cliente_id' => $cliente->cliente_id,
                'total' => $venta->total_venta,
                'comprobante_pago' => $venta->comprobante_pago,
                'codigo_operacion' => $venta->codigo_operacion,
            ]);

            return $this->createdResponse($venta, 'Pedido realizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al crear pedido:', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return $this->errorResponse('Error al procesar pedido: ' . $e->getMessage(), 500);
        }
    }
}