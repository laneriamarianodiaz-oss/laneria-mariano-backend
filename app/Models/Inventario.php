<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventarios';
    protected $primaryKey = 'inventario_id';
    public $timestamps = true;

    protected $fillable = [
        'producto_id',
        'stock_actual',
        'stock_minimo',
        'ultima_actualizacion'
    ];

    protected $casts = [
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'ultima_actualizacion' => 'datetime',
    ];

    /**
     * Relación: Un inventario pertenece a un producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'producto_id');
    }

    /**
     * Scope: Inventarios con stock bajo
     */
    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    /**
     * Scope: Inventarios sin stock
     */
    public function scopeSinStock($query)
    {
        return $query->where('stock_actual', 0);
    }

    /**
     * Accessor: Verificar si tiene stock bajo
     */
    public function getStockBajoAttribute()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    /**
     * Accessor: Verificar si está sin stock
     */
    public function getSinStockAttribute()
    {
        return $this->stock_actual == 0;
    }
}