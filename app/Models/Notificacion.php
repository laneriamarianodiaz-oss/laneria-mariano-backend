<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'notificacion_id';
    public $timestamps = true;

    protected $fillable = [
        'cliente_id',
        'mensaje',
        'fecha_envio',
        'estado',
        'tipo'
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
    ];

    /**
     * Relación: Una notificación pertenece a un cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    /**
     * Scope: Notificaciones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    /**
     * Scope: Notificaciones enviadas
     */
    public function scopeEnviadas($query)
    {
        return $query->where('estado', 'Enviada');
    }
}