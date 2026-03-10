<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mv_datos_manifestacion', function (Blueprint $table) {
            $table->string('folio_interno', 20)->nullable()->unique()->after('applicant_id')
                  ->comment('Folio único interno por MVE, generado automáticamente al crear');
        });

        // Backfill: asignar folio_interno a registros existentes que no lo tengan
        $registros = DB::table('mv_datos_manifestacion')->whereNull('folio_interno')->orderBy('id')->get();
        foreach ($registros as $reg) {
            $folio = 'MVE-' . date('Y', strtotime($reg->created_at)) . '-' . str_pad($reg->id, 5, '0', STR_PAD_LEFT);
            DB::table('mv_datos_manifestacion')->where('id', $reg->id)->update(['folio_interno' => $folio]);
        }
    }

    public function down(): void
    {
        Schema::table('mv_datos_manifestacion', function (Blueprint $table) {
            $table->dropColumn('folio_interno');
        });
    }
};
