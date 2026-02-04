<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega nuevos estados: guardado, enviado, rechazado
     */
    public function up(): void
    {
        // Modificar ENUM en mv_datos_manifestacion
        DB::statement("ALTER TABLE mv_datos_manifestacion MODIFY COLUMN status ENUM('borrador', 'completado', 'guardado', 'enviado', 'rechazado') DEFAULT 'borrador'");
        
        // Modificar ENUM en mv_informacion_cove
        DB::statement("ALTER TABLE mv_informacion_cove MODIFY COLUMN status ENUM('borrador', 'completado', 'guardado', 'enviado', 'rechazado') DEFAULT 'borrador'");
        
        // Modificar ENUM en mv_documentos si existe columna status
        if (Schema::hasColumn('mv_documentos', 'status')) {
            DB::statement("ALTER TABLE mv_documentos MODIFY COLUMN status ENUM('borrador', 'completado', 'guardado', 'enviado', 'rechazado') DEFAULT 'borrador'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero actualizar cualquier registro con nuevo status a 'completado'
        DB::table('mv_datos_manifestacion')
            ->whereIn('status', ['guardado', 'enviado', 'rechazado'])
            ->update(['status' => 'completado']);
            
        DB::table('mv_informacion_cove')
            ->whereIn('status', ['guardado', 'enviado', 'rechazado'])
            ->update(['status' => 'completado']);
        
        // Restaurar ENUM original
        DB::statement("ALTER TABLE mv_datos_manifestacion MODIFY COLUMN status ENUM('borrador', 'completado') DEFAULT 'borrador'");
        DB::statement("ALTER TABLE mv_informacion_cove MODIFY COLUMN status ENUM('borrador', 'completado') DEFAULT 'borrador'");
        
        if (Schema::hasColumn('mv_documentos', 'status')) {
            DB::table('mv_documentos')
                ->whereIn('status', ['guardado', 'enviado', 'rechazado'])
                ->update(['status' => 'completado']);
            DB::statement("ALTER TABLE mv_documentos MODIFY COLUMN status ENUM('borrador', 'completado') DEFAULT 'borrador'");
        }
    }
};
