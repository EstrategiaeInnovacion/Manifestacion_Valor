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
        \DB::statement("ALTER TABLE support_tickets MODIFY COLUMN status ENUM('open','in_progress','closed','cancelled') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE support_tickets MODIFY COLUMN status ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open'");
    }
};
