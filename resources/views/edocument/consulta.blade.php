<x-app-layout>
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
                            {{ $pageTitle ?? 'Consulta VUCEM' }}
                        </span>
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

        {{-- CONTENIDO PRINCIPAL --}}
        <main class="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            
            {{-- Encabezado de la página --}}
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>

                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    {{ $pageTitle ?? 'Consulta' }} <span class="text-[#003399]">VUCEM</span>
                </h2>
                <p class="text-slate-500 mt-2">
                    {{ $description ?? 'Ingresa el folio del eDocument para consultar su estado y descargar archivos.' }}
                </p>
                
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2 flex items-center">
                        <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                        Información sobre Credenciales
                    </h3>
                    <div class="text-xs text-blue-700 space-y-1">
                        <p>• <strong>RFC:</strong> Se obtiene automáticamente de su perfil o de los RFC asociados.</p>
                        <p>• <strong>Clave WebService:</strong> Se utiliza la configurada en el solicitante seleccionado.</p>
                        <p>• <strong>eFirma:</strong> Es obligatorio subir los archivos .cer y .key vigentes para firmar la solicitud.</p>
                    </div>
                </div>
            </div>

            {{-- Alertas de Error --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 animate-fade-in-up">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-red-800 mb-2">Error de Validación</h3>
                            <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- FORMULARIO DE CONSULTA --}}
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8 mb-8">
                <form method="POST" action="{{ $formRoute ?? route('edocument.consulta') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 gap-6">
                        @if(isset($solicitantes) && $solicitantes->count() > 0)
                            {{-- Selección de Solicitante --}}
                            <div>
                                <label for="solicitante_id" class="block text-sm font-semibold text-slate-700 mb-2">Solicitante (RFC Consultante)</label>
                                <select name="solicitante_id" id="solicitante_id" class="form-input w-full bg-slate-50" required>
                                    <option value="">Seleccione un solicitante...</option>
                                    @foreach($solicitantes as $solicitante)
                                        <option value="{{ $solicitante->id }}" {{ old('solicitante_id', $solicitante_seleccionado ?? '') == $solicitante->id ? 'selected' : '' }}>
                                            {{ $solicitante->applicant_rfc }} - {{ $solicitante->business_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Folio eDocument --}}
                            <div>
                                <label for="folio_edocument" class="block text-sm font-semibold text-slate-700 mb-2">Folio eDocument</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="hash" class="h-5 w-5 text-slate-400"></i>
                                    </div>
                                    <input type="text" name="folio_edocument" id="folio_edocument" 
                                           value="{{ old('folio_edocument', $folio ?? '') }}" 
                                           class="form-input w-full pl-10 uppercase font-mono placeholder:normal-case" 
                                           placeholder="Ej. 0000000000000" required />
                                </div>
                            </div>

                            {{-- Archivos eFirma --}}
                            <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center">
                                    <i data-lucide="shield" class="w-4 h-4 mr-2 text-[#003399]"></i>
                                    Firma Electrónica (Requerida por VUCEM)
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="certificado" class="block text-xs font-semibold text-slate-500 mb-2 uppercase">Certificado (.cer)</label>
                                        <input type="file" name="certificado" id="certificado" class="file-input w-full text-sm" accept=".cer,.crt,.pem" required />
                                    </div>
                                    <div>
                                        <label for="llave_privada" class="block text-xs font-semibold text-slate-500 mb-2 uppercase">Llave Privada (.key)</label>
                                        <input type="file" name="llave_privada" id="llave_privada" class="file-input w-full text-sm" accept=".key,.pem" required />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="contrasena_llave" class="block text-xs font-semibold text-slate-500 mb-2 uppercase">Contraseña de la Llave</label>
                                        <input type="password" name="contrasena_llave" id="contrasena_llave" class="form-input w-full" placeholder="••••••••" required />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" class="btn-primary shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                                    <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                    Consultar en VUCEM
                                </button>
                            </div>
                        @else
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
                                <i data-lucide="alert-triangle" class="w-10 h-10 text-amber-500 mx-auto mb-3"></i>
                                <h3 class="text-amber-800 font-bold">Sin Solicitantes</h3>
                                <p class="text-amber-700 text-sm mt-1">No hay solicitantes registrados en su cuenta. Debe registrar uno primero para obtener las credenciales de conexión.</p>
                                <a href="{{ route('applicants.create') }}" class="btn-secondary mt-4 inline-flex">Registrar Solicitante</a>
                            </div>
                        @endif
                    </div>
                </form>
            </div>

            {{-- RESULTADOS DE LA CONSULTA --}}
            @if(isset($result))
                <div class="mt-8 space-y-6">
                    
                    {{-- Tarjeta de Estado --}}
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden animate-fade-in-up">
                        <div class="bg-slate-50 px-8 py-4 border-b border-slate-200 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-[#001a4d]">Respuesta del Servidor</h3>
                            @if($result['success'])
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200 shadow-sm">
                                    <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> ÉXITO
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 shadow-sm">
                                    <i data-lucide="x-circle" class="w-3 h-3 mr-1"></i> ERROR
                                </span>
                            @endif
                        </div>
                        <div class="p-8">
                            <p class="text-slate-600 mb-2 font-medium">
                                Mensaje VUCEM: <span class="font-normal">{{ $result['message'] }}</span>
                            </p>
                            @if(isset($folio))
                                <p class="text-slate-600 text-sm">
                                    Folio Consultado: <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-800 font-bold">{{ $folio }}</span>
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- DECISIÓN INTELIGENTE DE VISTA --}}
                    
                    {{-- CASO A: ES UN COVE (Tiene datos estructurados) --}}
                    @if(isset($result['cove_data']) && !empty($result['cove_data']))
                        
                        {{-- 1. Mostrar detalles visuales del COVE --}}
                        @include('edocument.partials.cove-details', ['cove' => $result['cove_data']])
                        
                        {{-- 2. Mostrar archivos XML/Acuses adjuntos (Estilo lista simple) --}}
                        @if(isset($files) && count($files) > 0)
                            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
                                <h4 class="text-lg font-bold text-[#001a4d] mb-4 flex items-center">
                                    <i data-lucide="paperclip" class="w-5 h-5 mr-2"></i>
                                    Archivos del Trámite (XML / Acuses)
                                </h4>
                                <ul class="divide-y divide-slate-200 border border-slate-200 rounded-lg overflow-hidden">
                                    @foreach($files as $file)
                                        <li class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                            <div class="flex items-center">
                                                <div class="bg-blue-100 p-2 rounded-lg text-blue-600 mr-3">
                                                    <i data-lucide="file-code" class="w-5 h-5"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-slate-700">{{ $file['name'] }}</p>
                                                    <p class="text-xs text-slate-400">{{ $file['mime'] }}</p>
                                                </div>
                                            </div>
                                            <a href="{{ route('edocument.descargar', $file['token']) }}" class="btn-secondary text-xs px-3 py-2">
                                                <i data-lucide="download" class="w-3 h-3 mr-1.5"></i> Descargar
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                    {{-- CASO B: ES UNA DIGITALIZACIÓN (Solo tiene archivos, sin datos COVE) --}}
                    @elseif(isset($files) && count($files) > 0)
                        
                        {{-- Usamos la vista especializada para expedientes digitales --}}
                        @include('edocument.partials.digitization-details', ['files' => $files, 'folio' => $folio])

                    {{-- CASO C: RESPUESTA VACÍA --}}
                    @else
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center animate-fade-in-up">
                            <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                <i data-lucide="search-x" class="w-10 h-10 text-slate-400"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-700">Sin Documentos Visuales</h3>
                            <p class="text-slate-500 mt-2 max-w-md mx-auto text-sm">
                                La consulta fue procesada por VUCEM, pero no se devolvieron datos de mercancías (COVE) ni archivos adjuntos (Digitalización) para visualizar.
                            </p>
                        </div>
                    @endif

                </div>
            @endif
        </main>
    </div>
</x-app-layout>