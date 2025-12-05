<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use HasFactory;

    protected $table = 'carritos';
    protected $primaryKey = 'carrito_id';

    protected $fillable = [
        'cliente_id',
        'estado',
        'fecha_creacion'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
    ];

    /**
     * Relación: Un carrito pertenece a un cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    /**
     * Relación: Un carrito tiene muchos detalles
     */
    public function detalles()
    {
        return $this->hasMany(DetalleCarrito::class, 'carrito_id', 'carrito_id');
    }

    /**
     * Scope: Carritos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'Activo');
    }

    /**
     * Calcular total del carrito
     */
    public function calcularTotal()
    {
        return $this->detalles->sum('subtotal');
    }

    /**
     * Obtener cantidad de items en el carrito
     */
    public function getCantidadItemsAttribute()
    {
        return $this->detalles->sum('cantidad');
    }
}