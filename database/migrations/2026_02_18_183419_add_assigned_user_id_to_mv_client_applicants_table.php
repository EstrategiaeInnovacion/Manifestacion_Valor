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
        Schema::table('mv_client_applicants', function (Blueprint $table) {
            // Usuario al que está asignado el solicitante
            $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_email');
            // Usuario (Admin) que creó el solicitante
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('assigned_user_id');
            
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mv_client_applicants', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['assigned_user_id', 'created_by_user_id']);
        });
    }
};
