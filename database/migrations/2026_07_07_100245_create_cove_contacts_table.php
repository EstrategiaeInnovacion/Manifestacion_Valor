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
        Schema::create('cove_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            $table->string('type', 20)->index(); // 'proveedor', 'comprador', 'destinatario'
            $table->string('tipo_identificador', 20)->nullable(); // TAX_ID, RFC, CURP
            $table->string('tax_id', 50)->nullable()->index();
            $table->text('razon_social');
            $table->string('calle')->nullable();
            $table->string('num_exterior', 50)->nullable();
            $table->string('num_interior', 50)->nullable();
            $table->string('cp', 20)->nullable();
            $table->string('colonia')->nullable();
            $table->string('localidad')->nullable();
            $table->string('entidad_federativa')->nullable();
            $table->string('municipio')->nullable();
            $table->string('pais')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cove_contacts');
    }
};
