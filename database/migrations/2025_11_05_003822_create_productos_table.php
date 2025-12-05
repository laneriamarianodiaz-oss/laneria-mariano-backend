<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id('producto_id');
            $table->string('nombre_producto', 100);
            $table->string('tipo_de_producto', 50);
            $table->string('color_producto', 50)->nullable();
            $table->string('talla_producto', 20)->nullable();
            $table->decimal('precio_producto', 10, 2);
            $table->integer('stock_disponible')->default(0);
            $table->text('descripcion')->nullable();
            $table->string('imagen_url', 255)->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->enum('estado_producto', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamps();

            $table->foreign('proveedor_id')
                  ->references('proveedor_id')
                  ->on('proveedores')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};