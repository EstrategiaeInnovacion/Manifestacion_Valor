<x-app-layout>
    <x-slot name="title">Nuevo Solicitante</x-slot>
    @vite(['resources/css/applicant-form.css', 'resources/js/applicant-form.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegación --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Registrar Solicitante</span>
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

        <main class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('applicants.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar a Lista de Solicitantes
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">Registrar <span class="text-[#003399]">Solicitante</span></h2>
                <p class="text-slate-500 mt-2">Complete los datos del solicitante para operaciones en VUCEM</p>
            </div>

            @if ($errors->any())
                <div class="alert-error">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <div>
                        <p class="font-semibold">Se encontraron los siguientes errores:</p>
                        <ul class="list-disc list-inside mt-2 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="form-card">
                <form method="POST" action="{{ route('applicants.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- ═══════════════════════════════════════════════════════ --}}
                    {{-- SECCIÓN 1: Datos del Solicitante (obligatorios)        --}}
                    {{-- ═══════════════════════════════════════════════════════ --}}
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                            Datos del Solicitante
                        </h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="applicant_email" class="form-label">Correo Electrónico del Solicitante <span class="text-red-500">*</span></label>
                                <input type="email" id="applicant_email" name="applicant_email" value="{{ old('applicant_email') }}"
                                       class="form-input" placeholder="correo@ejemplo.com" required>
                                <p class="text-xs text-slate-500 mt-1">Ingresa el correo electrónico del solicitante (puede ser diferente al de tu cuenta)</p>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="applicant_rfc" class="form-label">RFC del Solicitante <span class="text-red-500">*</span></label>
                                <input type="text" id="applicant_rfc" name="applicant_rfc" value="{{ old('applicant_rfc') }}" 
                                       class="form-input" maxlength="13" placeholder="XAXX010101000" required>
                            </div>

                            <div class="form-group">
                                <label for="business_name" class="form-label">Razón Social <span class="text-red-500">*</span></label>
                                <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" 
                                       class="form-input" placeholder="Nombre o Razón Social" required>
                            </div>
                        </div>

                        {{-- Asignación de Usuarios (múltiple) --}}
                        @if(isset($usersForAssignment) && $usersForAssignment->count() > 0)
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i data-lucide="user-plus" class="w-4 h-4 inline-block mr-1"></i>
                                    Asignar a Usuarios
                                </label>
                                <div class="border border-slate-200 rounded-lg divide-y divide-slate-100 max-h-48 overflow-y-auto">
                                    @foreach($usersForAssignment as $userOption)
                                        <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 cursor-pointer">
                                            <input type="checkbox"
                                                   name="assigned_user_ids[]"
                                                   value="{{ $userOption->id }}"
                                                   {{ in_array($userOption->id, old('assigned_user_ids', [])) ? 'checked' : '' }}
                                                   class="w-4 h-4 text-blue-600 border-slate-300 rounded">
                                            <span class="text-sm text-slate-700">
                                                <span class="font-semibold">{{ $userOption->full_name }}</span>
                                                <span class="text-slate-400">({{ $userOption->email }})</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Los usuarios seleccionados podrán ver y operar con este solicitante</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- ═══════════════════════════════════════════════════════ --}}
                    {{-- SECCIÓN 2: Sellos VUCEM (opcionales)                   --}}
                    {{-- ═══════════════════════════════════════════════════════ --}}
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                            Sellos VUCEM
                            <span class="text-xs font-normal text-slate-400 ml-2">(Opcional)</span>
                        </h3>

                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                            <div class="flex gap-3">
                                <i data-lucide="info" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"></i>
                                <div class="text-sm text-blue-800">
                                    <p class="font-semibold mb-1">Información sobre Sellos VUCEM</p>
                                    <p>Si proporciona los sellos VUCEM (.key, .cer), la contraseña y/o la clave de Web Service, estos se guardarán 
                                    <strong>encriptados</strong> en la base de datos y se cargarán automáticamente cuando realice operaciones de 
                                    <strong>Manifestación de Valor</strong>, <strong>Digitalización de Documentos</strong> y <strong>Consulta de COVE</strong>.</p>
                                    <p class="mt-2">Si no los proporciona aquí, deberá ingresarlos manualmente cada vez que realice una operación.</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="vucem_key_file" class="form-label">
                                    <i data-lucide="key" class="w-4 h-4 inline-block mr-1"></i>
                                    Archivo .key (Sello VUCEM)
                                </label>
                                <input type="file" id="vucem_key_file" name="vucem_key_file" 
                                       class="form-input file-input" accept=".key">
                                <p class="text-xs text-slate-500 mt-1">Archivo de llave privada del sello VUCEM</p>
                            </div>

                            <div class="form-group">
                                <label for="vucem_cert_file" class="form-label">
                                    <i data-lucide="file-badge" class="w-4 h-4 inline-block mr-1"></i>
                                    Archivo .cer (Certificado VUCEM)
                                </label>
                                <input type="file" id="vucem_cert_file" name="vucem_cert_file" 
                                       class="form-input file-input" accept=".cer,.cert,.crt">
                                <p class="text-xs text-slate-500 mt-1">Archivo de certificado del sello VUCEM</p>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="vucem_password" class="form-label">
                                    <i data-lucide="lock" class="w-4 h-4 inline-block mr-1"></i>
                                    Contraseña del Sello
                                </label>
                                <div class="relative">
                                    <input type="password" id="vucem_password" name="vucem_password" 
                                           class="form-input pr-10" placeholder="Contraseña del sello VUCEM">
                                    <button type="button" onclick="togglePassword('vucem_password')" 
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <i data-lucide="eye" class="w-4 h-4" id="vucem_password_icon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="vucem_webservice_key" class="form-label">
                                    <i data-lucide="globe" class="w-4 h-4 inline-block mr-1"></i>
                                    Clave Web Service VUCEM
                                </label>
                                <div class="relative">
                                    <input type="password" id="vucem_webservice_key" name="vucem_webservice_key" 
                                           class="form-input pr-10" placeholder="Clave de Web Service">
                                    <button type="button" onclick="togglePassword('vucem_webservice_key')" 
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <i data-lucide="eye" class="w-4 h-4" id="vucem_webservice_key_icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════ --}}
                    {{-- SECCIÓN 3: Aviso de Privacidad y Consentimiento       --}}
                    {{-- ═══════════════════════════════════════════════════════ --}}
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i data-lucide="file-text" class="w-5 h-5"></i>
                            Aviso de Privacidad y Consentimiento
                        </h3>

                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-2 max-h-64 overflow-y-auto" id="privacyScrollBox"
                            onscroll="checkPrivacyScroll(this)">
                            {!! \App\Models\AppSetting::get('aviso_privacidad_sellos') !!}
                        </div>
                        <p id="scrollNotice" class="text-xs text-amber-600 font-semibold flex items-center gap-1 mb-3">
                            <i data-lucide="arrow-down" class="w-3 h-3"></i>
                            Desplázate hasta el final del aviso para activar el consentimiento.
                        </p>
                        <p id="scrollDone" class="text-xs text-emerald-600 font-semibold hidden flex items-center gap-1 mb-3">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            Has leído el aviso. Ya puedes aceptar.
                        </p>

                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-slate-50 transition-colors" id="privacyLabel">
                            <input type="checkbox" name="privacy_consent" value="1" id="privacy_consent"
                                   class="mt-1 h-5 w-5 rounded border-slate-300 text-[#003399] focus:ring-[#003399] disabled:opacity-40 disabled:cursor-not-allowed"
                                   disabled
                                   {{ old('privacy_consent') ? 'checked' : '' }}>
                            <span class="text-sm text-slate-700">
                                <strong>Acepto y autorizo</strong> el almacenamiento encriptado de mis sellos VUCEM, contraseña y clave de Web Service
                                para las operaciones de Manifestación de Valor, Digitalización de Documentos y Consulta de COVE.
                                He leído y comprendido el aviso de privacidad anterior.
                                <a href="{{ route('legal.privacidad') }}" target="_blank" class="text-[#003399] underline ml-1">Ver aviso completo →</a>
                            </span>
                        </label>
                        <p class="text-xs text-slate-400 mt-2 ml-8">
                            <i data-lucide="info" class="w-3 h-3 inline-block mr-1"></i>
                            Este consentimiento es necesario solo si proporciona sellos VUCEM.
                        </p>
                    </div>

                    <script>
                    (function() {
                        var box   = document.getElementById('privacyScrollBox');
                        var cb    = document.getElementById('privacy_consent');
                        var note  = document.getElementById('scrollNotice');
                        var done  = document.getElementById('scrollDone');
                        function unlock() {
                            cb.disabled = false;
                            note.classList.add('hidden');
                            done.classList.remove('hidden');
                        }
                        window.checkPrivacyScroll = function(el) {
                            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 10) unlock();
                        };
                        // Auto-unlock if content is short (no scroll needed)
                        if (box && box.scrollHeight <= box.clientHeight + 10) unlock();
                    })();
                    </script>

                    <div class="form-actions">
                        <a href="{{ route('applicants.index') }}" class="btn-secondary">
                            <i data-lucide="x" class="w-5 h-5 mr-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn-primary">
                            <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                            Registrar Solicitante
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Validar que si se suben archivos VUCEM, se necesita consentimiento
        document.querySelector('form').addEventListener('submit', function(e) {
            const keyFile = document.getElementById('vucem_key_file').files.length > 0;
            const certFile = document.getElementById('vucem_cert_file').files.length > 0;
            const password = document.getElementById('vucem_password').value.trim() !== '';
            const wsKey = document.getElementById('vucem_webservice_key').value.trim() !== '';
            const consent = document.getElementById('privacy_consent').checked;

            if ((keyFile || certFile || password || wsKey) && !consent) {
                e.preventDefault();
                alert('Para guardar los sellos VUCEM debe aceptar el aviso de privacidad y consentimiento.');
                document.getElementById('privacy_consent').focus();
            }
        });
    </script>
</x-app-layout>
