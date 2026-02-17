<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edocuments_registrados', function (Blueprint $table) {
            $table->unsignedBigInteger('applicant_id')->nullable()->after('id');
            $table->string('numero_operacion', 30)->nullable()->after('folio_edocument');
            $table->string('tipo_documento', 10)->nullable()->after('numero_operacion');
            $table->string('nombre_documento', 100)->nullable()->after('tipo_documento');

            $table->foreign('applicant_id')
                  ->references('id')
                  ->on('mv_client_applicants')
                  ->onDelete('set null');
            
            $table->index('applicant_id');
            $table->index('numero_operacion');
        });
    }

    public function down(): void
    {
        Schema::table('edocuments_registrados', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropIndex(['applicant_id']);
            $table->dropIndex(['numero_operacion']);
            $table->dropColumn(['applicant_id', 'numero_operacion', 'tipo_documento', 'nombre_documento']);
        });
    }
};
