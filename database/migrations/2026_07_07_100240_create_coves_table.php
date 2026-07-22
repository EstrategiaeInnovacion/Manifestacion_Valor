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
        Schema::create('coves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            $table->string('factura_numero')->nullable();
            $table->string('factura_fecha')->nullable();
            $table->string('edocument', 30)->nullable()->index();
            $table->string('status', 20)->default('borrador')->index();
            $table->longText('cove_json')->nullable();
            $table->longText('xml_solicitud')->nullable();
            $table->longText('xml_respuesta')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coves');
    }
};
