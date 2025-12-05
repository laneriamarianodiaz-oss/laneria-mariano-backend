<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ CAMBIAR 'producto' por 'productos' (PLURAL)
        if (!Schema::hasColumn('productos', 'stock_minimo')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->integer('stock_minimo')->default(5)->after('stock_disponible');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'stock_minimo')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('stock_minimo');
            });
        }
    }
};