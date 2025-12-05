<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCarrito extends Model
{
    use HasFactory;

    protected $table = 'detalle_carritos';
    protected $primaryKey = 'detalle_carrito_id';

    protected $fillable = [
        'carrito_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación: Un detalle pertenece a un carrito
     */
    public function carrito()
    {
        return $this->belongsTo(Carrito::class, 'carrito_id', 'carrito_id');
    }

    /**
     * Relación: Un detalle pertenece a un producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'producto_id');
    }

    /**
     * Calcular subtotal automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detalle) {
            $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;
        });
    }
}