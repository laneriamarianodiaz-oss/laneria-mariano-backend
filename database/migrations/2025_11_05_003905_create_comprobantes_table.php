<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id('comprobante_id');
            $table->unsignedBigInteger('venta_id');
            $table->string('numero_comprobante', 20)->unique();
            $table->enum('tipo_comprobante', ['Boleta', 'Factura', 'Recibo']);
            $table->timestamp('fecha_emision')->useCurrent();
            $table->decimal('monto_total', 10, 2);
            $table->timestamps();

            $table->foreign('venta_id')
                  ->references('venta_id')
                  ->on('ventas')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};