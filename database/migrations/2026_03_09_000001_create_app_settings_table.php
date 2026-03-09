<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        // Seed default values
        $now = now();
        DB::table('app_settings')->insert([
            [
                'key' => 'aviso_privacidad_sellos',
                'value' => '<h4 class="font-bold text-[#001a4d] text-sm mb-3">AVISO DE PRIVACIDAD Y AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS SENSIBLES</h4><p class="text-xs text-slate-700 mb-3">De conformidad con lo establecido en la Ley Federal de Protección de Datos Personales en Posesión de los Particulares y su Reglamento, se informa al usuario que el presente sistema recopila y almacena la siguiente información sensible:</p><ul class="text-xs text-slate-700 mb-3 list-disc list-inside space-y-1"><li><strong>Sellos digitales VUCEM</strong> (archivos .key y .cer)</li><li><strong>Contraseña</strong> asociada a los sellos digitales</li><li><strong>Clave de Web Service</strong> para conexión con VUCEM</li></ul><p class="text-xs text-slate-700 mb-3"><strong>Finalidad del tratamiento:</strong> Esta información se almacena con el único propósito de facilitar al usuario la ejecución de las siguientes operaciones ante la Ventanilla Única de Comercio Exterior Mexicano (VUCEM): Manifestación de Valor, Digitalización de Documentos y Consulta de COVE.</p><p class="text-xs text-slate-700 mb-3"><strong>Medidas de seguridad:</strong> Toda la información sensible se almacena bajo <strong>encriptación AES-256-CBC</strong>. Los datos no son visibles en formato legible y solo se desencriptan temporalmente al momento de ejecutar las operaciones. En ningún caso se comparte esta información con terceros.</p><p class="text-xs text-slate-700 font-semibold">Al marcar la casilla de consentimiento, el usuario declara que ha leído y comprende este aviso, y autoriza expresamente el almacenamiento encriptado de sus sellos VUCEM para las operaciones indicadas.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'aviso_privacidad_completo',
                'value' => '<h2 class="text-2xl font-bold text-[#001a4d] mb-4">Aviso de Privacidad</h2><p class="text-sm text-slate-700 mb-4">De conformidad con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP) y su Reglamento, Estrategia e Innovación S.A. de C.V. (en adelante "el Sistema"), con domicilio en México, pone a su disposición el presente Aviso de Privacidad.</p><h3 class="text-lg font-bold text-[#003399] mb-2">Datos Personales Recabados</h3><p class="text-sm text-slate-700 mb-4">El Sistema recaba y trata los siguientes datos personales e información sensible: nombre completo, correo electrónico, RFC, sellos digitales VUCEM (archivos .key y .cer), contraseña de sellos, clave de Web Service VUCEM.</p><h3 class="text-lg font-bold text-[#003399] mb-2">Finalidad del Tratamiento</h3><p class="text-sm text-slate-700 mb-4">Los datos son utilizados exclusivamente para: (a) Firma y envío electrónico de Manifestaciones de Valor ante VUCEM; (b) Digitalización y registro de documentos electrónicos (eDocuments); (c) Consulta de Comprobantes de Valor Electrónico (COVE) en VUCEM; (d) Administración y control de acceso al sistema.</p><h3 class="text-lg font-bold text-[#003399] mb-2">Medidas de Seguridad</h3><p class="text-sm text-slate-700 mb-4">Toda la información sensible (sellos, contraseñas, claves) se almacena con encriptación AES-256-CBC. No se comparte con terceros bajo ninguna circunstancia.</p><h3 class="text-lg font-bold text-[#003399] mb-2">Derechos ARCO</h3><p class="text-sm text-slate-700 mb-4">Usted tiene derecho a Acceder, Rectificar, Cancelar u Oponerse al tratamiento de sus datos personales (derechos ARCO). Para ejercerlos, contacte al administrador del sistema.</p><h3 class="text-lg font-bold text-[#003399] mb-2">Cambios al Aviso de Privacidad</h3><p class="text-sm text-slate-700">Cualquier modificación a este aviso será notificada a través del sistema. La continuación del uso del servicio implica la aceptación de los cambios.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'condiciones_uso',
                'value' => '<h2 class="text-2xl font-bold text-[#001a4d] mb-4">Condiciones de Uso</h2><p class="text-sm text-slate-700 mb-4">Al utilizar este sistema, el usuario acepta las siguientes condiciones de uso:</p><h3 class="text-lg font-bold text-[#003399] mb-2">1. Acceso y Uso Autorizado</h3><p class="text-sm text-slate-700 mb-4">El acceso al sistema es exclusivo para usuarios autorizados. Está prohibido el uso no autorizado, la transferencia de credenciales de acceso o cualquier uso que contravenga la ley.</p><h3 class="text-lg font-bold text-[#003399] mb-2">2. Responsabilidad del Usuario</h3><p class="text-sm text-slate-700 mb-4">El usuario es responsable de la veracidad de los datos ingresados, de la custodia de sus credenciales y de los documentos presentados ante VUCEM a través del sistema.</p><h3 class="text-lg font-bold text-[#003399] mb-2">3. Propiedad Intelectual</h3><p class="text-sm text-slate-700 mb-4">Todo el software, diseño y contenido del sistema son propiedad de Estrategia e Innovación. Queda prohibida su reproducción sin autorización expresa.</p><h3 class="text-lg font-bold text-[#003399] mb-2">4. Limitación de Responsabilidad</h3><p class="text-sm text-slate-700 mb-4">El sistema no se hace responsable por errores en la información proporcionada por el usuario, fallas en los servicios de VUCEM, ni por consecuencias derivadas del uso incorrecto del sistema.</p><h3 class="text-lg font-bold text-[#003399] mb-2">5. Modificaciones</h3><p class="text-sm text-slate-700">Nos reservamos el derecho de modificar estas condiciones en cualquier momento. Los cambios entrarán en vigor al momento de su publicación en el sistema.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
