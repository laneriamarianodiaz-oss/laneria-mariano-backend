<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índices para productos
        Schema::table('productos', function (Blueprint $table) {
            $table->index('tipo_de_producto');
            $table->index('color_producto');
            $table->index('estado_producto');
            $table->index('proveedor_id');
        });

        // Índices para ventas
        Schema::table('ventas', function (Blueprint $table) {
            $table->index('cliente_id');
            $table->index('fecha_venta');
            $table->index('estado_venta');
            $table->index('metodo_pago');
        });

        // Índices para inventario
        Schema::table('inventarios', function (Blueprint $table) {
            $table->index('producto_id');
            $table->index(['stock_actual', 'stock_minimo']);
        });

        // Índices para clientes
        Schema::table('clientes', function (Blueprint $table) {
            $table->index('telefono');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['tipo_de_producto']);
            $table->dropIndex(['color_producto']);
            $table->dropIndex(['estado_producto']);
            $table->dropIndex(['proveedor_id']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['cliente_id']);
            $table->dropIndex(['fecha_venta']);
            $table->dropIndex(['estado_venta']);
            $table->dropIndex(['metodo_pago']);
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['stock_actual', 'stock_minimo']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['telefono']);
            $table->dropIndex(['email']);
        });
    }
};