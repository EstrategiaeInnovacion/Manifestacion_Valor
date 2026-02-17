<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('max_users')->default(5)->after('role');         // Máx usuarios que puede crear (Admin)
            $table->unsignedInteger('max_applicants')->default(10)->after('max_users'); // Máx solicitantes que puede añadir
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['max_users', 'max_applicants']);
        });
    }
};
