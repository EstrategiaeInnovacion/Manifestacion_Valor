<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina los campos de webservice de las tablas users y mv_client_applicants.
     * Ya no se usan ya que la clave se pide manualmente al firmar.
     */
    public function up(): void
    {
        // Eliminar webservice_key de users
        if (Schema::hasColumn('users', 'webservice_key')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('webservice_key');
            });
        }

        // Eliminar ws_file_upload_key de mv_client_applicants
        if (Schema::hasColumn('mv_client_applicants', 'ws_file_upload_key')) {
            Schema::table('mv_client_applicants', function (Blueprint $table) {
                $table->dropColumn('ws_file_upload_key');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar webservice_key en users
        if (!Schema::hasColumn('users', 'webservice_key')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('webservice_key')->nullable()->after('rfc');
            });
        }

        // Restaurar ws_file_upload_key en mv_client_applicants
        if (!Schema::hasColumn('mv_client_applicants', 'ws_file_upload_key')) {
            Schema::table('mv_client_applicants', function (Blueprint $table) {
                $table->text('ws_file_upload_key')->nullable();
            });
        }
    }
};
