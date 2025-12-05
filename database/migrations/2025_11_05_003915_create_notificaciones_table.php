<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id('notificacion_id');
            $table->unsignedBigInteger('cliente_id');
            $table->string('mensaje', 200);
            $table->timestamp('fecha_envio')->useCurrent();
            $table->enum('estado', ['Enviada', 'Pendiente', 'Error'])->default('Pendiente');
            $table->enum('tipo', ['Pedido Confirmado', 'En Proceso', 'Listo', 'Entregado']);
            $table->timestamps();

            $table->foreign('cliente_id')
                  ->references('cliente_id')
                  ->on('clientes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};