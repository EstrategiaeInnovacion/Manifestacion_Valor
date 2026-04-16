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
        Schema::create('cove_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('applicant_id')->index(); // Vínculo principal con la empresa/solicitante
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Usuario que lo creó
            $table->string('tipo_operacion')->nullable(); // TOL, etc.
            $table->string('patente_aduanal')->nullable();
            
            // Estados y seguimiento VUCEM
            $table->string('status')->default('borrador'); // borrador, guardado, procesando_vucem, enviado, rechazado, error
            $table->string('numero_operacion')->nullable();
            $table->string('e_document')->nullable();
            
            // El JSON encriptado con todo el detalle de facturas, mercancías, emisor, etc.
            $table->longText('payload')->nullable();

            // Detalles XML generados
            $table->longText('xml_enviado')->nullable();
            $table->longText('xml_respuesta')->nullable(); // Guardar el acuse devuelto
            $table->longText('acuse_pdf')->nullable(); // Contenido base64 del pdf devuelto por EDocumentConsulta
            
            $table->timestamps();

            // Opcional: Relación a applicant (dependiendo del tipo en su tabla)
            // Ya que MvClientApplicant puede tener incrementos, indexamos.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cove_documents');
    }
};
