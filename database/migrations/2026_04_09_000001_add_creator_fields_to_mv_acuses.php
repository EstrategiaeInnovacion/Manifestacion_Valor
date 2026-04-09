<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Agrega created_by_user_id y folio_interno a mv_acuses.
     * Ambas columnas son nullable para no afectar registros históricos.
     * Sin FK constraint para evitar riesgo en datos históricos con borrados previos.
     */
    public function up(): void
    {
        Schema::table('mv_acuses', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('datos_manifestacion_id');
            $table->string('folio_interno', 30)->nullable()->after('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('mv_acuses', function (Blueprint $table) {
            $table->dropColumn(['created_by_user_id', 'folio_interno']);
        });
    }
};
