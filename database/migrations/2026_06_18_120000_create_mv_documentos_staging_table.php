<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mv_documentos_staging', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            $table->foreignId('datos_manifestacion_id')->nullable()
                ->constrained('mv_datos_manifestacion')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('tipo_documento', 10);
            $table->string('nombre_documento', 45);
            $table->string('original_filename');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_vucem_compliant')->default(false);
            $table->boolean('was_converted')->default(false);
            $table->string('rfc_consulta', 13)->nullable();

            $table->string('numero_operacion', 30)->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['applicant_id', 'datos_manifestacion_id']);
            $table->index('numero_operacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mv_documentos_staging');
    }
};
