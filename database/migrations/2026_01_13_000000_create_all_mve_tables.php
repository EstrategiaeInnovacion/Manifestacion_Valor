<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migración consolidada que crea todas las tablas del sistema MVE
     */
    public function up(): void
    {
        // Tabla: mv_client_applicants (Solicitantes/Clientes)
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
            $table->text('applicant_rfc');               // RFC del Solicitante
            $table->text('business_name');               // Razón Social
            $table->text('main_economic_activity');      // Actividad Económica Preponderante

            // Domicilio Fiscal del Solicitante (TEXT para soportar encriptación)
            $table->text('country');                     // País
            $table->text('postal_code');                 // Código Postal
            $table->text('state');                       // Estado
            $table->text('municipality');                // Municipio
            $table->text('locality')->nullable();        // Localidad (Opcional)
            $table->text('neighborhood');                // Colonia
            $table->text('street');                      // Calle
            $table->text('exterior_number');             // No. Exterior
            $table->text('interior_number')->nullable(); // No. Interior (Opcional según formulario)

            // Datos de Contacto (TEXT para soportar encriptación)
            $table->text('area_code');                   // Lada
            $table->text('phone');                       // Teléfono

            // Clave para el envío de archivos mediante Servicios Web (TEXT para soportar encriptación)
            $table->text('ws_file_upload_key')->nullable();// Web Services File Upload Key (Opcional)

            $table->timestamps();
        });

        // Tabla: mve_rfc_consulta (RFC de consulta para personas vinculadas)
        Schema::create('mve_rfc_consulta', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_rfc', 13); // RFC del solicitante (sin encriptar para búsquedas)
            $table->text('rfc_consulta'); // RFC de consulta (encriptado)
            $table->text('razon_social'); // Razón social (encriptado)
            $table->text('tipo_figura'); // Tipo de figura (encriptado)
            $table->timestamps();
            
            // Índice para mejorar búsquedas por RFC del solicitante
            $table->index('applicant_rfc');
        });

        // Tabla: mv_datos_manifestacion (Datos principales de la Manifestación)
        Schema::create('mv_datos_manifestacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            
            // CORRECCIÓN AQUÍ: Agregados todos los estados necesarios desde el inicio
            $table->enum('status', ['borrador', 'completado', 'guardado', 'enviado', 'rechazado'])->default('borrador');
            
            // Datos de Manifestación
            $table->text('rfc_importador')->nullable();
            $table->text('metodo_valoracion')->nullable();
            $table->text('existe_vinculacion')->nullable();
            $table->text('pedimento')->nullable();
            $table->text('patente')->nullable();
            $table->text('aduana')->nullable();
            $table->text('persona_consulta')->nullable(); // JSON encriptado
            
            $table->timestamps();
            
            // Índice para búsquedas rápidas
            $table->index(['applicant_id', 'status']);
        });

        // Tabla: mv_informacion_cove (Información COVE y Valor en Aduana consolidado)
        Schema::create('mv_informacion_cove', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            
            // CORRECCIÓN AQUÍ: Agregados todos los estados necesarios desde el inicio
            $table->enum('status', ['borrador', 'completado', 'guardado', 'enviado', 'rechazado'])->default('borrador');
            
            // Información COVE
            $table->text('informacion_cove')->nullable(); // JSON encriptado con array de COVEs
            $table->text('pedimentos')->nullable(); // JSON encriptado con array de pedimentos
            
            // Datos de Valoración (antes en mv_valor_aduana)
            $table->text('precio_pagado')->nullable(); // JSON encriptado
            $table->text('precio_por_pagar')->nullable(); // JSON encriptado
            $table->text('compenso_pago')->nullable(); // JSON encriptado
            $table->text('incrementables')->nullable(); // JSON encriptado
            $table->text('decrementables')->nullable(); // JSON encriptado
            $table->text('valor_en_aduana')->nullable(); // JSON encriptado con totales calculados
            
            $table->timestamps();
            
            // Índice para búsquedas rápidas
            $table->index(['applicant_id', 'status']);
        });

        // Tabla: mv_documentos (Documentos de la Manifestación)
        Schema::create('mv_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            
            // CORRECCIÓN AQUÍ: Agregados todos los estados necesarios desde el inicio
            $table->enum('status', ['borrador', 'completado', 'guardado', 'enviado', 'rechazado'])->default('borrador');
            
            // Array de documentos eDocument (para formulario manual)
            $table->text('documentos')->nullable(); // JSON encriptado con array de documentos
            
            // Campos para documentos individuales subidos (PDF)
            $table->string('document_name')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('folio_edocument', 30)->nullable();
            $table->string('estado_vucem')->nullable();
            $table->string('original_filename')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->boolean('is_vucem_compliant')->default(false);
            $table->boolean('was_converted')->default(false);
            $table->string('uploaded_by')->nullable();
            $table->longText('file_content_base64')->nullable();
            $table->string('mime_type')->nullable();
            
            $table->timestamps();
            
            // Índices para búsquedas
            $table->index(['applicant_id', 'status']);
            $table->index(['applicant_id', 'document_name']);
            $table->index(['is_vucem_compliant']);
            $table->index(['mime_type']);
        });

        // Tabla: edocuments_registrados (Cache de consultas a VUCEM)
        Schema::create('edocuments_registrados', function (Blueprint $table) {
            $table->id();
            $table->string('folio_edocument', 30)->unique();
            $table->boolean('existe_en_vucem')->default(false);
            $table->timestamp('fecha_ultima_consulta')->nullable();
            $table->string('response_code', 50)->nullable();
            $table->string('response_message', 255)->nullable();
            $table->timestamps();
        });

        // Agregar campo created_by a users
        Schema::table('users', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });

        Schema::dropIfExists('edocuments_registrados');
        Schema::dropIfExists('mv_documentos');
        Schema::dropIfExists('mv_informacion_cove');
        Schema::dropIfExists('mv_datos_manifestacion');
        Schema::dropIfExists('mve_rfc_consulta');
        Schema::dropIfExists('mv_client_applicants');
    }
};