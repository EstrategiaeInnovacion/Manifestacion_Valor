<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Panel de Control</span>
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
            
            {{-- ALERTA DE SEGURIDAD (MINIMALISTA & LLAMATIVA) --}}
            <div class="mb-8 rounded-lg bg-[#001a4d] p-3 shadow-lg shadow-blue-900/20 flex items-center justify-between border-l-4 border-emerald-400 relative overflow-hidden group">
                {{-- Efecto de brillo al pasar el mouse --}}
                <div class="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                <div class="flex items-center gap-4 relative z-10">
                    {{-- Icono con Pulso --}}
                    <div class="flex-shrink-0 relative flex items-center justify-center w-8 h-8 rounded-full bg-white/10">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-20 animate-ping"></span>
                        <i data-lucide="shield-check" class="text-emerald-400 w-5 h-5 relative z-10"></i>
                    </div>

                    <div class="text-sm text-blue-100 font-medium">
                        <span class="text-white font-bold tracking-wide mr-1">PRIVACIDAD SEGURA:</span>
                        Tu e.firma no se almacena y se elimina tras su uso.
                    </div>
                </div>

                {{-- Badge derecho --}}
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-black/20 border border-white/5">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></div>
                    <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest">Encrypted RAM Only</span>
                </div>
            </div>

            {{-- HEADER CORPORATIVO --}}
            <div class="mb-12 bg-white border border-slate-200 rounded-sm p-10 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 p-4 opacity-[0.03] pointer-events-none">
                    <i data-lucide="layers" class="w-64 h-64"></i>
                </div>

                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="bg-[#001a4d] text-white text-[10px] font-bold px-2 py-1 tracking-[0.2em]">V.2.0</span>
                            <div class="h-px w-8 bg-slate-300"></div>
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-widest">Global Asset Management</span>
                        </div>
                        
                        <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo" class="h-20 object-contain">
                    </div>

                    <div class="mt-8 md:mt-0 text-left md:text-right border-t md:border-t-0 md:border-l border-slate-100 pt-6 md:pt-0 md:pl-12">
                        <p class="text-sm font-bold text-[#001a4d] uppercase tracking-widest mb-2">Message of the Day</p>
                        <p class="text-2xl text-slate-500 font-light leading-snug max-w-md">
                            "La excelencia no es un acto, es un <span class="text-[#001a4d] font-semibold">estándar operativo.</span>"
                        </p>
                    </div>
                </div>
            </div>

            @php
                $isAdmin = auth()->user()->role === 'SuperAdmin' || auth()->user()->role === 'Admin';
            @endphp

            {{-- SECCIÓN 1: ADMINISTRACIÓN Y GESTIÓN --}}
            <div class="mb-12">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-8 w-1 bg-slate-300"></div>
                    <h3 class="text-xl font-bold text-slate-700 uppercase tracking-widest">Administración & Gestión</h3>
                    <div class="h-px flex-grow bg-slate-200"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    {{-- TARJETA: USUARIOS (Solo Admin) --}}
                    @if($isAdmin)
                        <a href="{{ route('users.index') }}" class="modern-card group border-t-4 border-t-slate-800">
                            <div class="card-content">
                                <div class="icon-box bg-slate-50 text-slate-700 group-hover:bg-slate-800 group-hover:text-white transition-all duration-500">
                                    <i data-lucide="users-round" class="w-8 h-8"></i>
                                </div>
                                <h3 class="text-xl font-bold text-[#001a4d] mt-6">Gestión de Usuarios</h3>
                                <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                    Administra el personal y accesos.
                                </p>
                                <div class="mt-8 flex items-center text-slate-700 font-bold text-sm">
                                    Configurar 
                                    <i data-lucide="settings-2" class="w-4 h-4 ml-2 group-hover:rotate-90 transition-transform"></i>
                                </div>
                            </div>
                        </a>
                    @endif

                    {{-- TARJETA: LICENCIAS (Solo SuperAdmin) --}}
                    @if(auth()->user()->role === 'SuperAdmin')
                        <a href="{{ route('admin.licenses.index') }}" class="modern-card group border-t-4 border-t-amber-500">
                            <div class="card-content">
                                <div class="icon-box bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition-all duration-500">
                                    <i data-lucide="key-round" class="w-8 h-8"></i>
                                </div>
                                <h3 class="text-xl font-bold text-[#001a4d] mt-6">Licencias & Límites</h3>
                                <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                    Gestiona licencias y límites de administradores.
                                </p>
                                <div class="mt-8 flex items-center text-amber-600 font-bold text-sm">
                                    Administrar 
                                    <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                                </div>
                            </div>
                        </a>
                    @endif

                    {{-- TARJETA: SOLICITANTES (Para todos) --}}
                    <a href="{{ route('applicants.index') }}" class="modern-card group border-t-4 border-t-slate-600">
                        <div class="card-content">
                            <div class="icon-box bg-slate-50 text-slate-600 group-hover:bg-slate-600 group-hover:text-white transition-all duration-500">
                                <i data-lucide="briefcase" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Solicitantes</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Base de datos de Empresas y RFCs.
                            </p>
                            <div class="mt-8 flex items-center text-slate-600 font-bold text-sm">
                                Gestionar 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            {{-- SECCIÓN 2: CENTRO DE OPERACIONES --}}
            <div>
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-8 w-1 bg-[#003399]"></div>
                    <h3 class="text-xl font-bold text-[#001a4d] uppercase tracking-widest">Centro de Operaciones</h3>
                    <div class="h-px flex-grow bg-slate-200"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    {{-- MVE --}}
                    <button type="button" onclick="openMveModal()" class="modern-card group border-t-4 border-t-[#003399] text-left w-full">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="file-text" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Crear Manifestación</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Generación de documentos de valor.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Ejecutar 
                                <i data-lucide="play" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </button>

                    {{-- DIGITALIZACIÓN --}}
                    <a href="{{ route('digitalizacion.create') }}" class="modern-card group border-t-4 border-t-[#003399]">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="scan-line" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Digitalizar Documento</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Firma y generación de Acuses (eDocs).
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Procesar 
                                <i data-lucide="cpu" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    {{-- CONSULTA COVE --}}
                    <a href="{{ route('cove.consulta.index') }}" class="modern-card group border-t-4 border-t-emerald-600 relative overflow-hidden transition-all hover:shadow-xl">
                        <div class="card-content">
                            <div class="icon-box bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-500">
                                <i data-lucide="badge-dollar-sign" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Consulta COVE</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Extracción de datos de VUCEM.
                            </p>
                            <div class="mt-8 flex items-center text-emerald-700 font-bold text-sm">
                                Consultar 
                                <i data-lucide="search" class="w-4 h-4 ml-2 group-hover:scale-110 transition-transform"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

        
            {{-- SECCIÓN 3: SOPORTE & AYUDA --}}
            <div class="mt-12">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-8 w-1 bg-amber-500"></div>
                    <h3 class="text-xl font-bold text-slate-700 uppercase tracking-widest">Soporte & Ayuda</h3>
                    <div class="h-px flex-grow bg-slate-200"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- TARJETA: SOPORTE --}}
                    <button type="button" onclick="openSupportModal()" class="modern-card group border-t-4 border-t-amber-500 text-left w-full">
                        <div class="card-content">
                            <div class="icon-box bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition-all duration-500">
                                <i data-lucide="headset" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Contactar Soporte</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                ¿Tienes algún problema o sugerencia? Envíanos un mensaje y te contactaremos.
                            </p>
                            <div class="mt-8 flex items-center text-amber-600 font-bold text-sm">
                                Enviar Ticket
                                <i data-lucide="send" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </main>

        {{-- Modal para elegir tipo de MVE (SE MANTIENE IGUAL) --}}
        <div id="mveModal" class="mve-modal">
            <div class="mve-modal-overlay" onclick="closeMveModal()"></div>
            <div class="mve-modal-content">
                <div class="mve-modal-header">
                    <h3 class="text-2xl font-black text-[#001a4d]">¿Desea realizar la MVE?</h3>
                    <button type="button" onclick="closeMveModal()" class="mve-modal-close">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <div class="mve-modal-body">
                    <p class="text-slate-600 mb-8">Seleccione el método para crear la Manifestación de Valor:</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <button type="button" onclick="selectMveManual()" class="mve-option-card">
                            <div class="mve-option-icon">
                                <i data-lucide="pencil" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-xl font-bold text-[#001a4d] mb-2">Manual</h4>
                            <p class="text-sm text-slate-500">Formulario manual</p>
                        </button>
                        
                        <button type="button" onclick="selectMveArchivoM()" class="mve-option-card">
                            <div class="mve-option-icon">
                                <i data-lucide="file-up" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-xl font-bold text-[#001a4d] mb-2">Archivo M</h4>
                            <p class="text-sm text-slate-500">Desde archivo</p>
                        </button>
                        
                        <button type="button" onclick="selectMvePendientes()" class="mve-option-card mve-option-pendientes">
                            <div class="mve-option-icon mve-icon-warning">
                                <i data-lucide="file-clock" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-xl font-bold text-[#001a4d] mb-2">Pendientes</h4>
                            <p class="text-sm text-slate-500">Continuar borrador</p>
                            @if($mvePendientesCount > 0)
                                <div class="mve-badge">{{ $mvePendientesCount }}</div>
                            @endif
                        </button>
                        
                        <button type="button" onclick="selectMveCompletadas()" class="mve-option-card mve-option-completadas">
                            <div class="mve-option-icon mve-icon-success">
                                <i data-lucide="check-circle" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-xl font-bold text-[#001a4d] mb-2">Completadas</h4>
                            <p class="text-sm text-slate-500">Historial</p>
                            @if($mveCompletadasCount > 0)
                                <div class="mve-badge mve-badge-success">{{ $mveCompletadasCount }}</div>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal de Soporte --}}
        <div id="supportModal" class="mve-modal">
            <div class="mve-modal-overlay" onclick="closeSupportModal()"></div>
            <div class="mve-modal-content" style="max-width:560px;">
                <div class="mve-modal-header">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <i data-lucide="headset" class="w-5 h-5 text-amber-600"></i>
                        </div>
                        <h3 class="text-2xl font-black text-[#001a4d]">Soporte Técnico</h3>
                    </div>
                    <button type="button" onclick="closeSupportModal()" class="mve-modal-close">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <div class="mve-modal-body">
                    <form id="supportForm" onsubmit="submitSupportForm(event)">
                        @csrf
                        
                        {{-- Info del usuario (readonly) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Nombre</label>
                                <div class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-600 font-medium">
                                    {{ auth()->user()->full_name }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Correo</label>
                                <div class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-600 font-medium truncate">
                                    {{ auth()->user()->email }}
                                </div>
                            </div>
                        </div>
                        
                        {{-- Categoría --}}
                        <div class="mb-5">
                            <label for="supportCategory" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Categoría</label>
                            <select id="supportCategory" name="category" required
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all outline-none appearance-none cursor-pointer">
                                <option value="" disabled selected>Selecciona una categoría...</option>
                                <option value="Error en el sistema">Error en el sistema</option>
                                <option value="Problema con e.firma">Problema con e.firma</option>
                                <option value="Problema con VUCEM">Problema con VUCEM</option>
                                <option value="Solicitud de cambio">Solicitud de cambio</option>
                                <option value="Duda general">Duda general</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        {{-- Asunto --}}
                        <div class="mb-5">
                            <label for="supportSubject" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Asunto</label>
                            <input type="text" id="supportSubject" name="subject" required maxlength="255"
                                placeholder="Describe brevemente el caso..."
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all outline-none placeholder:text-slate-300">
                        </div>

                        {{-- Descripción --}}
                        <div class="mb-6">
                            <label for="supportDescription" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Descripción</label>
                            <textarea id="supportDescription" name="description" required maxlength="5000" rows="5"
                                placeholder="Explica con detalle tu caso o solicitud..."
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all outline-none placeholder:text-slate-300 resize-none"></textarea>
                            <p class="text-xs text-slate-400 mt-1.5 text-right"><span id="charCount">0</span>/5000</p>
                        </div>

                        {{-- Botones --}}
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" onclick="closeSupportModal()"
                                class="px-6 py-3 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors rounded-xl">
                                Cancelar
                            </button>
                            <button type="submit" id="supportSubmitBtn"
                                class="px-8 py-3 bg-[#001a4d] hover:bg-[#003399] text-white font-bold text-sm rounded-xl transition-all shadow-lg flex items-center gap-2">
                                <i data-lucide="send" class="w-4 h-4 btn-icon"></i>
                                <span class="spinner" style="display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-radius:50%;border-top-color:#fff;animation:spin 0.6s linear infinite;"></span>
                                Enviar Ticket
                            </button>
                        </div>
                    </form>

                    {{-- Success state --}}
                    <div id="supportSuccess" class="hidden text-center py-8">
                        <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500"></i>
                        </div>
                        <h4 class="text-xl font-bold text-[#001a4d] mb-2">¡Ticket Enviado!</h4>
                        <p class="text-slate-500 text-sm mb-6">Nos pondremos en contacto contigo pronto.</p>
                        <button type="button" onclick="closeSupportModal()"
                            class="px-8 py-3 bg-[#001a4d] hover:bg-[#003399] text-white font-bold text-sm rounded-xl transition-all shadow-lg">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>