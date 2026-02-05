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
        // Solo ejecutamos el MODIFY COLUMN si NO estamos en SQLite
        // SQLite acepta los nuevos strings sin necesidad de cambiar el esquema
        if (DB::connection()->getDriverName() !== 'sqlite') {
            
            // Modificar ENUM en mv_datos_manifestacion
            DB::statement("ALTER TABLE mv_datos_manifestacion MODIFY COLUMN status ENUM('borrador', 'completado', 'guardado', 'enviado', 'rechazado') DEFAULT 'borrador'");
            
            // Modificar ENUM en mv_informacion_cove
            DB::statement("ALTER TABLE mv_informacion_cove MODIFY COLUMN status ENUM('borrador', 'completado', 'guardado', 'enviado', 'rechazado') DEFAULT 'borrador'");
            
            // Modificar ENUM en mv_documentos si existe columna status
            if (Schema::hasColumn('mv_documentos', 'status')) {
                DB::statement("ALTER TABLE mv_documentos MODIFY COLUMN status ENUM('borrador', 'completado', 'guardado', 'enviado', 'rechazado') DEFAULT 'borrador'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. La limpieza de datos SI se debe ejecutar en ambos (SQLite y MySQL)
        // para evitar inconsistencias lógicas si haces rollback.
        
        DB::table('mv_datos_manifestacion')
            ->whereIn('status', ['guardado', 'enviado', 'rechazado'])
            ->update(['status' => 'completado']);
            
        DB::table('mv_informacion_cove')
            ->whereIn('status', ['guardado', 'enviado', 'rechazado'])
            ->update(['status' => 'completado']);
        
        if (Schema::hasColumn('mv_documentos', 'status')) {
            DB::table('mv_documentos')
                ->whereIn('status', ['guardado', 'enviado', 'rechazado'])
                ->update(['status' => 'completado']);
        }

        // 2. El ALTER para restringir el ENUM de nuevo solo se ejecuta si NO es SQLite
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE mv_datos_manifestacion MODIFY COLUMN status ENUM('borrador', 'completado') DEFAULT 'borrador'");
            DB::statement("ALTER TABLE mv_informacion_cove MODIFY COLUMN status ENUM('borrador', 'completado') DEFAULT 'borrador'");
            
            if (Schema::hasColumn('mv_documentos', 'status')) {
                DB::statement("ALTER TABLE mv_documentos MODIFY COLUMN status ENUM('borrador', 'completado') DEFAULT 'borrador'");
            }
        }
    }
};