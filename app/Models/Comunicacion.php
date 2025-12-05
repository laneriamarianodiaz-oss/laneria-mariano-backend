<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comunicacion extends Model
{
    use HasFactory;

    protected $table = 'comunicaciones';
    protected $primaryKey = 'comunicacion_id';

    protected $fillable = [
        'cliente_id',
        'medio_comunicacion',
        'mensaje_comunicacion',
        'fecha_envio_comunicacion'
    ];

    protected $casts = [
        'fecha_envio_comunicacion' => 'datetime',
    ];

    /**
     * Relación: Una comunicación pertenece a un cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }
}