<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mve_rfc_consulta', function (Blueprint $table) {
            $table->text('razon_social')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mve_rfc_consulta', function (Blueprint $table) {
            $table->text('razon_social')->nullable(false)->change();
        });
    }
};
