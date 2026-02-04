<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cambia las columnas rfc y webservice_key de string a text
     * para soportar valores encriptados que exceden el límite de 255 caracteres.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cambiar rfc de string(255) a text para soportar valores encriptados
            $table->text('rfc')->nullable()->change();
            
            // Cambiar webservice_key de string(255) a text para soportar valores encriptados
            $table->text('webservice_key')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir a string (puede causar pérdida de datos si hay valores largos)
            $table->string('rfc', 255)->nullable()->change();
            $table->string('webservice_key', 255)->nullable()->change();
        });
    }
};
