<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cambiar acuse_pdf de TEXT a LONGTEXT para soportar PDFs grandes en Base64
     */
    public function up(): void
    {
        Schema::table('mv_acuses', function (Blueprint $table) {
            $table->longText('acuse_pdf')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mv_acuses', function (Blueprint $table) {
            $table->text('acuse_pdf')->nullable()->change();
        });
    }
};
