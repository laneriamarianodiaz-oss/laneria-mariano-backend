<?php

namespace App\Http\Controllers\Api;

use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductoController extends BaseController
{
    /**
     * 🔹 Listar TODOS los productos para PANEL ADMIN (incluye inactivos)
     */
    public function indexAdmin(Request $request)
    {
        try {
            $query = Producto::query();
            
            // Búsqueda
            if ($request->has('buscar') || $request->has('search')) {
                $search = $request->buscar ?? $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('codigo_producto', 'LIKE', "%{$search}%")
                      ->orWhere('nombre_producto', 'LIKE', "%{$search}%");
                });
            }
            
            // Filtros
            if ($request->has('tipo')) {
                $query->where('tipo_de_producto', $request->tipo);
            }
            
            if ($request->has('color')) {
                $query->where('color_producto', $request->color);
            }

            if ($request->has('categoria')) {
                $query->where('categoria', $request->categoria);
            }

            if ($request->has('estado')) {
                $query->where('estado_producto', $request->estado);
            }
            
            // Paginación
            $perPage = $request->get('per_page', 15);
            $productos = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            // Mapear respuesta
            $productosMapeados = $productos->map(function($producto) {
                return $this->mapearProducto($producto);
            });

            \Log::info('✅ Productos ADMIN obtenidos:', [
                'total' => $productos->total(),
                'activos' => Producto::where('estado_producto', 'Activo')->count(),
                'inactivos' => Producto::where('estado_producto', 'Inactivo')->count()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $productosMapeados,
                    'current_page' => $productos->currentPage(),
                    'last_page' => $productos->lastPage(),
                    'per_page' => $productos->perPage(),
                    'total' => $productos->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error indexAdmin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar productos públicos (SOLO ACTIVOS - para catálogo)
     */
    public function index(Request $request)
    {
        try {
            $query = Producto::where('estado_producto', 'Activo');
            
            // Búsqueda
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('codigo_producto', 'LIKE', "%{$search}%")
                      ->orWhere('nombre_producto', 'LIKE', "%{$search}%");
                });
            }
            
            // Filtros
            if ($request->has('tipo')) {
                $query->where('tipo_de_producto', $request->tipo);
            }
            
            if ($request->has('color')) {
                $query->where('color_producto', $request->color);
            }
            
            // Solo productos con stock
            if ($request->has('en_stock') && $request->en_stock) {
                $query->where('stock_disponible', '>', 0);
            }
            
            $productos = $query->get();
            $productosMapeados = $productos->map(function($producto) {
                return $this->mapearProducto($producto);
            });
            
            return response()->json([
                'success' => true,
                'data' => $productosMapeados
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error al obtener productos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un producto por ID
     */
    public function show($id)
    {
        $producto = Producto::with(['proveedor', 'inventario'])->find($id);

        if (!$producto) {
            return $this->notFoundResponse('Producto no encontrado');
        }

        return $this->successResponse($this->mapearProducto($producto));
    }

    /**
     * ✅ CREAR PRODUCTO - RECIBE JSON CON imagen_url DE CLOUDINARY
     */
    public function store(Request $request)
    {
        try {
            Log::info('📦 Datos recibidos para crear producto:', $request->all());

            $validator = Validator::make($request->all(), [
                'codigo_producto' => 'required|string|max:50|unique:productos,codigo_producto',
                'nombre_producto' => 'required|string|max:100',
                'tipo_de_producto' => 'required|string|max:50',
                'categoria' => 'nullable|string|max:50',
                'color_producto' => 'nullable|string|max:50',
                'talla_producto' => 'nullable|string|max:20',
                'precio_producto' => 'required|numeric|min:0',
                'stock_disponible' => 'required|integer|min:0',
                'stock_minimo' => 'nullable|integer|min:0',
                'descripcion' => 'nullable|string',
                'proveedor_id' => 'nullable|exists:proveedores,proveedor_id',
                'estado_producto' => 'nullable|in:Activo,Inactivo',
                'imagen_url' => 'nullable|string|max:500', // ⭐ URL de Cloudinary
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validación fallida:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Crear producto con la URL de Cloudinary
                $producto = Producto::create([
                    'codigo_producto' => $request->codigo_producto,
                    'nombre_producto' => $request->nombre_producto,
                    'tipo_de_producto' => $request->tipo_de_producto,
                    'categoria' => $request->categoria,
                    'color_producto' => $request->color_producto,
                    'talla_producto' => $request->talla_producto,
                    'precio_producto' => $request->precio_producto,
                    'stock_disponible' => $request->stock_disponible,
                    'stock_minimo' => $request->stock_minimo ?? 0,
                    'descripcion' => $request->descripcion,
                    'imagen_url' => $request->imagen_url, // ⭐ URL DE CLOUDINARY
                    'proveedor_id' => $request->proveedor_id,
                    'estado_producto' => $request->estado_producto ?? 'Activo',
                ]);

                DB::commit();

                Log::info('✅ Producto creado:', [
                    'id' => $producto->producto_id,
                    'imagen_url' => $producto->imagen_url
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Producto creado exitosamente',
                    'data' => $this->mapearProducto($producto)
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al crear producto: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el producto',
                    'error' => $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * ✅ ACTUALIZAR PRODUCTO - RECIBE JSON CON imagen_url
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return $this->notFoundResponse('Producto no encontrado');
        }

        Log::info('📦 Datos recibidos para actualizar producto:', $request->all());

        $validator = Validator::make($request->all(), [
            'codigo_producto' => 'string|max:50|unique:productos,codigo_producto,' . $id . ',producto_id',
            'nombre_producto' => 'string|max:100',
            'tipo_de_producto' => 'string|max:50',
            'categoria' => 'nullable|string|max:50',
            'color_producto' => 'nullable|string|max:50',
            'talla_producto' => 'nullable|string|max:20',
            'precio_producto' => 'numeric|min:0',
            'stock_disponible' => 'integer|min:0',
            'stock_minimo' => 'integer|min:0',
            'descripcion' => 'nullable|string',
            'proveedor_id' => 'nullable|exists:proveedores,proveedor_id',
            'estado_producto' => 'in:Activo,Inactivo',
            'imagen_url' => 'nullable|string|max:500' // ⭐ URL de Cloudinary
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        DB::beginTransaction();
        try {
            $producto->update($request->only([
                'codigo_producto', 
                'nombre_producto',
                'tipo_de_producto',
                'categoria',
                'color_producto',
                'talla_producto',
                'precio_producto',
                'stock_disponible',
                'stock_minimo',
                'descripcion',
                'imagen_url', // ⭐ URL DE CLOUDINARY
                'proveedor_id',
                'estado_producto',
            ]));

            DB::commit();
            $producto->load(['proveedor', 'inventario']);

            Log::info('✅ Producto actualizado:', ['id' => $producto->producto_id]);

            return $this->successResponse($this->mapearProducto($producto), 'Producto actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar producto: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar producto
     */
    public function destroy($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return $this->notFoundResponse('Producto no encontrado');
        }

        if ($producto->stock_disponible > 0) {
            return $this->errorResponse('No se puede eliminar un producto con stock disponible', 400);
        }

        if ($producto->detalleVentas()->count() > 0) {
            $producto->estado_producto = 'Inactivo';
            $producto->save();
            return $this->successResponse(null, 'Producto marcado como inactivo (tiene historial de ventas)');
        }

        DB::beginTransaction();
        try {
            $producto->inventario()->delete();
            $producto->delete();
            DB::commit();

            return $this->successResponse(null, 'Producto eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al eliminar producto: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener productos por categoría/tipo
     */
    public function porTipo($tipo)
    {
        $productos = Producto::with(['proveedor', 'inventario'])
            ->where('tipo_de_producto', $tipo)
            ->where('estado_producto', 'Activo')
            ->orderBy('nombre_producto')
            ->get();

        return $this->successResponse($productos->map(function($p) {
            return $this->mapearProducto($p);
        }));
    }

    /**
     * Obtener tipos de productos únicos
     */
    public function tipos()
    {
        $tipos = Producto::select('tipo_de_producto')
            ->distinct()
            ->orderBy('tipo_de_producto')
            ->pluck('tipo_de_producto');

        return $this->successResponse($tipos);
    }

    /**
     * Obtener colores disponibles
     */
    public function colores()
    {
        $colores = Producto::select('color_producto')
            ->whereNotNull('color_producto')
            ->distinct()
            ->orderBy('color_producto')
            ->pluck('color_producto');

        return $this->successResponse($colores);
    }

    /**
     * 🔧 Mapear producto a formato frontend
     */
    private function mapearProducto($producto)
    {
        return [
            'id' => $producto->producto_id,
            'producto_id' => $producto->producto_id,
            'codigo_lana' => $producto->codigo_producto,
            'codigo_producto' => $producto->codigo_producto,
            'nombre' => $producto->nombre_producto,
            'nombre_producto' => $producto->nombre_producto,
            'tipo_lana' => $producto->tipo_de_producto,
            'tipo_de_producto' => $producto->tipo_de_producto,
            'categoria' => $producto->categoria,
            'color' => $producto->color_producto,
            'color_producto' => $producto->color_producto,
            'talla_tamano' => $producto->talla_producto,
            'talla_producto' => $producto->talla_producto,
            'precio_unitario' => (float) $producto->precio_producto,
            'precio_producto' => (float) $producto->precio_producto,
            'precio' => (float) $producto->precio_producto,
            'stock_disponible' => $producto->stock_disponible,
            'stock_actual' => $producto->stock_disponible,
            'stock_minimo' => $producto->stock_minimo ?? 0,
            'descripcion' => $producto->descripcion,
            'imagen_url' => $producto->imagen_url,
            'imagenes' => $producto->imagen_url ? [$producto->imagen_url] : [],
            'estado' => $producto->estado_producto,
            'estado_producto' => $producto->estado_producto,
            'proveedor' => $producto->proveedor,
            'created_at' => $producto->created_at,
            'updated_at' => $producto->updated_at,
        ];
    }
}