<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id('inventario_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(5);
            $table->timestamp('ultima_actualizacion')->useCurrent();
            $table->timestamps();

            $table->foreign('producto_id')
                  ->references('producto_id')
                  ->on('productos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};