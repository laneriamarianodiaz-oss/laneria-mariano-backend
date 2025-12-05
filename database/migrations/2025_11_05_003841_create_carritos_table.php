<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id('carrito_id');
            $table->unsignedBigInteger('cliente_id');
            $table->enum('estado', ['Activo', 'Convertido', 'Abandonado'])->default('Activo');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamps();

            $table->foreign('cliente_id')
                  ->references('cliente_id')
                  ->on('clientes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carritos');
    }
};
