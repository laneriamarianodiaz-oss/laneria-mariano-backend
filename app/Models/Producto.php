<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    protected $primaryKey = 'producto_id';
    public $timestamps = true;

    protected $fillable = [
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
        'imagen_url',
        'proveedor_id',
        'estado_producto',
    ];

    protected $casts = [
        'precio_producto' => 'decimal:2',
        'stock_disponible' => 'integer',
        'stock_minimo' => 'integer',
        'fecha_creacion' => 'datetime',
    ];

    /**
     * Relación: Un producto pertenece a un proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'proveedor_id');
    }

    /**
     * Relación: Un producto tiene un inventario
     */
    public function inventario()
    {
        return $this->hasOne(Inventario::class, 'producto_id', 'producto_id');
    }

    /**
     * Relación: Un producto puede estar en muchos detalles de venta
     */
    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id', 'producto_id');
    }

    /**
     * Relación: Un producto puede estar en muchos detalles de carrito
     */
    public function detalleCarritos()
    {
        return $this->hasMany(DetalleCarrito::class, 'producto_id', 'producto_id');
    }

    /**
     * Scope: Productos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado_producto', 'Activo');
    }

    /**
     * Scope: Productos con stock disponible
     */
    public function scopeConStock($query)
    {
        return $query->where('stock_disponible', '>', 0);
    }

    /**
     * Accessor: Verificar si tiene stock
     */
    public function getTieneStockAttribute()
    {
        return $this->stock_disponible > 0;
    }
}