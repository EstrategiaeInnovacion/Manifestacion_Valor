<x-app-layout>
    <x-slot name="title">Consulta de COVE</x-slot>
    @vite(['resources/css/mve-create.css', 'resources/js/edocument-consulta.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- NAVBAR --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">
                            Consulta VUCEM
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-6">
                         <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Usuario Conectado</p>
                            <p class="text-sm font-black text-[#003399]">{{ auth()->user()->full_name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>

                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Consulta de <span class="text-[#003399]">COVE</span>
                </h2>
                <p class="text-slate-500 mt-2">
                    {{ $description ?? 'Ingresa el eDocument para recuperar la información de valor y mercancías.' }}
                </p>
            </div>

            {{-- Alertas --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex items-start">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5 mr-3"></i>
                        <div class="text-sm text-red-700">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- FORMULARIO --}}
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8 mb-8">
                <form method="POST" action="{{ route('cove.consulta') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 gap-6">
                        @if(isset($solicitantes) && $solicitantes->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="solicitante_id" class="block text-sm font-semibold text-slate-700 mb-2">Solicitante (RFC)</label>
                                    <select name="solicitante_id" id="solicitante_id" class="form-input w-full bg-slate-50" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($solicitantes as $solicitante)
                                            <option value="{{ $solicitante->id }}" {{ old('solicitante_id', $solicitante_seleccionado ?? '') == $solicitante->id ? 'selected' : '' }}>
                                                {{ $solicitante->applicant_rfc }} - {{ $solicitante->business_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Clave Web Service: se oculta si hay almacenada --}}
                                <div id="ws-manual-section">
                                    <label for="clave_webservice" class="block text-sm font-semibold text-slate-700 mb-2">Contraseña Web Service VUCEM</label>
                                    <input type="password" name="clave_webservice" id="clave_webservice" 
                                           class="form-input w-full" 
                                           placeholder="Contraseña de acceso al portal VUCEM" />
                                    <p class="text-[10px] text-slate-400 mt-1">Es la contraseña que usas para entrar al portal, NO la de la FIEL.</p>
                                </div>
                            </div>

                            {{-- Badge de credenciales detectadas --}}
                            <div id="cred-status" class="hidden">
                                <div id="cred-badge-ok" class="hidden rounded-xl border border-green-200 bg-green-50 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                            <i data-lucide="shield-check" class="w-5 h-5 text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-green-800">Credenciales VUCEM detectadas</p>
                                            <p class="text-xs text-green-600" id="cred-detail">Los sellos (.key, .cer) y contraseña se cargarán automáticamente desde la configuración del solicitante.</p>
                                        </div>
                                    </div>
                                </div>
                                <div id="cred-badge-partial" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-amber-800">Credenciales VUCEM parciales</p>
                                            <p class="text-xs text-amber-600" id="cred-partial-detail">Algunos datos se cargarán automáticamente. Complete los campos faltantes manualmente.</p>
                                        </div>
                                    </div>
                                </div>
                                <div id="cred-badge-none" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                                            <i data-lucide="key" class="w-5 h-5 text-slate-500"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-700">Sin credenciales almacenadas</p>
                                            <p class="text-xs text-slate-500">Ingrese los archivos de firma electrónica y contraseña del Web Service manualmente.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="folio_edocument" class="block text-sm font-semibold text-slate-700 mb-2">Folio COVE (eDocument)</label>
                                <input type="text" name="folio_edocument" id="folio_edocument" 
                                       value="{{ old('folio_edocument', $folio ?? '') }}" 
                                       class="form-input w-full uppercase font-mono border-blue-200 bg-blue-50/30" 
                                       placeholder="Ej. 0000000000000" required />
                            </div>

                            {{-- Sección de firma manual: se oculta si hay credenciales almacenadas --}}
                            <div id="efirma-manual-section" class="p-6 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center">
                                    <i data-lucide="key" class="w-4 h-4 mr-2 text-[#003399]"></i>
                                    Firma Electrónica (Para desencriptar respuesta)
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Certificado (.cer)</label>
                                        <input type="file" name="certificado" id="certificado_input" class="file-input w-full text-sm" accept=".cer,.crt,.pem" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Llave (.key)</label>
                                        <input type="file" name="llave_privada" id="llave_privada_input" class="file-input w-full text-sm" accept=".key,.pem" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Contraseña de la Llave Privada</label>
                                        <input type="password" name="contrasena_llave" id="contrasena_llave_input" class="form-input w-full" placeholder="••••••••" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" class="btn-primary">
                                    <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                    Consultar Valor
                                </button>
                            </div>
                        @else
                           <div class="bg-amber-50 p-4 rounded text-amber-800 text-sm">Registre un solicitante primero en la sección de Solicitantes.</div>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Script para auto-detección de credenciales --}}
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const solicitanteSelect = document.getElementById('solicitante_id');
                    if (!solicitanteSelect) return;

                    const credStatus = document.getElementById('cred-status');
                    const badgeOk = document.getElementById('cred-badge-ok');
                    const badgePartial = document.getElementById('cred-badge-partial');
                    const badgeNone = document.getElementById('cred-badge-none');
                    const efirmaSection = document.getElementById('efirma-manual-section');
                    const wsSection = document.getElementById('ws-manual-section');
                    const credDetail = document.getElementById('cred-detail');
                    const partialDetail = document.getElementById('cred-partial-detail');

                    // Inputs manuales
                    const certInput = document.getElementById('certificado_input');
                    const keyInput = document.getElementById('llave_privada_input');
                    const passInput = document.getElementById('contrasena_llave_input');
                    const wsInput = document.getElementById('clave_webservice');

                    function hideAllBadges() {
                        badgeOk.classList.add('hidden');
                        badgePartial.classList.add('hidden');
                        badgeNone.classList.add('hidden');
                    }

                    function setFieldsRequired(required) {
                        if (certInput) certInput.required = required;
                        if (keyInput) keyInput.required = required;
                        if (passInput) passInput.required = required;
                    }

                    function checkCredentials(applicantId) {
                        if (!applicantId) {
                            credStatus.classList.add('hidden');
                            efirmaSection.classList.remove('hidden');
                            wsSection.classList.remove('hidden');
                            setFieldsRequired(true);
                            if (wsInput) wsInput.required = true;
                            return;
                        }

                        fetch(`/cove/credenciales/${applicantId}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        })
                        .then(r => r.json())
                        .then(data => {
                            credStatus.classList.remove('hidden');
                            hideAllBadges();

                            const hasCreds = data.has_credentials;
                            const hasWs = data.has_webservice_key;

                            if (hasCreds && hasWs) {
                                // Todo almacenado: ocultar sección manual completa
                                badgeOk.classList.remove('hidden');
                                credDetail.textContent = 'Los sellos (.key, .cer), contraseña de llave y clave de Web Service se cargarán automáticamente.';
                                efirmaSection.classList.add('hidden');
                                wsSection.classList.add('hidden');
                                setFieldsRequired(false);
                                if (wsInput) wsInput.required = false;
                            } else if (hasCreds && !hasWs) {
                                // Sellos almacenados pero sin WS key
                                badgePartial.classList.remove('hidden');
                                partialDetail.textContent = 'Sellos VUCEM (.key, .cer) detectados. Ingrese la contraseña del Web Service manualmente.';
                                efirmaSection.classList.add('hidden');
                                wsSection.classList.remove('hidden');
                                setFieldsRequired(false);
                                if (wsInput) wsInput.required = true;
                            } else if (!hasCreds && hasWs) {
                                // Solo WS key almacenada
                                badgePartial.classList.remove('hidden');
                                partialDetail.textContent = 'Clave de Web Service detectada. Ingrese los sellos de firma electrónica manualmente.';
                                efirmaSection.classList.remove('hidden');
                                wsSection.classList.add('hidden');
                                setFieldsRequired(true);
                                if (wsInput) wsInput.required = false;
                            } else {
                                // Nada almacenado
                                badgeNone.classList.remove('hidden');
                                efirmaSection.classList.remove('hidden');
                                wsSection.classList.remove('hidden');
                                setFieldsRequired(true);
                                if (wsInput) wsInput.required = true;
                            }

                            // Re-inicializar iconos de Lucide
                            if (window.lucide) lucide.createIcons();
                        })
                        .catch(() => {
                            // En caso de error, mostrar todos los campos manuales
                            credStatus.classList.add('hidden');
                            efirmaSection.classList.remove('hidden');
                            wsSection.classList.remove('hidden');
                            setFieldsRequired(true);
                            if (wsInput) wsInput.required = true;
                        });
                    }

                    // Evento de cambio de solicitante
                    solicitanteSelect.addEventListener('change', function() {
                        checkCredentials(this.value);
                    });

                    // Verificar al cargar si ya hay un solicitante seleccionado
                    if (solicitanteSelect.value) {
                        checkCredentials(solicitanteSelect.value);
                    }
                });
            </script>

            {{-- RESULTADOS --}}
            @if(isset($result))
                <div class="mt-8 space-y-6 animate-fade-in-up">
                    
                    {{-- Status --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-[#001a4d]">Respuesta VUCEM</h3>
                            <p class="text-sm text-slate-500">{{ $result['message'] }}</p>
                        </div>
                        @if($result['success'])
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">EXITOSO</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">ERROR</span>
                        @endif
                    </div>

                    {{-- BOTÓN PARA DESCARGAR PDF ACUSE --}}
                    @if($result['success'] && isset($folio))
                        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                            <h4 class="font-bold text-[#001a4d] mb-4 flex items-center">
                                <i data-lucide="file-check" class="w-5 h-5 mr-2 text-red-600"></i>
                                Acuse PDF Sellado
                            </h4>
                            <p class="text-sm text-slate-500 mb-4">Descarga el acuse oficial sellado por VUCEM de este COVE.</p>
                            
                            <div class="flex items-center gap-4">
                                @if(isset($acuse_pdf_base64) && $acuse_pdf_base64)
                                    {{-- PDF disponible --}}
                                    <button type="button" id="btnDescargarAcusePdf"
                                       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-lg transition-colors">
                                        <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                        Descargar Acuse PDF
                                    </button>
                                    <span class="text-sm text-green-600">✓ PDF disponible</span>
                                @else
                                    {{-- PDF no disponible --}}
                                    <span class="text-sm text-amber-600">
                                        <i data-lucide="alert-circle" class="w-4 h-4 inline mr-1"></i>
                                        No se pudo obtener el acuse PDF automáticamente. Intente consultar nuevamente.
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Script para guardar PDF en sessionStorage y descargar --}}
                        @if(isset($acuse_pdf_base64) && $acuse_pdf_base64)
                        <script>
                            (function() {
                                // Guardar el PDF en sessionStorage (se borra al cerrar la pestaña/actualizar)
                                const pdfKey = 'cove_acuse_pdf_{{ $folio }}';
                                const pdfData = {
                                    base64: '{{ $acuse_pdf_base64 }}',
                                    folio: '{{ $folio }}',
                                    timestamp: Date.now()
                                };
                                sessionStorage.setItem(pdfKey, JSON.stringify(pdfData));
                                
                                // Función para descargar
                                document.getElementById('btnDescargarAcusePdf').addEventListener('click', function() {
                                    const stored = sessionStorage.getItem(pdfKey);
                                    if (!stored) {
                                        alert('El PDF ha expirado. Por favor, consulte nuevamente.');
                                        return;
                                    }
                                    
                                    const data = JSON.parse(stored);
                                    
                                    // Crear blob y descargar
                                    const byteCharacters = atob(data.base64);
                                    const byteNumbers = new Array(byteCharacters.length);
                                    for (let i = 0; i < byteCharacters.length; i++) {
                                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                                    }
                                    const byteArray = new Uint8Array(byteNumbers);
                                    const blob = new Blob([byteArray], { type: 'application/pdf' });
                                    
                                    // Crear URL y abrir en nueva pestaña
                                    const url = URL.createObjectURL(blob);
                                    window.open(url, '_blank');
                                    
                                    // Limpiar URL después de un momento
                                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                                });
                            })();
                        </script>
                        @endif
                    @endif

                    {{-- DETALLE COVE (Solo si hay data) --}}
                    @if(isset($result['cove_data']) && !empty($result['cove_data']))
                        @include('edocument.partials.cove-details', ['cove' => $result['cove_data']])
                    @endif

                    {{-- ARCHIVOS XML (Si los hay) --}}
                    @if(isset($files) && count($files) > 0)
                        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
                            <h4 class="font-bold text-[#001a4d] mb-4">Archivos XML Recuperados</h4>
                            <ul class="divide-y divide-slate-200">
                                @foreach($files as $file)
                                    <li class="py-3 flex justify-between items-center">
                                        <div class="flex items-center">
                                            <i data-lucide="file-code" class="w-5 h-5 text-slate-400 mr-3"></i>
                                            <span class="text-sm font-mono text-slate-600">{{ $file['name'] }}</span>
                                        </div>
                                        <a href="{{ route('cove.descargar', $file['token']) }}" class="text-blue-600 text-xs font-bold hover:underline bg-blue-50 px-3 py-1 rounded-md">Descargar</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                </div>
            @endif
        </main>
    </div>
</x-app-layout>