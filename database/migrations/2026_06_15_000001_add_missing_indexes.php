<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mv_datos_manifestacion', function (Blueprint $table) {
            if (!$this->indexExists('mv_datos_manifestacion', 'mv_datos_manifestacion_created_by_user_id_index')) {
                $table->index('created_by_user_id');
            }
        });

        Schema::table('mv_acuses', function (Blueprint $table) {
            if (!$this->indexExists('mv_acuses', 'mv_acuses_created_by_user_id_index')) {
                $table->index('created_by_user_id');
            }
            if (!$this->indexExists('mv_acuses', 'mv_acuses_datos_manifestacion_id_index')) {
                $table->index('datos_manifestacion_id');
            }
        });

        Schema::table('edocuments_registrados', function (Blueprint $table) {
            if (!$this->indexExists('edocuments_registrados', 'edocuments_registrados_numero_operacion_index')) {
                $table->index('numero_operacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mv_datos_manifestacion', function (Blueprint $table) {
            $table->dropIndex(['created_by_user_id']);
        });

        Schema::table('mv_acuses', function (Blueprint $table) {
            $table->dropIndex(['created_by_user_id']);
            $table->dropIndex(['datos_manifestacion_id']);
        });

        Schema::table('edocuments_registrados', function (Blueprint $table) {
            $table->dropIndex(['numero_operacion']);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
