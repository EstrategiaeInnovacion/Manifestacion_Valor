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
        Schema::table('mv_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('mv_documentos', 'estado_vucem')) {
                $table->dropColumn('estado_vucem');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mv_documentos', function (Blueprint $table) {
            $table->string('estado_vucem')->nullable()->after('folio_edocument');
        });
    }
};
