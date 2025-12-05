<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    use HasFactory;

    protected $table = 'comprobantes';
    protected $primaryKey = 'comprobante_id';

    protected $fillable = [
        'venta_id',
        'numero_comprobante',
        'tipo_comprobante',
        'fecha_emision',
        'monto_total'
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'monto_total' => 'decimal:2',
    ];

    /**
     * Relación: Un comprobante pertenece a una venta
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id', 'venta_id');
    }

    /**
     * Generar número de comprobante automáticamente
     */
    public static function generarNumeroComprobante($tipo)
    {
        $prefijo = match($tipo) {
            'Boleta' => 'BOL',
            'Factura' => 'FAC',
            'Recibo' => 'REC',
            default => 'COM'
        };

        $ultimo = self::where('tipo_comprobante', $tipo)
                      ->orderBy('comprobante_id', 'desc')
                      ->first();

        $numero = $ultimo ? ((int)substr($ultimo->numero_comprobante, -6)) + 1 : 1;

        return $prefijo . '-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}