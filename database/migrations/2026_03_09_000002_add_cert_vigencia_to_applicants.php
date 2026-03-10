<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mv_client_applicants', function (Blueprint $table) {
            $table->date('vucem_cert_vigencia')->nullable()->after('vucem_cert_file')
                  ->comment('Fecha de vencimiento del certificado .cer, extraída automáticamente al subirlo');
            $table->boolean('seal_expiry_notified')->default(false)->after('vucem_cert_vigencia')
                  ->comment('Indica si ya se envió la notificación de vencimiento próximo (30 días)');
        });
    }

    public function down(): void
    {
        Schema::table('mv_client_applicants', function (Blueprint $table) {
            $table->dropColumn(['vucem_cert_vigencia', 'seal_expiry_notified']);
        });
    }
};
