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
        Schema::create('mv_acuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('datos_manifestacion_id')->nullable();
            $table->string('folio_manifestacion', 50)->unique(); // Folio generado por VUCEM
            $table->string('numero_pedimento', 20)->nullable();
            $table->string('numero_cove', 20)->nullable();
            $table->text('xml_enviado'); // XML firmado enviado a VUCEM
            $table->text('xml_respuesta'); // XML de respuesta de VUCEM
            $table->text('acuse_pdf')->nullable(); // PDF del acuse si VUCEM lo genera
            $table->string('status', 50)->default('ENVIADO'); // ENVIADO, ACEPTADO, RECHAZADO
            $table->text('mensaje_vucem')->nullable(); // Mensaje de respuesta de VUCEM
            $table->timestamp('fecha_envio');
            $table->timestamp('fecha_respuesta')->nullable();
            $table->timestamps();
            
            $table->foreign('applicant_id')->references('id')->on('mv_client_applicants')->onDelete('cascade');
            $table->foreign('datos_manifestacion_id')->references('id')->on('mv_datos_manifestacion')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mv_acuses');
    }
};
