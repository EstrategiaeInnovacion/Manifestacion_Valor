<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla pivote para relación muchos-a-muchos Solicitante <-> Usuario
        Schema::create('mv_applicant_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('mv_client_applicants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unique(['applicant_id', 'user_id']);
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            // Migrar los datos existentes de assigned_user_id a la nueva tabla pivote
            DB::statement('
                INSERT INTO mv_applicant_user_assignments (applicant_id, user_id, created_at, updated_at)
                SELECT id, assigned_user_id, NOW(), NOW()
                FROM mv_client_applicants
                WHERE assigned_user_id IS NOT NULL
            ');
        }

        // Eliminar la columna antigua ya no necesaria
        Schema::table('mv_client_applicants', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn('assigned_user_id');
        });
    }

    public function down(): void
    {
        // Restaurar la columna assigned_user_id
        Schema::table('mv_client_applicants', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_email');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            // Restaurar el último usuario asignado por cada solicitante desde la pivote
            DB::statement('
                UPDATE mv_client_applicants c
                INNER JOIN (
                    SELECT applicant_id, MAX(user_id) AS user_id
                    FROM mv_applicant_user_assignments
                    GROUP BY applicant_id
                ) AS last ON c.id = last.applicant_id
                SET c.assigned_user_id = last.user_id
            ');
        }

        Schema::dropIfExists('mv_applicant_user_assignments');
    }
};
