<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_personalizados', function (Blueprint $table) {
            $table->id('pedido_personalizado_id');
            $table->unsignedBigInteger('cliente_id');
            $table->text('detalles');
            $table->timestamp('fecha')->useCurrent();
            $table->enum('estado', ['Solicitado', 'En Proceso', 'Listo', 'Entregado'])->default('Solicitado');
            $table->timestamps();

            $table->foreign('cliente_id')
                  ->references('cliente_id')
                  ->on('clientes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_personalizados');
    }
};