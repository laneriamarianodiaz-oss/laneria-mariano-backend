<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizacionService
{
    /**
     * Cachear dashboard por 5 minutos
     */
    public static function getDashboardCacheado()
    {
        return Cache::remember('dashboard_data', 300, function () {
            return [
                'ventas_hoy' => DB::table('ventas')
                    ->whereDate('fecha_venta', today())
                    ->where('estado_venta', 'Completada')
                    ->sum('total_venta'),
                    
                'ventas_mes' => DB::table('ventas')
                    ->whereMonth('fecha_venta', now()->month)
                    ->whereYear('fecha_venta', now()->year)
                    ->where('estado_venta', 'Completada')
                    ->sum('total_venta'),
                    
                'productos_stock_bajo' => DB::table('inventarios')
                    ->whereRaw('stock_actual <= stock_minimo')
                    ->count(),
            ];
        });
    }

    /**
     * Limpiar caché cuando hay cambios importantes
     */
    public static function limpiarCache()
    {
        Cache::forget('dashboard_data');
        Cache::forget('productos_mas_vendidos');
        Cache::forget('inventario_resumen');
    }
}