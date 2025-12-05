<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id('proveedor_id');
            $table->string('nombre', 50);
            $table->string('contacto', 50)->nullable();
            $table->string('telefono', 9)->nullable();
            $table->string('email', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};