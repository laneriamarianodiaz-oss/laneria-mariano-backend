<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id('detalle_venta_id');
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('venta_id')
                  ->references('venta_id')
                  ->on('ventas')
                  ->onDelete('cascade');

            $table->foreign('producto_id')
                  ->references('producto_id')
                  ->on('productos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};