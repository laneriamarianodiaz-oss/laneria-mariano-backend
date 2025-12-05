<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('venta_id');
            $table->unsignedBigInteger('cliente_id');
            $table->timestamp('fecha_venta')->useCurrent();
            $table->enum('estado_venta', ['Pendiente', 'Completada', 'Cancelada'])->default('Pendiente');
            $table->decimal('total_venta', 10, 2);
            $table->enum('metodo_pago', ['Efectivo', 'Transferencia', 'Yape', 'Plin']);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')
                  ->references('cliente_id')
                  ->on('clientes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};