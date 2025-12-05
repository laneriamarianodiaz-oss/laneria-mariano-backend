<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoPersonalizado extends Model
{
    use HasFactory;

    protected $table = 'pedidos_personalizados';
    protected $primaryKey = 'pedido_personalizado_id';

    protected $fillable = [
        'cliente_id',
        'detalles',
        'fecha',
        'estado'
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    /**
     * Relación: Un pedido personalizado pertenece a un cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    /**
     * Scope: Pedidos solicitados
     */
    public function scopeSolicitados($query)
    {
        return $query->where('estado', 'Solicitado');
    }

    /**
     * Scope: Pedidos en proceso
     */
    public function scopeEnProceso($query)
    {
        return $query->where('estado', 'En Proceso');
    }

    /**
     * Scope: Pedidos listos
     */
    public function scopeListos($query)
    {
        return $query->where('estado', 'Listo');
    }
}