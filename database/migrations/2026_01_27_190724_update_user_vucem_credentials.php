<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Configurar credenciales dummy para el primer usuario
        $user = \App\Models\User::first();
        if ($user) {
            $user->update([
                'rfc' => 'NET070608EM9', // RFC de prueba
                'webservice_key' => 'clave_webservice_dummy_123456', // Clave dummy
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Limpiar credenciales
        $user = \App\Models\User::first();
        if ($user) {
            $user->update([
                'rfc' => null,
                'webservice_key' => null,
            ]);
        }
    }
};
