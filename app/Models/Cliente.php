<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'cliente_id';
    public $timestamps = true;

    protected $fillable = [
        'nombre_cliente',
        'contacto_cliente',
        'telefono',
        'email',
        'direccion',
        'preferencias_cliente',
        'historial_compras',
        'dni',
        'user_id',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    /**
     * Accessors para compatibilidad con frontend
     */
    public function getNombreClieAttribute()
    {
        return $this->nombre_cliente;
    }

    public function getContactoClieAttribute()
    {
        return $this->contacto_cliente;
    }

    public function getPreferenciasClieAttribute()
    {
        return $this->preferencias_cliente;
    }

    /**
     * ✅ Accessor para correo (alias de email)
     */
    public function getCorreoAttribute()
    {
        return $this->email;
    }

    /**
     * Relación con usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con ventas
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id', 'cliente_id');
    }

    /**
     * Relación con carrito
     */
    public function carritos()
    {
        return $this->hasMany(Carrito::class, 'cliente_id', 'cliente_id');
    }
}