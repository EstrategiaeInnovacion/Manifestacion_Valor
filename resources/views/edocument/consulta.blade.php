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

                                <div>
                                    <label for="clave_webservice" class="block text-sm font-semibold text-slate-700 mb-2">Contraseña Web Service VUCEM</label>
                                    <input type="password" name="clave_webservice" id="clave_webservice" 
                                           class="form-input w-full" 
                                           placeholder="Contraseña de acceso al portal VUCEM" required />
                                    <p class="text-[10px] text-slate-400 mt-1">Es la contraseña que usas para entrar al portal, NO la de la FIEL.</p>
                                </div>
                            </div>

                            <div>
                                <label for="folio_edocument" class="block text-sm font-semibold text-slate-700 mb-2">Folio COVE (eDocument)</label>
                                <input type="text" name="folio_edocument" id="folio_edocument" 
                                       value="{{ old('folio_edocument', $folio ?? '') }}" 
                                       class="form-input w-full uppercase font-mono border-blue-200 bg-blue-50/30" 
                                       placeholder="Ej. 0000000000000" required />
                            </div>

                            <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center">
                                    <i data-lucide="key" class="w-4 h-4 mr-2 text-[#003399]"></i>
                                    Firma Electrónica (Para desencriptar respuesta)
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Certificado (.cer)</label>
                                        <input type="file" name="certificado" class="file-input w-full text-sm" accept=".cer,.crt,.pem" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Llave (.key)</label>
                                        <input type="file" name="llave_privada" class="file-input w-full text-sm" accept=".key,.pem" required />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Contraseña de la Llave Privada</label>
                                        <input type="password" name="contrasena_llave" class="form-input w-full" placeholder="••••••••" required />
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