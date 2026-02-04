<x-app-layout>
    {{-- Estilos específicos para esta vista (opcional, si usas vite puedes agregarlos ahí) --}}
    <style>
        .file-drop-zone:hover { border-color: #003399; background-color: #f0f9ff; }
        .form-select, .form-input { border-radius: 0.5rem; border-color: #e2e8f0; }
        .form-select:focus, .form-input:focus { border-color: #003399; ring: #003399; }
    </style>

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- NAV HEADER (Consistente con tu diseño anterior) --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">
                            Módulo de Digitalización
                        </span>
                    </div>
                    {{-- Información de usuario (simplificada para este header) --}}
                    <div class="flex items-center">
                        <div class="text-right mr-4 hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Usuario</p>
                            <p class="text-sm font-black text-[#003399]">{{ auth()->user()->full_name ?? auth()->user()->name }}</p>
                        </div>
                        <div class="h-10 w-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            
            {{-- ENCABEZADO DE PÁGINA --}}
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Regresar al Dashboard
                </a>

                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Digitalización <span class="text-[#003399]">VUCEM</span>
                </h2>
                <p class="text-slate-500 mt-2 text-lg">
                    Genera eDocuments firmados digitalmente listos para asociar a tus pedimentos.
                </p>
            </div>

            {{-- ALERTAS DE ÉXITO --}}
            @if (session('success'))
                <div class="mb-8 bg-emerald-50 border border-emerald-200 rounded-2xl p-6 shadow-sm flex items-start animate-fade-in-down">
                    <div class="flex-shrink-0 bg-emerald-100 rounded-full p-2 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-emerald-800">¡eDocument Generado con Éxito!</h3>
                        <p class="mt-1 text-emerald-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- ALERTAS DE ERROR --}}
            @if ($errors->any())
                <div class="mb-8 bg-red-50 border border-red-200 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-2 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-red-800">No pudimos procesar la solicitud</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TARJETA PRINCIPAL DEL FORMULARIO --}}
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                <form action="{{ route('digitalizacion.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3">
                        
                        {{-- COLUMNA IZQUIERDA: CONFIGURACIÓN Y CREDENCIALES --}}
                        <div class="lg:col-span-2 p-8 border-b lg:border-b-0 lg:border-r border-slate-100">
                            
                            {{-- SECCIÓN 1: DATOS GENERALES --}}
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-[#001a4d] mb-1 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-[#003399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    1. Datos del Firmante
                                </h3>
                                <p class="text-sm text-slate-500 mb-6 pl-7">Selecciona quién está firmando este documento.</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pl-7">
                                    {{-- RFC FIRMANTE --}}
                                    <div class="col-span-1 md:col-span-2">
                                        <x-input-label for="applicant_id" class="text-slate-700 font-bold" :value="__('Firmar como (RFC Dueño)')" />
                                        <div class="relative mt-1">
                                            <select id="applicant_id" name="applicant_id" class="block w-full pl-3 pr-10 py-3 text-base border-slate-300 focus:outline-none focus:ring-[#003399] focus:border-[#003399] sm:text-sm rounded-lg shadow-sm bg-slate-50">
                                                <option value="" disabled selected>-- Selecciona un RFC --</option>
                                                @foreach($solicitantes as $solicitante)
                                                    <option value="{{ $solicitante->id }}" {{ old('applicant_id') == $solicitante->id ? 'selected' : '' }}>
                                                        {{ $solicitante->applicant_rfc }} - {{ Str::limit($solicitante->business_name, 40) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- RFC CONSULTA --}}
                                    <div class="col-span-1 md:col-span-2">
                                        <div class="flex justify-between items-center">
                                            <x-input-label for="rfc_consulta" class="text-slate-700 font-bold" :value="__('RFC Agente Aduanal (Consulta)')" />
                                            <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Opcional</span>
                                        </div>
                                        <x-text-input id="rfc_consulta" class="block mt-1 w-full uppercase" 
                                                      type="text" 
                                                      name="rfc_consulta" 
                                                      placeholder="Ej: CDS041216MS3" 
                                                      :value="old('rfc_consulta')" />
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-100 my-8">

                            {{-- SECCIÓN 2: ARCHIVOS DE LA E.FIRMA --}}
                            <div>
                                <h3 class="text-lg font-bold text-[#001a4d] mb-1 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-[#003399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 19.464a3 3 0 01-.894.553l-4.485 1.58 1.58-4.485a3 3 0 01.553-.894l4.707-4.707a6 6 0 00-1.743-4.243 6 6 0 1111.314 0z"></path></svg>
                                    2. Archivos e.Firma (FIEL)
                                </h3>
                                <p class="text-sm text-slate-500 mb-6 pl-7">Sube los archivos .cer y .key vigentes del RFC seleccionado.</p>

                                <div class="pl-7 space-y-5">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        {{-- CER --}}
                                        <div>
                                            <label class="block text-sm font-bold text-slate-700 mb-1">Certificado (.cer)</label>
                                            <input type="file" name="certificado_file" accept=".cer" class="block w-full text-sm text-slate-500
                                                file:mr-4 file:py-2.5 file:px-4
                                                file:rounded-lg file:border-0
                                                file:text-sm file:font-bold
                                                file:bg-blue-50 file:text-[#003399]
                                                hover:file:bg-blue-100
                                                cursor-pointer border border-slate-300 rounded-lg p-1 bg-white
                                            "/>
                                        </div>
                                        {{-- KEY --}}
                                        <div>
                                            <label class="block text-sm font-bold text-slate-700 mb-1">Llave Privada (.key)</label>
                                            <input type="file" name="private_key_file" accept=".key" class="block w-full text-sm text-slate-500
                                                file:mr-4 file:py-2.5 file:px-4
                                                file:rounded-lg file:border-0
                                                file:text-sm file:font-bold
                                                file:bg-blue-50 file:text-[#003399]
                                                hover:file:bg-blue-100
                                                cursor-pointer border border-slate-300 rounded-lg p-1 bg-white
                                            "/>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        {{-- PASS WS --}}
                                        <div>
                                            <x-input-label for="vucem_password" class="text-slate-700 font-bold" :value="__('Contraseña Web Service')" />
                                            <x-text-input id="vucem_password" class="block mt-1 w-full" 
                                                          type="password" 
                                                          name="vucem_password" 
                                                          required
                                                          placeholder="Contraseña del portal VUCEM" />
                                        </div>
                                        {{-- PASS FIEL --}}
                                        <div>
                                            <div class="flex justify-between items-center">
                                                <x-input-label for="password_fiel" class="text-slate-700 font-bold" :value="__('Contraseña FIEL')" />
                                                <span class="text-[10px] text-slate-400">Opcional si es PEM</span>
                                            </div>
                                            <x-text-input id="password_fiel" class="block mt-1 w-full" type="password" name="password_fiel" placeholder="Contraseña de la llave privada" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- COLUMNA DERECHA: DOCUMENTO Y ACCIÓN --}}
                        <div class="col-span-1 bg-slate-50 p-8 flex flex-col h-full">
                            <h3 class="text-lg font-bold text-[#001a4d] mb-1 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#003399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                3. Documento
                            </h3>
                            <p class="text-sm text-slate-500 mb-6 pl-7">Selecciona el PDF y su tipo.</p>

                            <div class="space-y-6 flex-grow">
                                {{-- TIPO DOC --}}
                                <div>
                                    <x-input-label for="tipo_documento" class="text-slate-700 font-bold" :value="__('Tipo de Documento')" />
                                    <select id="tipo_documento" name="tipo_documento" class="mt-1 block w-full pl-3 pr-10 py-3 text-base border-slate-300 focus:outline-none focus:ring-[#003399] focus:border-[#003399] sm:text-sm rounded-lg shadow-sm bg-white">
                                        <option value="" disabled selected>-- Seleccionar --</option>
                                        @foreach($tiposDocumento as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('tipo_documento') == $id ? 'selected' : '' }}>
                                                {{ $id }} - {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- DROPZONE --}}
                                <div>
                                    <x-input-label for="archivo" class="text-slate-700 font-bold mb-2" :value="__('Archivo PDF')" />
                                    <div class="file-drop-zone mt-1 flex justify-center px-6 pt-10 pb-10 border-2 border-slate-300 border-dashed rounded-xl bg-white transition-all group relative cursor-pointer">
                                        <input id="archivo" name="archivo" type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".pdf" />
                                        
                                        <div class="space-y-2 text-center relative z-0">
                                            <div class="mx-auto h-16 w-16 bg-blue-50 text-[#003399] rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                                                <svg class="h-8 w-8" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </div>
                                            <div class="text-sm text-slate-600">
                                                <span class="font-bold text-[#003399]">Clic para subir</span> o arrastra
                                            </div>
                                            <p class="text-xs text-slate-400">PDF hasta 20MB</p>
                                        </div>
                                    </div>
                                    {{-- FEEDBACK JS --}}
                                    <div id="file-feedback" class="hidden mt-3 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center animate-pulse">
                                        <svg class="w-5 h-5 text-[#003399] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span id="file-name-text" class="text-sm font-bold text-[#003399] truncate"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- BOTÓN DE ACCIÓN --}}
                            <div class="mt-8 pt-6 border-t border-slate-200">
                                <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-gradient-to-r from-[#003399] to-[#001a4d] hover:from-[#002266] hover:to-[#001133] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#003399] transform transition hover:-translate-y-0.5">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    FIRMAR Y DIGITALIZAR
                                </button>
                                <p class="text-xs text-center text-slate-400 mt-3">
                                    Se generará un eDocument válido ante el SAT/VUCEM.
                                </p>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </main>
    </div>

    {{-- SCRIPT MEJORADO PARA FEEDBACK VISUAL --}}
    <script>
        const fileInput = document.getElementById('archivo');
        const feedbackBox = document.getElementById('file-feedback');
        const fileNameText = document.getElementById('file-name-text');
        const dropZone = document.querySelector('.file-drop-zone');

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                fileNameText.textContent = e.target.files[0].name;
                feedbackBox.classList.remove('hidden');
                dropZone.classList.add('border-[#003399]', 'bg-blue-50');
            } else {
                feedbackBox.classList.add('hidden');
                dropZone.classList.remove('border-[#003399]', 'bg-blue-50');
            }
        });

        // Efectos Drag & Drop visuales
        fileInput.addEventListener('dragenter', () => dropZone.classList.add('border-[#003399]', 'bg-blue-50'));
        fileInput.addEventListener('dragleave', () => dropZone.classList.remove('border-[#003399]', 'bg-blue-50'));
        fileInput.addEventListener('drop', () => dropZone.classList.add('border-[#003399]', 'bg-blue-50'));
    </script>
</x-app-layout>