<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Required fields (English column names)
            $table->string('full_name');                 // Nombre Completo
            $table->string('email')->unique();           // Correo electrónico
            $table->string('username')->unique();        // Usuario
            $table->string('password');                  // Contraseña (hashed)
            $table->string('role');                      // Rol
            $table->rememberToken();                     // Token para "Remember Me"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
