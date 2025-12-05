<?php

namespace App\Http\Controllers\Api;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProveedorController extends BaseController
{
    /**
     * Listar todos los proveedores
     */
    public function index(Request $request)
    {
        $query = Proveedor::withCount('productos');

        // Búsqueda
        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('contacto', 'like', "%{$buscar}%")
                  ->orWhere('telefono', 'like', "%{$buscar}%");
            });
        }

        // Ordenamiento
        $orderBy = $request->get('order_by', 'nombre');
        $orderDir = $request->get('order_dir', 'asc');
        $query->orderBy($orderBy, $orderDir);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $proveedores = $query->paginate($perPage);

        return $this->successResponse($proveedores);
    }

    /**
     * Obtener un proveedor específico
     */
    public function show($id)
    {
        $proveedor = Proveedor::with(['productos' => function($query) {
            $query->where('estado_producto', 'Activo');
        }])->find($id);

        if (!$proveedor) {
            return $this->notFoundResponse('Proveedor no encontrado');
        }

        return $this->successResponse($proveedor);
    }

    /**
     * Crear nuevo proveedor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'contacto' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:9',
            'email' => 'nullable|email|max:50',
            'direccion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $proveedor = Proveedor::create([
            'nombre' => $request->nombre,
            'contacto' => $request->contacto,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'direccion' => $request->direccion,
            'fecha_registro' => now(),
        ]);

        return $this->createdResponse($proveedor, 'Proveedor registrado exitosamente');
    }

    /**
     * Actualizar proveedor
     */
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return $this->notFoundResponse('Proveedor no encontrado');
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:50',
            'contacto' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:9',
            'email' => 'nullable|email|max:50',
            'direccion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $proveedor->update($request->only([
            'nombre',
            'contacto',
            'telefono',
            'email',
            'direccion',
        ]));

        return $this->successResponse($proveedor, 'Proveedor actualizado exitosamente');
    }

    /**
     * Eliminar proveedor
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return $this->notFoundResponse('Proveedor no encontrado');
        }

        // Verificar si tiene productos asociados
        if ($proveedor->productos()->count() > 0) {
            return $this->errorResponse(
                'No se puede eliminar un proveedor con productos asociados',
                400
            );
        }

        $proveedor->delete();

        return $this->successResponse(null, 'Proveedor eliminado exitosamente');
    }

    /**
     * Obtener productos de un proveedor
     */
    public function productos($id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return $this->notFoundResponse('Proveedor no encontrado');
        }

        $productos = $proveedor->productos()
            ->with('inventario')
            ->where('estado_producto', 'Activo')
            ->get();

        return $this->successResponse([
            'proveedor' => [
                'proveedor_id' => $proveedor->proveedor_id,
                'nombre' => $proveedor->nombre,
            ],
            'total_productos' => $productos->count(),
            'productos' => $productos,
        ]);
    }
}