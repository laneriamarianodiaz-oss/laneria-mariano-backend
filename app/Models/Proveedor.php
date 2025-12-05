<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';  // ✅ PLURAL (según tu diccionario)
    protected $primaryKey = 'proveedor_id';
       public $timestamps = true;  // ✅ CORREGIDO: SÍ tiene 

    protected $fillable = [
        'nombre',
        'contacto',
        'telefono',
        'email',
        'direccion',
        'fecha_registro'
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    /**
     * Relación: Un proveedor tiene muchos productos
     */
    public function productos()
    {
        return $this->hasMany(Producto::class, 'proveedor_id', 'proveedor_id');
    }
}