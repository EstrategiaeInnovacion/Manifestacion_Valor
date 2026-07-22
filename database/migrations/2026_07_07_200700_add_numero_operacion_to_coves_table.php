<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coves', function (Blueprint $table) {
            $table->string('numero_operacion', 30)->nullable()->after('edocument')->index();
            $table->unsignedTinyInteger('intentos_consulta')->default(0)->after('numero_operacion');
        });
    }

    public function down(): void
    {
        Schema::table('coves', function (Blueprint $table) {
            $table->dropIndex(['numero_operacion']);
            $table->dropColumn(['numero_operacion', 'intentos_consulta']);
        });
    }
};
