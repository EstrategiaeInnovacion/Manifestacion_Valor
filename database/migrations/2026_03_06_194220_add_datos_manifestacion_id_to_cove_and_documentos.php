<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mv_informacion_cove', function (Blueprint $table) {
            $table->unsignedBigInteger('datos_manifestacion_id')->nullable()->after('applicant_id');
            $table->foreign('datos_manifestacion_id')
                  ->references('id')->on('mv_datos_manifestacion')
                  ->onDelete('cascade');
        });

        Schema::table('mv_documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('datos_manifestacion_id')->nullable()->after('applicant_id');
            $table->foreign('datos_manifestacion_id')
                  ->references('id')->on('mv_datos_manifestacion')
                  ->onDelete('cascade');
        });

        // Backfill: link existing records by applicant_id match
        DB::statement("
            UPDATE mv_informacion_cove ic
            JOIN mv_datos_manifestacion dm ON dm.applicant_id = ic.applicant_id
            SET ic.datos_manifestacion_id = dm.id
            WHERE ic.datos_manifestacion_id IS NULL
        ");

        DB::statement("
            UPDATE mv_documentos d
            JOIN mv_datos_manifestacion dm ON dm.applicant_id = d.applicant_id
            SET d.datos_manifestacion_id = dm.id
            WHERE d.datos_manifestacion_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('mv_informacion_cove', function (Blueprint $table) {
            $table->dropForeign(['datos_manifestacion_id']);
            $table->dropColumn('datos_manifestacion_id');
        });

        Schema::table('mv_documentos', function (Blueprint $table) {
            $table->dropForeign(['datos_manifestacion_id']);
            $table->dropColumn('datos_manifestacion_id');
        });
    }
};
