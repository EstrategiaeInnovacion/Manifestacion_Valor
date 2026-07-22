<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de Registros de Importaciones de Glosa Data Stage
        Schema::create('glosa_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('original_filename');
            $table->string('folio')->nullable();
            $table->string('rfc')->nullable();
            $table->date('fecha_inicial')->nullable();
            $table->date('fecha_final')->nullable();
            $table->integer('total_files')->default(0);
            $table->integer('total_pedimentos')->default(0);
            $table->integer('total_partidas')->default(0);
            $table->decimal('total_valor_dolares', 15, 2)->default(0);
            $table->decimal('total_contribuciones', 15, 2)->default(0);
            $table->string('status', 30)->default('processing'); // processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'status']);
            $table->index(['admin_id', 'fecha_inicial', 'fecha_final']);
        });

        // Bóveda 501 - Datos Generales del Pedimento
        Schema::create('glosa_501_datos_generales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('glosa_imports')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('clave_operacion', 50)->index(); // Patente-Pedimento-SeccionAduanera
            $table->string('patente', 10)->nullable();
            $table->string('pedimento', 20)->nullable();
            $table->string('seccion_aduanera', 10)->nullable();
            $table->string('tipo_operacion', 5)->nullable(); // 1: Imp, 2: Exp
            $table->string('clave_documento', 10)->nullable();
            $table->string('seccion_aduanera_entrada', 10)->nullable();
            $table->string('curp_contribuyente', 25)->nullable();
            $table->string('rfc', 20)->nullable()->index();
            $table->string('curp_agente', 25)->nullable();
            $table->decimal('tipo_cambio', 12, 4)->default(1);
            $table->decimal('total_fletes', 14, 2)->default(0);
            $table->decimal('total_seguros', 14, 2)->default(0);
            $table->decimal('total_embalajes', 14, 2)->default(0);
            $table->decimal('total_incrementables', 14, 2)->default(0);
            $table->decimal('total_deducibles', 14, 2)->default(0);
            $table->decimal('peso_bruto', 14, 3)->default(0);
            $table->string('medio_transporte_salida', 10)->nullable();
            $table->string('medio_transporte_arribo', 10)->nullable();
            $table->string('medio_transporte_entrada_salida', 10)->nullable();
            $table->string('destino_mercancia', 10)->nullable();
            $table->string('nombre_contribuyente')->nullable();
            $table->string('tipo_pedimento', 10)->nullable();
            $table->date('fecha_pago_real')->nullable()->index();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'clave_operacion']);
        });

        // Bóveda 505 - Facturas
        Schema::create('glosa_505_facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('glosa_imports')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('clave_operacion', 50)->index();
            $table->string('numero_factura', 100)->nullable();
            $table->date('fecha_factura')->nullable();
            $table->string('incoterm', 10)->nullable();
            $table->string('moneda', 10)->nullable();
            $table->decimal('valor_dolares', 15, 2)->default(0);
            $table->decimal('valor_moneda_extranjera', 15, 2)->default(0);
            $table->string('proveedor_nombre')->nullable();
            $table->string('proveedor_tax_id', 50)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'clave_operacion']);
        });

        // Bóveda 510 - Contribuciones a Nivel Pedimento
        Schema::create('glosa_510_contribuciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('glosa_imports')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('clave_operacion', 50)->index();
            $table->string('clave_contribucion', 10)->nullable()->index(); // 1: IGI/DTA, 3: IVA, etc.
            $table->string('forma_pago', 10)->nullable();
            $table->decimal('importe', 15, 2)->default(0);
            $table->date('fecha_pago_real')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'clave_operacion']);
        });

        // Bóveda 551 - Partidas
        Schema::create('glosa_551_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('glosa_imports')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('clave_operacion', 50)->index();
            $table->integer('secuencia')->default(1);
            $table->string('fraccion_arancelaria', 20)->nullable()->index();
            $table->string('subdivision', 10)->nullable();
            $table->text('descripcion_mercancia')->nullable();
            $table->decimal('precio_unitario', 15, 4)->default(0);
            $table->decimal('valor_aduana', 15, 2)->default(0);
            $table->decimal('valor_comercial', 15, 2)->default(0);
            $table->decimal('valor_dolares', 15, 2)->default(0);
            $table->decimal('cantidad_umc', 14, 3)->default(0);
            $table->string('umc', 10)->nullable();
            $table->decimal('cantidad_umt', 14, 3)->default(0);
            $table->string('umt', 10)->nullable();
            $table->string('pais_origen_destino', 10)->nullable();
            $table->date('fecha_pago_real')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'clave_operacion']);
            $table->index(['admin_id', 'fraccion_arancelaria']);
        });

        // Bóveda 557 - Contribuciones por Partida
        Schema::create('glosa_557_contribuciones_partida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('glosa_imports')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('clave_operacion', 50)->index();
            $table->integer('secuencia')->default(1);
            $table->string('clave_contribucion', 10)->nullable()->index();
            $table->string('forma_pago', 10)->nullable();
            $table->decimal('importe', 15, 2)->default(0);
            $table->date('fecha_pago_real')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'clave_operacion']);
        });

        // Bóveda 701 - Rectificaciones
        Schema::create('glosa_701_rectificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('glosa_imports')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('clave_operacion', 50)->index(); // Pedimento Rectificado
            $table->string('clave_operacion_original', 50)->nullable()->index(); // Pedimento Original Link
            $table->string('pedimento_anterior', 20)->nullable();
            $table->string('patente_anterior', 10)->nullable();
            $table->string('seccion_anterior', 10)->nullable();
            $table->string('pedimento_original', 20)->nullable();
            $table->string('patente_original', 10)->nullable();
            $table->string('seccion_original', 10)->nullable();
            $table->date('fecha_pago_rectificacion')->nullable();
            $table->date('fecha_pago_original')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'clave_operacion']);
            $table->index(['admin_id', 'clave_operacion_original']);
        });

        // Tabla genérica para almacenar los registros completos de las 26 bóvedas
        Schema::create('glosa_vault_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('glosa_imports')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('vault_code', 20)->index(); // 501, 502, 503, ..., 701, Resumen, Inci, Sel
            $table->string('sheet_name', 100);
            $table->string('clave_operacion', 50)->nullable()->index();
            $table->json('data')->comment('Array asociativo de columna => valor');
            $table->timestamps();

            $table->index(['import_id', 'vault_code']);
            $table->index(['admin_id', 'vault_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glosa_vault_records');
        Schema::dropIfExists('glosa_701_rectificaciones');
        Schema::dropIfExists('glosa_557_contribuciones_partida');
        Schema::dropIfExists('glosa_551_partidas');
        Schema::dropIfExists('glosa_510_contribuciones');
        Schema::dropIfExists('glosa_505_facturas');
        Schema::dropIfExists('glosa_501_datos_generales');
        Schema::dropIfExists('glosa_imports');
    }
};
