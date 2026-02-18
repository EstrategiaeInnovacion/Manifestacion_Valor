<x-app-layout>
    <x-slot name="title">MVE Pendientes</x-slot>
    @vite(['resources/css/users-list.css', 'resources/js/mve-pendientes.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegación --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">MVE Pendientes</span>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Usuario Conectado</p>
                            <p class="text-sm font-black text-[#003399]">{{ auth()->user()->full_name }}</p>
                        </div>
                        
                        <div class="user-dropdown">
                            <div id="avatarButton" class="avatar-button h-10 w-10 bg-ei-gradient rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                {{ substr(auth()->user()->full_name, 0, 1) }}
                            </div>

                            <div id="dropdownMenu" class="dropdown-menu">
                                <div class="dropdown-header">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mi Cuenta</p>
                                    <p class="text-sm font-bold text-[#001a4d] mt-1">{{ auth()->user()->full_name }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ auth()->user()->email }}</p>
                                </div>
                                
                                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                    <i data-lucide="user-circle" class="w-5 h-5"></i>
                                    <span class="font-semibold text-sm">Mi Perfil</span>
                                </a>
                                
                                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                                    @csrf
                                    <button type="submit" class="dropdown-item logout w-full">
                                        <i data-lucide="log-out" class="w-5 h-5"></i>
                                        <span class="font-semibold text-sm">Cerrar Sesión</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                        MVE <span class="text-[#003399]">Pendientes</span>
                    </h2>
                    <p class="text-slate-500 mt-2">Manifestaciones de Valor en proceso</p>
                </div>
                
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-[#003399] hover:bg-[#001a4d] text-white font-bold rounded-lg transition-all">
                    <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                    Volver al Dashboard
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg">
                    <p class="text-green-700 font-semibold">{{ session('success') }}</p>
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                    <p class="text-red-700 font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            @php
                // Agrupar todas las secciones por applicant_id usando array en lugar de colección
                $allPendientes = [];
                
                foreach($datosMvPendientes as $item) {
                    if (!isset($allPendientes[$item->applicant_id])) {
                        $allPendientes[$item->applicant_id] = [
                            'applicant' => $item->applicant,
                            'datos_manifestacion' => null,
                            'informacion_cove' => null,
                            'documentos' => null,
                            'updated_at' => $item->updated_at,
                            'created_at' => $item->created_at
                        ];
                    }
                    $allPendientes[$item->applicant_id]['datos_manifestacion'] = $item;
                    if ($item->updated_at > $allPendientes[$item->applicant_id]['updated_at']) {
                        $allPendientes[$item->applicant_id]['updated_at'] = $item->updated_at;
                    }
                }
                
                foreach($covePendientes as $item) {
                    if (!isset($allPendientes[$item->applicant_id])) {
                        $allPendientes[$item->applicant_id] = [
                            'applicant' => $item->applicant,
                            'datos_manifestacion' => null,
                            'informacion_cove' => null,
                            'documentos' => null,
                            'updated_at' => $item->updated_at,
                            'created_at' => $item->created_at
                        ];
                    }
                    $allPendientes[$item->applicant_id]['informacion_cove'] = $item;
                    if ($item->updated_at > $allPendientes[$item->applicant_id]['updated_at']) {
                        $allPendientes[$item->applicant_id]['updated_at'] = $item->updated_at;
                    }
                }
                
                foreach($documentosPendientes as $item) {
                    if (!isset($allPendientes[$item->applicant_id])) {
                        $allPendientes[$item->applicant_id] = [
                            'applicant' => $item->applicant,
                            'datos_manifestacion' => null,
                            'informacion_cove' => null,
                            'documentos' => null,
                            'updated_at' => $item->updated_at,
                            'created_at' => $item->created_at
                        ];
                    }
                    $allPendientes[$item->applicant_id]['documentos'] = $item;
                    if ($item->updated_at > $allPendientes[$item->applicant_id]['updated_at']) {
                        $allPendientes[$item->applicant_id]['updated_at'] = $item->updated_at;
                    }
                }
            @endphp

            @if(empty($allPendientes))
                <div class="bg-white rounded-xl shadow-sm border-2 border-slate-200 p-12 text-center">
                    <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="file-text" class="w-10 h-10 text-amber-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700 mb-2">No hay MVE pendientes</h3>
                    <p class="text-slate-500 mb-6">Aún no tienes manifestaciones de valor en borrador</p>
                    <a href="{{ route('mve.select-applicant', ['mode' => 'manual']) }}" class="inline-flex items-center px-6 py-3 bg-[#003399] hover:bg-[#001a4d] text-white font-bold rounded-lg transition-all">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                        Crear Nueva MVE
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($allPendientes as $applicantId => $mveData)
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow duration-200 overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-start justify-between gap-6">
                                    {{-- Información del Solicitante --}}
                                    <div class="flex items-start gap-4 flex-1">
                                        <div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
                                            <i data-lucide="building-2" class="w-7 h-7 text-white"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-black text-[#001a4d] mb-1">
                                                {{ $mveData['applicant']->business_name }}
                                            </h3>
                                            <p class="text-sm text-slate-500 mb-3">
                                                <span class="font-semibold">RFC:</span> {{ $mveData['applicant']->applicant_rfc }}
                                            </p>
                                            
                                            {{-- Secciones Guardadas --}}
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                @if($mveData['datos_manifestacion'])
                                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg">
                                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                        <span class="text-xs font-bold text-blue-700">Datos Manifestación</span>
                                                    </div>
                                                @endif
                                                @if($mveData['informacion_cove'] && $mveData['informacion_cove']->informacion_cove)
                                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 border border-purple-200 rounded-lg">
                                                        <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                                        <span class="text-xs font-bold text-purple-700">Información COVE</span>
                                                    </div>
                                                @endif
                                                @if($mveData['informacion_cove'] && $mveData['informacion_cove']->valor_en_aduana)
                                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg">
                                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                        <span class="text-xs font-bold text-green-700">Valor en Aduana</span>
                                                    </div>
                                                @endif
                                                @if($mveData['documentos'])
                                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg">
                                                        <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                                                        <span class="text-xs font-bold text-amber-700">Documentos</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Información de Fechas --}}
                                            <div class="flex items-center gap-4 text-xs text-slate-500">
                                                <div class="flex items-center gap-1.5">
                                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                                    <span>Actualizado: {{ $mveData['updated_at']->format('d/m/Y H:i') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Estado y Acciones --}}
                                    <div class="flex flex-col items-end gap-3">
                                        @php
                                            $status = $mveData['datos_manifestacion']?->status ?? 'borrador';
                                        @endphp
                                        
                                        @if($status === 'guardado')
                                            {{-- Estado: Guardado sin enviar --}}
                                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-300 rounded-lg">
                                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                <span class="text-sm font-bold text-blue-700">Guardado - Sin enviar a VUCEM</span>
                                            </span>
                                            
                                            <div class="flex gap-2">
                                                <button type="button" 
                                                    onclick="mostrarVistaPreviaYFirmar({{ $applicantId }}, '{{ $mveData['applicant']->business_name }}')"
                                                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                                                    <i data-lucide="send" class="w-4 h-4"></i>
                                                    <span>Firmar y Enviar a VUCEM</span>
                                                </button>
                                                
                                                <button type="button" 
                                                    onclick="mostrarModalDescartar({{ $applicantId }}, '{{ $mveData['applicant']->business_name }}')"
                                                    class="inline-flex items-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition-all">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                            
                                        @elseif($status === 'enviado')
                                            {{-- Estado: Enviado a VUCEM --}}
                                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-50 to-green-100 border border-green-300 rounded-lg">
                                                <i data-lucide="check-circle" class="w-4 h-4 text-green-600"></i>
                                                <span class="text-sm font-bold text-green-700">Enviado a VUCEM</span>
                                            </span>
                                            
                                            @if($mveData['datos_manifestacion'])
                                                <a href="{{ route('mve.acuse', $mveData['datos_manifestacion']->id) }}" 
                                                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#003399] to-[#0047cc] hover:from-[#001a4d] hover:to-[#003399] text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                                    <span>Ver Acuse</span>
                                                </a>
                                            @endif
                                            
                                        @elseif($status === 'rechazado')
                                            {{-- Estado: Rechazado por VUCEM --}}
                                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-50 to-red-100 border border-red-300 rounded-lg">
                                                <i data-lucide="x-circle" class="w-4 h-4 text-red-600"></i>
                                                <span class="text-sm font-bold text-red-700">Rechazado por VUCEM</span>
                                            </span>
                                            
                                            <div class="flex gap-2">
                                                <a href="{{ route('mve.create-manual', $applicantId) }}" 
                                                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    <span>Corregir y Reenviar</span>
                                                </a>
                                                
                                                <button type="button" 
                                                    onclick="mostrarModalDescartar({{ $applicantId }}, '{{ $mveData['applicant']->business_name }}')"
                                                    class="inline-flex items-center gap-2 px-4 py-3 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-lg transition-all">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                            
                                        @else
                                            {{-- Estado: Borrador / En Progreso --}}
                                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-50 to-amber-100 border border-amber-300 rounded-lg">
                                                <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                                                <span class="text-sm font-bold text-amber-700">En Progreso</span>
                                            </span>
                                            
                                            <div class="flex gap-2">
                                                <a href="{{ route('mve.create-manual', $applicantId) }}" 
                                                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#003399] to-[#0047cc] hover:from-[#001a4d] hover:to-[#003399] text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    <span>Continuar Llenado</span>
                                                </a>
                                                
                                                <button type="button" 
                                                    onclick="mostrarModalDescartar({{ $applicantId }}, '{{ $mveData['applicant']->business_name }}')"
                                                    class="inline-flex items-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition-all">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </main>
    </div>

    {{-- Modal de Firma y Envío a VUCEM --}}
    <div id="modalFirma" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999]" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-[#001a4d]">Firmar y Enviar a VUCEM</h3>
                        <p id="firmaEmpresaNombre" class="text-slate-500 mt-1"></p>
                    </div>
                    <button onclick="cerrarModalFirma()" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <form id="formFirmaEnvio" enctype="multipart/form-data">
                <input type="hidden" id="firmaApplicantId" name="applicant_id" value="">
                <input type="hidden" id="useStoredCredentials" name="use_stored_credentials" value="0">
                
                <div class="p-6 space-y-6">
                    
                    {{-- Banner de credenciales almacenadas --}}
                    <div id="storedCredsBanner" class="hidden">
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="shield-check" class="w-5 h-5 text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-green-800">Credenciales configuradas</p>
                                    <p class="text-xs text-green-600 mt-0.5">Se usarán automáticamente el certificado, llave privada y clave WS almacenados para este solicitante.</p>
                                </div>
                            </div>
                            <button type="button" onclick="switchToManualCredentials()" 
                                class="mt-3 text-xs text-green-700 hover:text-green-900 underline font-medium">
                                Usar credenciales manuales en su lugar
                            </button>
                        </div>
                    </div>
                    
                    {{-- Contenedor de campos manuales (se oculta si hay almacenadas) --}}
                    <div id="manualCredsContainer">
                    {{-- Archivo de Certificado --}}
                    <div>
                        <label for="certificado" class="block text-sm font-bold text-slate-700 mb-2">
                            Archivo de Certificado (.cer) <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label for="certificado" class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-400 mb-2"></i>
                                    <p class="text-sm text-slate-500"><span class="font-semibold">Click para seleccionar</span> archivo .cer</p>
                                    <p class="text-xs text-slate-400 mt-1" id="certificadoFileName">Ningún archivo seleccionado</p>
                                </div>
                                <input id="certificado" name="certificado" type="file" accept=".cer,.crt" class="hidden" required />
                            </label>
                        </div>
                    </div>
                    
                    {{-- Archivo de Llave Privada --}}
                    <div>
                        <label for="llave_privada" class="block text-sm font-bold text-slate-700 mb-2">
                            Archivo de Llave Privada (.key) <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label for="llave_privada" class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i data-lucide="key" class="w-8 h-8 text-slate-400 mb-2"></i>
                                    <p class="text-sm text-slate-500"><span class="font-semibold">Click para seleccionar</span> archivo .key</p>
                                    <p class="text-xs text-slate-400 mt-1" id="llaveFileName">Ningún archivo seleccionado</p>
                                </div>
                                <input id="llave_privada" name="llave_privada" type="file" accept=".key,.pem" class="hidden" required />
                            </label>
                        </div>
                    </div>
                    
                    {{-- Contraseña --}}
                    <div>
                        <label for="password_llave" class="block text-sm font-bold text-slate-700 mb-2">
                            Contraseña de la Llave Privada <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password_llave" name="password_llave" required
                                class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ingrese la contraseña de su llave privada">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i data-lucide="eye" id="eyeIcon" class="w-5 h-5 text-slate-400"></i>
                            </button>
                        </div>
                    </div>
                    
                    {{-- Clave Web Service VUCEM --}}
                    <div>
                        <label for="clave_webservice" class="block text-sm font-bold text-slate-700 mb-2">
                            Clave Web Service VUCEM <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="clave_webservice" name="clave_webservice" required
                                class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ingrese la clave del web service de VUCEM">
                            <button type="button" onclick="toggleWebserviceVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i data-lucide="eye" id="eyeIconWs" class="w-5 h-5 text-slate-400"></i>
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">La clave de autenticación proporcionada por VUCEM para su RFC.</p>
                    </div>
                    </div>{{-- Cierre manualCredsContainer --}}
                    
                    {{-- Advertencia --}}
                    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
                        <div class="flex items-start">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 mr-3 flex-shrink-0 mt-0.5"></i>
                            <div>
                                <p class="text-sm text-amber-700">
                                    Al firmar y enviar, la manifestación será procesada por VUCEM.
                                    @if(config('vucem.send_manifestation_enabled'))
                                        <strong>Se realizará el envío real a VUCEM.</strong>
                                    @else
                                        <strong class="text-blue-600">Modo de prueba activado - No se enviará a VUCEM.</strong>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Checkbox de confirmación --}}
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" id="confirmacion" name="confirmacion" 
                                class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500 mt-0.5"
                                onchange="toggleBotonEnviar()">
                            <span class="ml-3 text-sm text-slate-700">
                                <strong>Confirmo que la información contenida en esta Manifestación de Valor es correcta y sin error.</strong>
                                Entiendo que una vez enviada a VUCEM no podré modificarla.
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="p-6 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" onclick="cerrarModalFirma()" 
                        class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="btnEnviarVucem" disabled
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <i data-lucide="send" class="w-5 h-5"></i>
                        <span id="btnEnviarTexto">Firmar y Enviar a VUCEM</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    {{-- Modal de Descartar --}}
    <div id="modalDescartar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9999]" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mx-auto mb-4">
                    <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
                </div>
                <h3 class="text-xl font-black text-center text-slate-800 mb-2">¿Descartar Manifestación?</h3>
                <p id="descartarEmpresaNombre" class="text-center text-slate-500 mb-4"></p>
                <p class="text-center text-sm text-slate-600">
                    Esta acción eliminará permanentemente todos los datos de la manifestación de valor. 
                    <strong class="text-red-600">Esta acción no se puede deshacer.</strong>
                </p>
                <input type="hidden" id="descartarApplicantId" value="">
            </div>
            
            <div class="p-6 border-t border-slate-200 flex justify-center gap-3">
                <button type="button" onclick="cerrarModalDescartar()" 
                    class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="button" onclick="confirmarDescartar()" id="btnConfirmarDescartar"
                    class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>Sí, Descartar</span>
                </button>
            </div>
        </div>
    </div>
    
    {{-- Modal de Vista Previa --}}
    <div id="modalVistaPrevia" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[9998]" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full mx-4 max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-slate-200 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-700">Vista Previa de Manifestación</h3>
                        <p id="vistaPreviaEmpresaNombre" class="text-slate-500 mt-1"></p>
                    </div>
                    <button onclick="cerrarVistaPrevia()" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1" id="vistaPreviaContenido">
                <div class="flex items-center justify-center py-12">
                    <i data-lucide="loader-2" class="w-8 h-8 text-slate-400 animate-spin"></i>
                    <span class="ml-2 text-slate-500">Cargando vista previa...</span>
                </div>
            </div>
            
            <div class="p-6 border-t border-slate-200 flex justify-end gap-3 flex-shrink-0">
                <button type="button" onclick="cerrarVistaPrevia()" 
                    class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="button" onclick="continuarAFirmar()" id="btnContinuarFirmar"
                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i>
                    <span>Información Correcta - Continuar a Firmar</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales para el flujo de firma
        let vistaPreviaApplicantId = null;
        let vistaPreviaEmpresaNombre = null;
        
        // URLs de Laravel (resueltas en servidor para evitar problemas con subdirectorios)
        const urlCheckCredentials = '{{ url("/cove/credenciales") }}';
        const urlPreviewData = '{{ url("/mve/preview-data") }}';
        const urlFirmarEnviar = '{{ url("/mve/firmar-enviar") }}';
        const urlDescartar = '{{ url("/mve/descartar") }}';
        
        // Mostrar nombres de archivos seleccionados
        document.getElementById('certificado')?.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Ningún archivo seleccionado';
            document.getElementById('certificadoFileName').textContent = fileName;
        });
        
        document.getElementById('llave_privada')?.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Ningún archivo seleccionado';
            document.getElementById('llaveFileName').textContent = fileName;
        });
        
        // Toggle visibilidad de contraseña
        function togglePasswordVisibility() {
            const input = document.getElementById('password_llave');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Toggle visibilidad de clave webservice
        function toggleWebserviceVisibility() {
            const input = document.getElementById('clave_webservice');
            const icon = document.getElementById('eyeIconWs');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
        
        // Toggle botón de enviar basado en checkbox
        function toggleBotonEnviar() {
            const checkbox = document.getElementById('confirmacion');
            const btn = document.getElementById('btnEnviarVucem');
            btn.disabled = !checkbox.checked;
        }
        
        // Mostrar modal de firma
        async function mostrarModalFirma(applicantId, empresaNombre) {
            document.getElementById('firmaApplicantId').value = applicantId;
            document.getElementById('firmaEmpresaNombre').textContent = empresaNombre;
            
            // Reset estado
            document.getElementById('useStoredCredentials').value = '0';
            document.getElementById('storedCredsBanner').classList.add('hidden');
            document.getElementById('manualCredsContainer').classList.remove('hidden');
            
            // Restaurar required en campos manuales
            setManualFieldsRequired(true);
            
            document.getElementById('modalFirma').style.display = 'flex';
            document.getElementById('modalFirma').classList.remove('hidden');
            lucide.createIcons();
            
            // Verificar si tiene credenciales almacenadas
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch(`${urlCheckCredentials}/${applicantId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                console.log('[MVE] Verificando credenciales para solicitante:', applicantId, 'Status:', response.status);
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('[MVE] Respuesta credenciales:', data);
                    
                    if (data.has_credentials && data.has_webservice_key) {
                        // Tiene todas las credenciales almacenadas
                        document.getElementById('useStoredCredentials').value = '1';
                        document.getElementById('storedCredsBanner').classList.remove('hidden');
                        document.getElementById('manualCredsContainer').classList.add('hidden');
                        setManualFieldsRequired(false);
                        lucide.createIcons();
                        console.log('[MVE] Credenciales almacenadas detectadas - usando automáticamente');
                    } else if (data.has_credentials) {
                        // Tiene cert/key pero no clave WS: ocultar solo cert/key
                        document.getElementById('useStoredCredentials').value = '1';
                        document.getElementById('storedCredsBanner').classList.remove('hidden');
                        // Mostrar solo campo de clave WS
                        document.querySelectorAll('#manualCredsContainer > div').forEach((el, idx) => {
                            // Ocultar cert (0), key (1), password (2), mantener clave_ws (3)
                            if (idx < 3) el.classList.add('hidden');
                        });
                        setManualFieldsRequired(false);
                        document.getElementById('clave_webservice').required = true;
                        lucide.createIcons();
                        console.log('[MVE] Credenciales parciales - falta clave WS');
                    } else {
                        console.log('[MVE] Sin credenciales almacenadas - modo manual');
                    }
                } else {
                    console.warn('[MVE] Error verificando credenciales:', response.status, response.statusText);
                }
            } catch (err) {
                console.error('[MVE] Error de conexión verificando credenciales:', err);
            }
        }
        
        // Cambiar a credenciales manuales
        function switchToManualCredentials() {
            document.getElementById('useStoredCredentials').value = '0';
            document.getElementById('storedCredsBanner').classList.add('hidden');
            document.getElementById('manualCredsContainer').classList.remove('hidden');
            // Mostrar todos los campos del contenedor manual
            document.querySelectorAll('#manualCredsContainer > div').forEach(el => {
                el.classList.remove('hidden');
            });
            setManualFieldsRequired(true);
            lucide.createIcons();
        }
        
        // Habilitar/deshabilitar required en campos manuales
        function setManualFieldsRequired(required) {
            const cert = document.getElementById('certificado');
            const key = document.getElementById('llave_privada');
            const pass = document.getElementById('password_llave');
            const ws = document.getElementById('clave_webservice');
            if (cert) cert.required = required;
            if (key) key.required = required;
            if (pass) pass.required = required;
            if (ws) ws.required = required;
        }
        
        // Mostrar vista previa antes de firmar
        async function mostrarVistaPreviaYFirmar(applicantId, empresaNombre) {
            vistaPreviaApplicantId = applicantId;
            vistaPreviaEmpresaNombre = empresaNombre;
            
            document.getElementById('vistaPreviaEmpresaNombre').textContent = empresaNombre;
            document.getElementById('modalVistaPrevia').style.display = 'flex';
            document.getElementById('modalVistaPrevia').classList.remove('hidden');
            lucide.createIcons();
            
            // Cargar la vista previa
            try {
                const response = await fetch(`${urlPreviewData}/${applicantId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('vistaPreviaContenido').innerHTML = generarContenidoVistaPrevia(data);
                    lucide.createIcons();
                } else {
                    document.getElementById('vistaPreviaContenido').innerHTML = `
                        <div class="text-center py-12">
                            <i data-lucide="alert-circle" class="w-12 h-12 text-red-500 mx-auto mb-4"></i>
                            <p class="text-red-600 font-semibold">Error al cargar la vista previa</p>
                            <p class="text-slate-500 mt-2">${data.message || 'Intente nuevamente'}</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('vistaPreviaContenido').innerHTML = `
                    <div class="text-center py-12">
                        <i data-lucide="alert-circle" class="w-12 h-12 text-red-500 mx-auto mb-4"></i>
                        <p class="text-red-600 font-semibold">Error de conexión</p>
                        <p class="text-slate-500 mt-2">${error.message}</p>
                    </div>
                `;
                lucide.createIcons();
            }
        }
        
        // Generar contenido HTML de vista previa (igual que mve-manual.js)
        function generarContenidoVistaPrevia(data) {
            let html = '';
            
            // Obtener datos — nuevo formato multi-COVE
            const coves = data.informacion_cove?.informacion_cove || [];
            const valorAduana = data.valor_aduana?.valor_en_aduana_data || {};
            const personasConsulta = data.datos_manifestacion?.persona_consulta || [];
            const documentos = data.documentos || [];
            
            // Función helper para generar bloque de un COVE
            function generarBloqueCove(cove, index) {
                const pedimentos = cove.pedimentos || [];
                const incrementables = cove.incrementables || [];
                const decrementables = cove.decrementables || [];
                const precioPagado = cove.precio_pagado || [];
                const precioPorPagar = cove.precio_por_pagar || [];
                const compensoPago = cove.compenso_pago || [];
                
                return `
                    <!-- COVE ${index + 1}: ${cove.numero_cove || ''} -->
                    <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-600 text-white text-xs font-bold">${index + 1}</span>
                            <h3 class="text-sm font-bold text-blue-900">COVE: ${cove.numero_cove || ''}</h3>
                        </div>
                        
                        <!-- Información Acuse de Valor -->
                        <div class="border-b-2 border-slate-300 pb-4 mb-4">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Información Acuse de Valor</h4>
                            <table class="w-full text-xs">
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="2">Método de valoración aduanera</td>
                                </tr>
                                <tr><td class="border border-slate-300 p-2" colspan="2">${cove.metodo_valoracion || ''}</td></tr>
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="2">INCOTERM</td>
                                </tr>
                                <tr><td class="border border-slate-300 p-2" colspan="2">${cove.incoterm || ''}</td></tr>
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="2">¿Existe vinculación entre importador y vendedor/proveedor?</td>
                                </tr>
                                <tr><td class="border border-slate-300 p-2" colspan="2">${cove.vinculacion === '1' || cove.vinculacion === 1 ? 'Sí' : (cove.vinculacion === '0' || cove.vinculacion === 0 ? 'No' : '')}</td></tr>
                            </table>
                        </div>
                        
                        <!-- Pedimentos -->
                        <div class="border-b-2 border-slate-300 pb-4 mb-4">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Pedimentos</h4>
                            <table class="w-full text-xs">
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold w-1/3">Pedimento</td>
                                    <td class="border border-slate-300 p-2 font-semibold w-1/3">Patente</td>
                                    <td class="border border-slate-300 p-2 font-semibold w-1/3">Aduana</td>
                                </tr>
                                ${pedimentos.length > 0 ? pedimentos.map(p => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${p.numeroDisplay || p.numero || ''}</td>
                                        <td class="border border-slate-300 p-2">${p.patente || ''}</td>
                                        <td class="border border-slate-300 p-2">${p.aduanaText || p.aduana || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                            </table>
                        </div>
                        
                        <!-- Incrementables -->
                        <div class="border-b-2 border-slate-300 pb-4 mb-4">
                            <h4 class="text-xs font-bold text-slate-700 mb-1 border-b border-slate-200 pb-1">Incrementables conforme al artículo 65 de la ley</h4>
                            <table class="w-full text-xs">
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Fecha de erogación</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                                </tr>
                                ${incrementables.length > 0 ? incrementables.map(inc => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${inc.fechaErogacion || ''}</td>
                                        <td class="border border-slate-300 p-2">${inc.importe ? '$' + parseFloat(inc.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                                        <td class="border border-slate-300 p-2">${inc.tipoMonedaText || inc.tipoMoneda || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Tipo de cambio</td>
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="2">¿Está a cargo del importador?</td>
                                </tr>
                                ${incrementables.length > 0 ? incrementables.map(inc => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${inc.tipoCambio || ''}</td>
                                        <td class="border border-slate-300 p-2" colspan="2">${inc.aCargoImportador !== undefined ? (inc.aCargoImportador ? 'Sí' : 'No') : ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                            </table>
                        </div>
                        
                        <!-- Decrementables -->
                        <div class="border-b-2 border-slate-300 pb-4 mb-4">
                            <h4 class="text-xs font-bold text-slate-700 mb-1 border-b border-slate-200 pb-1">Decrementables (Art. 66)</h4>
                            <table class="w-full text-xs">
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Fecha de erogación</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                                </tr>
                                ${decrementables.length > 0 ? decrementables.map(dec => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${dec.fechaErogacion || ''}</td>
                                        <td class="border border-slate-300 p-2">${dec.importe ? '$' + parseFloat(dec.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                                        <td class="border border-slate-300 p-2">${dec.tipoMonedaText || dec.tipoMoneda || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="3">Tipo de cambio</td>
                                </tr>
                                ${decrementables.length > 0 ? decrementables.map(dec => `
                                    <tr><td class="border border-slate-300 p-2" colspan="3">${dec.tipoCambio || ''}</td></tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                            </table>
                        </div>
                        
                        <!-- Precio pagado -->
                        <div class="border-b-2 border-slate-300 pb-4 mb-4">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Precio pagado</h4>
                            <table class="w-full text-xs">
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Fecha de pago</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Forma de pago</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Especifique</td>
                                </tr>
                                ${precioPagado.length > 0 ? precioPagado.map(p => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${p.fecha || ''}</td>
                                        <td class="border border-slate-300 p-2">${p.importe ? '$' + parseFloat(p.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                                        <td class="border border-slate-300 p-2">${p.formaPagoText || p.formaPago || ''}</td>
                                        <td class="border border-slate-300 p-2">${p.especifique || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="4">&nbsp;</td></tr>`}
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="3">Tipo de cambio</td>
                                </tr>
                                ${precioPagado.length > 0 ? precioPagado.map(p => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${p.tipoMonedaText || p.tipoMoneda || ''}</td>
                                        <td class="border border-slate-300 p-2" colspan="3">${p.tipoCambio || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="4">&nbsp;</td></tr>`}
                            </table>
                        </div>
                        
                        <!-- Precio por pagar -->
                        <div class="border-b-2 border-slate-300 pb-4 mb-4">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Precio por pagar</h4>
                            <table class="w-full text-xs">
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Fecha de pago</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Importe</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Forma de pago</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Especifique</td>
                                </tr>
                                ${precioPorPagar.length > 0 ? precioPorPagar.map(p => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${p.fecha || ''}</td>
                                        <td class="border border-slate-300 p-2">${p.importe ? '$' + parseFloat(p.importe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : ''}</td>
                                        <td class="border border-slate-300 p-2">${p.formaPagoText || p.formaPago || ''}</td>
                                        <td class="border border-slate-300 p-2">${p.especifique || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="4">&nbsp;</td></tr>`}
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Tipo de moneda</td>
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="3">Tipo de cambio</td>
                                </tr>
                                ${precioPorPagar.length > 0 ? precioPorPagar.map(p => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${p.tipoMonedaText || p.tipoMoneda || ''}</td>
                                        <td class="border border-slate-300 p-2" colspan="3">${p.tipoCambio || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="4">&nbsp;</td></tr>`}
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="4">Momento(s) o situación(es) cuando se realizará el pago</td>
                                </tr>
                                ${precioPorPagar.length > 0 ? precioPorPagar.map(p => `
                                    <tr><td class="border border-slate-300 p-2" colspan="4">${p.momentoSituacion || ''}</td></tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="4">&nbsp;</td></tr>`}
                            </table>
                        </div>
                        
                        <!-- Compenso pago -->
                        <div class="pb-2">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Compenso pago</h4>
                            <table class="w-full text-xs">
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold">Fecha de pago</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Forma de pago</td>
                                    <td class="border border-slate-300 p-2 font-semibold">Especifique</td>
                                </tr>
                                ${compensoPago.length > 0 ? compensoPago.map(c => `
                                    <tr>
                                        <td class="border border-slate-300 p-2">${c.fecha || ''}</td>
                                        <td class="border border-slate-300 p-2">${c.formaPagoText || c.formaPago || ''}</td>
                                        <td class="border border-slate-300 p-2">${c.especifique || ''}</td>
                                    </tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="3">Motivo por lo que se realizó</td>
                                </tr>
                                ${compensoPago.length > 0 ? compensoPago.map(c => `
                                    <tr><td class="border border-slate-300 p-2" colspan="3">${c.motivo || ''}</td></tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                                <tr class="bg-slate-100">
                                    <td class="border border-slate-300 p-2 font-semibold" colspan="3">Prestación de la mercancía</td>
                                </tr>
                                ${compensoPago.length > 0 ? compensoPago.map(c => `
                                    <tr><td class="border border-slate-300 p-2" colspan="3">${c.prestacionMercancia || ''}</td></tr>
                                `).join('') : `<tr><td class="border border-slate-300 p-2" colspan="3">&nbsp;</td></tr>`}
                            </table>
                        </div>
                    </div>
                `;
            }
            
            html += `
            <div class="bg-white border border-slate-300 shadow-lg">
                <!-- Header gob.mx -->
                <div class="bg-slate-600 text-white p-4">
                    <div class="text-2xl font-bold italic">gob.mx</div>
                    <div class="text-center mt-2">
                        <div class="text-sm font-bold tracking-wide">MANIFESTACIÓN DE VALOR</div>
                        <div class="text-xs">Ventanilla Digital Mexicana de Comercio Exterior</div>
                        <div class="text-xs">Promoción o solicitud en materia de comercio exterior</div>
                    </div>
                </div>
                
                <!-- Contenido Principal -->
                <div class="p-6 space-y-6">
                    
                    <!-- Datos de la Manifestación de valor -->
                    <div class="border-b-2 border-slate-300 pb-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Datos de la Manifestación de valor</h3>
                        <table class="w-full text-xs">
                            <tr class="bg-slate-100">
                                <td class="border border-slate-300 p-2 font-semibold w-1/3">RFC del importador</td>
                                <td class="border border-slate-300 p-2 w-2/3">Nombre o Razón social</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-2 font-medium">${data.datos_manifestacion?.rfc_importador || data.applicant?.rfc || ''}</td>
                                <td class="border border-slate-300 p-2">${data.applicant?.razon_social || ''}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- RFC's de consulta -->
                    <div class="border-b-2 border-slate-300 pb-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">RFC's de consulta</h3>
                        <table class="w-full text-xs">
                            <tr class="bg-slate-100">
                                <td class="border border-slate-300 p-2 font-semibold w-1/4">RFC</td>
                                <td class="border border-slate-300 p-2 w-2/4">Nombre o Razón social</td>
                            </tr>
                            ${personasConsulta.length > 0 ? personasConsulta.map(p => `
                                <tr>
                                    <td class="border border-slate-300 p-2 font-medium">${p.rfc || ''}</td>
                                    <td class="border border-slate-300 p-2">${p.razon_social || ''}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td class="border border-slate-300 p-2">&nbsp;</td>
                                    <td class="border border-slate-300 p-2">&nbsp;</td>
                                </tr>
                            `}
                            <tr class="bg-slate-100">
                                <td class="border border-slate-300 p-2 font-semibold" colspan="2">Tipo de figura</td>
                            </tr>
                            ${personasConsulta.length > 0 ? personasConsulta.map(p => `
                                <tr>
                                    <td class="border border-slate-300 p-2" colspan="2">${p.tipo_figura || ''}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td class="border border-slate-300 p-2" colspan="2">&nbsp;</td>
                                </tr>
                            `}
                        </table>
                    </div>
                    
                    <!-- Bloques de COVEs secuenciales -->
                    <div class="border-b-2 border-slate-300 pb-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-4 border-b border-slate-200 pb-1">Información de COVEs (${coves.length})</h3>
                        <div class="space-y-6">
                            ${coves.length > 0 ? coves.map((cove, i) => generarBloqueCove(cove, i)).join('') : `
                                <p class="text-sm text-slate-400 text-center py-4">No hay COVEs registrados</p>
                            `}
                        </div>
                    </div>
                    
                    <!-- Valor en aduana (totales across all COVEs) -->
                    <div class="border-b-2 border-slate-300 pb-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">Valor en aduana</h3>
                        <table class="w-full text-xs">
                            <tr class="bg-slate-100">
                                <td class="border border-slate-300 p-2 font-semibold">Importe total del precio pagado (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                                <td class="border border-slate-300 p-2 font-semibold">Importe total del precio por pagar (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_precio_pagado || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                                <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_precio_por_pagar || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                            </tr>
                            <tr class="bg-slate-100">
                                <td class="border border-slate-300 p-2 font-semibold">Importe total de incrementables (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                                <td class="border border-slate-300 p-2 font-semibold">Importe total de decrementables (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_incrementables || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                                <td class="border border-slate-300 p-2">$${parseFloat(valorAduana.total_decrementables || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                            </tr>
                            <tr class="bg-slate-100">
                                <td class="border border-slate-300 p-2 font-semibold" colspan="2">Total del valor en aduana (Sumatoria de los conceptos y deberán ser declarados en MN)</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 p-2 text-lg font-bold text-green-700" colspan="2">$${parseFloat(valorAduana.total_valor_aduana || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- eDocuments -->
                    <div class="pb-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-1">eDocuments</h3>
                        <table class="w-full text-xs">
                            <tr class="bg-slate-100">
                                <td class="border border-slate-300 p-2 font-semibold">eDocument</td>
                            </tr>
                            ${documentos.length > 0 ? documentos.map(doc => `
                                <tr>
                                    <td class="border border-slate-300 p-2">${doc.folio_edocument || ''}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td class="border border-slate-300 p-2">&nbsp;</td>
                                </tr>
                            `}
                        </table>
                    </div>
                    
                    <!-- Cadena Original -->
                    <div class="pt-4 border-t-2 border-slate-400">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-slate-700">Cadena Original (VUCEM)</h3>
                            <button type="button" id="toggleCadenaBtn" onclick="toggleCadenaOriginalPendientes()" 
                                    class="px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 border border-indigo-200 rounded hover:bg-indigo-50 transition-colors">
                                Mostrar cadena
                            </button>
                        </div>
                        <div id="contenidoCadenaOriginal" class="hidden">
                            <div class="bg-slate-900 text-green-400 text-xs font-mono p-4 rounded-lg overflow-x-auto whitespace-pre-wrap break-all max-h-64 overflow-y-auto">
                                ${data.cadena_original || '||Sin datos||'}
                            </div>
                            <p class="text-xs text-slate-500 mt-2 italic">
                                * Los campos vacíos se representan con || (doble pipe). El orden de los campos sigue el XSD de VUCEM.
                            </p>
                        </div>
                    </div>
                    
                </div>
            </div>
            `;
            
            return html;
        }
        
        // Toggle cadena original
        function toggleCadenaOriginalPendientes() {
            const content = document.getElementById('contenidoCadenaOriginal');
            const button = document.getElementById('toggleCadenaBtn');
            if (!content || !button) return;
            
            const isHidden = content.classList.contains('hidden');
            content.classList.toggle('hidden', !isHidden);
            button.textContent = isHidden ? 'Ocultar cadena' : 'Mostrar cadena';
        }
        
        // Cerrar vista previa
        function cerrarVistaPrevia() {
            document.getElementById('modalVistaPrevia').style.display = 'none';
            document.getElementById('modalVistaPrevia').classList.add('hidden');
            vistaPreviaApplicantId = null;
            vistaPreviaEmpresaNombre = null;
        }
        
        // Continuar a firmar después de verificar vista previa
        function continuarAFirmar() {
            // Guardar valores antes de cerrar el modal
            const applicantId = vistaPreviaApplicantId;
            const empresaNombre = vistaPreviaEmpresaNombre;
            
            cerrarVistaPrevia();
            
            if (applicantId && empresaNombre) {
                mostrarModalFirma(applicantId, empresaNombre);
            }
        }
        
        // Cerrar modal de firma
        function cerrarModalFirma() {
            document.getElementById('modalFirma').style.display = 'none';
            document.getElementById('modalFirma').classList.add('hidden');
            document.getElementById('formFirmaEnvio').reset();
            document.getElementById('certificadoFileName').textContent = 'Ningún archivo seleccionado';
            document.getElementById('llaveFileName').textContent = 'Ningún archivo seleccionado';
            document.getElementById('btnEnviarVucem').disabled = true;
            // Reset credenciales almacenadas
            document.getElementById('useStoredCredentials').value = '0';
            document.getElementById('storedCredsBanner').classList.add('hidden');
            document.getElementById('manualCredsContainer').classList.remove('hidden');
            document.querySelectorAll('#manualCredsContainer > div').forEach(el => {
                el.classList.remove('hidden');
            });
            setManualFieldsRequired(true);
        }
        
        // Mostrar modal de descartar
        function mostrarModalDescartar(applicantId, empresaNombre) {
            document.getElementById('descartarApplicantId').value = applicantId;
            document.getElementById('descartarEmpresaNombre').textContent = empresaNombre;
            document.getElementById('modalDescartar').style.display = 'flex';
            document.getElementById('modalDescartar').classList.remove('hidden');
            lucide.createIcons();
        }
        
        // Cerrar modal de descartar
        function cerrarModalDescartar() {
            document.getElementById('modalDescartar').style.display = 'none';
            document.getElementById('modalDescartar').classList.add('hidden');
        }
        
        // Confirmar descartar
        async function confirmarDescartar() {
            const applicantId = document.getElementById('descartarApplicantId').value;
            const btn = document.getElementById('btnConfirmarDescartar');
            const originalHtml = btn.innerHTML;
            
            try {
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>Eliminando...</span>';
                lucide.createIcons();
                
                const response = await fetch(`${urlDescartar}/${applicantId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    cerrarModalDescartar();
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al descartar la manifestación');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
        
        // Enviar formulario de firma
        document.getElementById('formFirmaEnvio')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const applicantId = document.getElementById('firmaApplicantId').value;
            const btn = document.getElementById('btnEnviarVucem');
            const btnTexto = document.getElementById('btnEnviarTexto');
            const originalText = btnTexto.textContent;
            
            try {
                btn.disabled = true;
                btnTexto.textContent = 'Procesando firma...';
                
                const formData = new FormData(this);
                formData.append('confirmacion', 'on');
                
                const response = await fetch(`${urlFirmarEnviar}/${applicantId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    cerrarModalFirma();
                    
                    // Mostrar mensaje de éxito
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-[10000] flex items-center gap-3';
                    alertDiv.innerHTML = `
                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                        <div>
                            <p class="font-bold">¡Enviado exitosamente!</p>
                            <p class="text-sm">${result.message}</p>
                            ${result.folio ? `<p class="text-sm mt-1">Folio: ${result.folio}</p>` : ''}
                        </div>
                    `;
                    document.body.appendChild(alertDiv);
                    lucide.createIcons();
                    
                    setTimeout(() => {
                        if (result.redirect_url) {
                            window.location.href = result.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    alert('Error: ' + result.message);
                    btn.disabled = false;
                    btnTexto.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la firma: ' + error.message);
                btn.disabled = false;
                btnTexto.textContent = originalText;
            }
        });
    </script>

</x-app-layout>
