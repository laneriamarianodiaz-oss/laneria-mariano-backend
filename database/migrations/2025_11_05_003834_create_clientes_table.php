<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('cliente_id');
            $table->string('nombre_cliente', 100);
            $table->string('contacto_cliente', 50)->nullable();
            $table->string('telefono', 9);
            $table->string('email', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->text('preferencias_cliente')->nullable();
            $table->text('historial_compras')->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};