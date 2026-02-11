<x-app-layout>
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
                        
                        <h2 class="text-6xl font-light text-[#001a4d] tracking-tighter">
                            VEXUM<span class="font-black text-[#003399]">CORE</span>
                        </h2>
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
    </div>
</x-app-layout>