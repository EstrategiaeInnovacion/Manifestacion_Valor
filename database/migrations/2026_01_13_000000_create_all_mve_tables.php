<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migración consolidada que crea todas las tablas del sistema MVE.
     * Estado final limpio — sin migraciones incrementales.
     */
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════════
        // Tabla: mv_client_applicants (Solicitantes/Clientes)
        // ══════════════════════════════════════════════════════════════
        Schema::create('mv_client_applicants', function (Blueprint $table) {
            $table->id();

            // Relación con la tabla users a través del correo electrónico
            $table->string('user_email');
            $table->foreign('user_email')
                  ->references('email')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Datos del Solicitante (TEXT para soportar encriptación)
            $table->text('applicant_rfc');               // RFC del Solicitante (encriptado)
            $table->text('business_name');                // Razón Social (encriptado)

            // Sellos VUCEM (opcionales, encriptados)
            $table->longText('vucem_key_file')->nullable();       // Archivo .key en base64 (encriptado)
            $table->longText('vucem_cert_file')->nullable();      // Archivo .cer en base64 (encriptado)
            $table->text('vucem_password')->nullable();            // Contraseña del sello (encriptada)
            $table->text('vucem_webservice_key')->nullable();      // Clave Web Service VUCEM (encriptada)

            // Consentimiento de privacidad
            $table->boolean('privacy_consent')->default(false);
            $table->timestamp('privacy_consent_at')->nullable();

            $table->timestamps();
        });

        // ══════════════════════════════════════════════════════════════
        // Tabla: mve_rfc_consulta (RFC de consulta para personas vinculadas)
        // ══════════════════════════════════════════════════════════════
        Schema::create('mve_rfc_consulta', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_rfc', 13); // RFC del solicitante (sin encriptar para búsquedas)
            $table->text('rfc_consulta');         // RFC de consulta (encriptado)
            $table->text('razon_social');         // Razón social (encriptado)
            $table->text('tipo_figura');          // Tipo de figura (encriptado)
            $table->timestamps();
            
            $table->index('applicant_rfc');
        });

        // ══════════════════════════════════════════════════════════════
        // Tabla: mv_datos_manifestacion (Datos principales de la Manifestación)
        // ══════════════════════════════════════════════════════════════
        Schema::create('mv_datos_manifestacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            
            $table->enum('status', ['borrador', 'completado', 'guardado', 'enviado', 'rechazado'])->default('borrador');
            
            $table->text('rfc_importador')->nullable();
            $table->text('metodo_valoracion')->nullable();
            $table->text('existe_vinculacion')->nullable();
            $table->text('pedimento')->nullable();
            $table->text('patente')->nullable();
            $table->text('aduana')->nullable();
            $table->text('persona_consulta')->nullable(); // JSON encriptado
            
            $table->timestamps();
            $table->index(['applicant_id', 'status']);
        });

        // ══════════════════════════════════════════════════════════════
        // Tabla: mv_informacion_cove (Información COVE y Valor en Aduana)
        // ══════════════════════════════════════════════════════════════
        Schema::create('mv_informacion_cove', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            
            $table->enum('status', ['borrador', 'completado', 'guardado', 'enviado', 'rechazado'])->default('borrador');
            
            // Información COVE
            $table->text('informacion_cove')->nullable();  // JSON encriptado con array de COVEs
            $table->text('pedimentos')->nullable();         // JSON encriptado con array de pedimentos
            
            // Datos de Valoración
            $table->text('precio_pagado')->nullable();
            $table->text('precio_por_pagar')->nullable();
            $table->text('compenso_pago')->nullable();
            $table->text('incrementables')->nullable();
            $table->text('decrementables')->nullable();
            $table->text('valor_en_aduana')->nullable();    // JSON encriptado con totales calculados
            
            $table->timestamps();
            $table->index(['applicant_id', 'status']);
        });

        // ══════════════════════════════════════════════════════════════
        // Tabla: mv_documentos (Documentos de la Manifestación)
        // ══════════════════════════════════════════════════════════════
        Schema::create('mv_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            
            $table->enum('status', ['borrador', 'completado', 'guardado', 'enviado', 'rechazado'])->default('borrador');
            
            // Documentos eDocument
            $table->text('documentos')->nullable();          // JSON encriptado con array de documentos
            
            // Campos para documentos individuales subidos (PDF)
            $table->string('document_name')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('folio_edocument', 30)->nullable();
            $table->string('original_filename')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->boolean('is_vucem_compliant')->default(false);
            $table->boolean('was_converted')->default(false);
            $table->string('uploaded_by')->nullable();
            $table->longText('file_content_base64')->nullable();
            $table->string('mime_type')->nullable();
            
            $table->timestamps();
            $table->index(['applicant_id', 'status']);
            $table->index(['applicant_id', 'document_name']);
            $table->index(['is_vucem_compliant']);
            $table->index(['mime_type']);
        });

        // ══════════════════════════════════════════════════════════════
        // Tabla: edocuments_registrados (Cache de consultas a VUCEM)
        // ══════════════════════════════════════════════════════════════
        Schema::create('edocuments_registrados', function (Blueprint $table) {
            $table->id();
            $table->string('folio_edocument', 30)->unique();
            $table->boolean('existe_en_vucem')->default(false);
            $table->timestamp('fecha_ultima_consulta')->nullable();
            $table->string('response_code', 50)->nullable();
            $table->string('response_message', 255)->nullable();
            $table->json('cove_data')->nullable();
            $table->timestamps();
        });

        // ══════════════════════════════════════════════════════════════
        // Tabla: mv_acuses (Acuses de envío a VUCEM)
        // ══════════════════════════════════════════════════════════════
        Schema::create('mv_acuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('datos_manifestacion_id')->nullable();
            $table->string('folio_manifestacion', 50)->unique();
            $table->string('numero_pedimento', 20)->nullable();
            $table->string('numero_cove', 20)->nullable();
            $table->text('xml_enviado');
            $table->text('xml_respuesta');
            $table->longText('acuse_pdf')->nullable();
            $table->string('status', 50)->default('ENVIADO');
            $table->text('mensaje_vucem')->nullable();
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
        Schema::dropIfExists('edocuments_registrados');
        Schema::dropIfExists('mv_documentos');
        Schema::dropIfExists('mv_informacion_cove');
        Schema::dropIfExists('mv_datos_manifestacion');
        Schema::dropIfExists('mve_rfc_consulta');
        Schema::dropIfExists('mv_client_applicants');
    }
};