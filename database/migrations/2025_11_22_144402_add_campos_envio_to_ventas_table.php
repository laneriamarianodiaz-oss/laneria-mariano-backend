<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('direccion_envio')->nullable()->after('observaciones');
            $table->string('telefono_contacto', 20)->nullable()->after('direccion_envio');
            $table->string('comprobante_pago')->nullable()->after('telefono_contacto');
            $table->string('codigo_operacion', 50)->nullable()->after('comprobante_pago');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['direccion_envio', 'telefono_contacto', 'comprobante_pago', 'codigo_operacion']);
        });
    }
};