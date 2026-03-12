<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vucem_error_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('applicant_id')->nullable();

            // Servicio VUCEM involucrado
            $table->string('servicio', 30); // MV_ENVIO, MV_CONSULTA, DIGITALIZACION, DIGITALIZACION_CONSULTA

            // Clasificación del error de red
            $table->string('tipo_error', 50); // TIMEOUT, CONNECTION_REFUSED, SSL_ERROR, DNS_ERROR, CURL_ERROR

            // Texto crudo de curl_error() para diagnóstico
            $table->text('curl_error_raw')->nullable();

            $table->timestamps();

            // Índices para consultas de diagnóstico
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['servicio', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('applicant_id')->references('id')->on('mv_client_applicants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vucem_error_logs');
    }
};
