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
        Schema::table('edocuments_registrados', function (Blueprint $table) {
            $table->json('cove_data')->nullable()->after('response_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edocuments_registrados', function (Blueprint $table) {
            $table->dropColumn('cove_data');
        });
    }
};
