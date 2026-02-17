<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key', 32)->unique();           // Clave alfanumérica única
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade'); // Admin asignado
            $table->string('duration_type');                        // 1min, 1month, 6months, 1year
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->foreignId('created_by')->constrained('users'); // SuperAdmin que la creó
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
