<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunicaciones', function (Blueprint $table) {
            $table->id('comunicacion_id');
            $table->unsignedBigInteger('cliente_id');
            $table->string('medio_comunicacion', 30);
            $table->text('mensaje_comunicacion');
            $table->timestamp('fecha_envio_comunicacion')->useCurrent();
            $table->timestamps();

            $table->foreign('cliente_id')
                  ->references('cliente_id')
                  ->on('clientes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicaciones');
    }
};