<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_carritos', function (Blueprint $table) {
            $table->id('detalle_carrito_id');
            $table->unsignedBigInteger('carrito_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('carrito_id')
                  ->references('carrito_id')
                  ->on('carritos')
                  ->onDelete('cascade');

            $table->foreign('producto_id')
                  ->references('producto_id')
                  ->on('productos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_carritos');
    }
};
