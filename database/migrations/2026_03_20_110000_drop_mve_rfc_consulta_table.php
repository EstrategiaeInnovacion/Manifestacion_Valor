<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('mve_rfc_consulta');
    }

    public function down(): void
    {
        Schema::create('mve_rfc_consulta', function ($table) {
            $table->id();
            $table->string('applicant_rfc', 13);
            $table->string('rfc_consulta', 13);
            $table->string('tipo_figura', 10)->nullable();
            $table->timestamps();
        });
    }
};
