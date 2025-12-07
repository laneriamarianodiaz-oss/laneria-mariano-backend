<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';
    protected $primaryKey = 'venta_id';
    
    // ✅ DESACTIVAR TIMESTAMPS si la tabla NO tiene created_at y updated_at
    public $timestamps = false; // ← AGREGADO

    protected $fillable = [
        'cliente_id',
        'fecha_venta', 
        'estado_venta',
        'total_venta',
        'metodo_pago',
        'canal_venta',
        'observaciones',
        'direccion_envio',
        'telefono_contacto',
        'comprobante_pago',
        'codigo_operacion'
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'total_venta' => 'decimal:2',
    ];

    // Appends para que siempre incluya estos campos virtuales
    protected $appends = ['numero_venta', 'total', 'subtotal', 'descuento'];

    /**
     * Generar número de venta automáticamente
     */
    public function getNumeroVentaAttribute()
    {
        return 'V-' . str_pad($this->venta_id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Accessor para compatibilidad - total
     */
    public function getTotalAttribute()
    {
        return $this->total_venta ?? 0;
    }

    /**
     * Accessor para subtotal (calcular desde detalles si están cargados)
     */
    public function getSubtotalAttribute()
    {
        // Solo calcular si los detalles ya están cargados
        if ($this->relationLoaded('detalles') && $this->detalles) {
            return $this->detalles->sum('subtotal');
        }
        
        // Si no hay detalles cargados, retornar el total_venta como subtotal
        return $this->total_venta ?? 0;
    }

    /**
     * Accessor para descuento
     */
    public function getDescuentoAttribute()
    {
        // Solo calcular si hay detalles cargados
        if ($this->relationLoaded('detalles') && $this->detalles) {
            $subtotal = $this->detalles->sum('subtotal');
            return max(0, $subtotal - $this->total_venta);
        }
        
        return 0;
    }

    /**
     * Relación: Una venta pertenece a un cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    /**
     * Relación: Una venta tiene muchos detalles
     */
    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id', 'venta_id');
    }

    /**
     * Relación: Una venta tiene un comprobante
     */
    public function comprobante()
    {
        return $this->hasOne(Comprobante::class, 'venta_id', 'venta_id');
    }

    /**
     * Scope: Ventas completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado_venta', 'Completado');
    }

    /**
     * Scope: Ventas pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_venta', 'Pendiente');
    }

    /**
     * Scope: Ventas confirmadas
     */
    public function scopeConfirmadas($query)
    {
        return $query->where('estado_venta', 'Confirmado');
    }

    /**
     * Scope: Ventas canceladas
     */
    public function scopeCanceladas($query)
    {
        return $query->where('estado_venta', 'Cancelado');
    }

    /**
     * Scope: Ventas por rango de fechas
     */
    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_venta', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope: Ventas por método de pago
     */
    public function scopePorMetodoPago($query, $metodo)
    {
        return $query->where('metodo_pago', $metodo);
    }

    /**
     * Scope: Ventas por canal
     */
    public function scopePorCanal($query, $canal)
    {
        return $query->where('canal_venta', $canal);
    }

    /**
     * Calcular total de la venta
     */
    public function calcularTotal()
    {
        return $this->detalles->sum('subtotal');
    }
}