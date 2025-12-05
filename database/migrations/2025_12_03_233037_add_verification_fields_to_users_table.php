<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
Schema::table('users', function (Blueprint $table) {
    $table->string('verification_code', 6)->nullable();
    $table->timestamp('verification_code_expires_at')->nullable();
    $table->text('password_reset_token')->nullable();
    $table->timestamp('password_reset_expires_at')->nullable();
    $table->boolean('email_verified')->default(false);
});
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'verification_code_expires_at',
                // ❌ NO eliminar email_verified_at porque es de Laravel
                'password_reset_token',
                'password_reset_expires_at'
            ]);
        });
    }
};