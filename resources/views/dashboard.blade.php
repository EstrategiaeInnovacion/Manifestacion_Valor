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
            
            <div class="mb-12">
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Hola, <span class="text-[#003399]">{{ explode(' ', auth()->user()->full_name)[0] }}</span> 
                </h2>
                <p class="text-slate-500 mt-2">¿Qué deseas gestionar el día de hoy?</p>
            </div>

            @if(auth()->user()->role === 'SuperAdmin' || auth()->user()->role === 'Admin')
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    
                    <a href="{{ route('users.create') }}" class="modern-card group">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="user-plus" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Añadir Nuevo Usuario</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Gestiona los accesos y niveles de permisos para el equipo administrativo.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Gestionar ahora 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('users.index') }}" class="modern-card group">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="users-round" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Usuarios</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Visualiza y administra todos los usuarios del sistema.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Ver usuarios 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('applicants.create') }}" class="modern-card group">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="user-plus" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Añadir Solicitante</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Registra nuevas empresas, RFCs y sus domicilios fiscales correspondientes.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Registrar RFC 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('applicants.index') }}" class="modern-card group">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="briefcase" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Lista de Solicitantes</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Administra todos los RFCs y datos fiscales registrados en el sistema.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Ver solicitantes 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <button type="button" onclick="openMveModal()" class="modern-card group border-t-4 border-t-[#003399] text-left w-full">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="file-text" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Crear Manifestación</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Inicia el proceso de creación de documentos de valor aduanal electrónico.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Crear documento 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </button>

                    {{-- NUEVA TARJETA: DIGITALIZAR DOCUMENTO --}}
                    <a href="{{ route('digitalizacion.create') }}" class="modern-card group border-t-4 border-t-[#003399]">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                {{-- Usamos icono de escaneo o subida --}}
                                <i data-lucide="scan-line" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Digitalizar Documento</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Sube PDFs, firma con e.firma y genera nuevos eDocuments (Acuses).
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Nuevo Trámite 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    {{-- OPCIÓN 1: CONSULTA COVE --}}
                    <a href="{{ route('cove.consulta.index') }}" class="modern-card group border-t-4 border-t-[#003399] relative overflow-hidden transition-all hover:shadow-xl">
                        <div class="card-content p-6">
                            <div class="icon-box bg-blue-50 text-[#003399] w-14 h-14 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#003399] group-hover:text-white transition-all duration-300">
                                <i data-lucide="badge-dollar-sign" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mb-2">Consulta COVE</h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-6">
                                Recupera valores, mercancías y acuses (XML) de un COVE.
                            </p>
                            <div class="flex items-center text-[#003399] font-bold text-sm mt-auto">
                                Consultar Valor 
                                <i data-lucide="arrow-right" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    {{-- OPCIÓN 2: CONSULTA EDOCUMENT --}}
                    <a href="{{ route('edocument.consulta.index') }}" class="modern-card group border-t-4 border-t-emerald-600 relative overflow-hidden transition-all hover:shadow-xl">
                        <div class="card-content p-6">
                            <div class="icon-box bg-emerald-50 text-emerald-600 w-14 h-14 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                                <i data-lucide="file-search" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mb-2">Consulta eDocument</h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-6">
                                Descarga los archivos PDF digitalizados asociados a un eDocument.
                            </p>
                            <div class="flex items-center text-emerald-600 font-bold text-sm mt-auto">
                                Descargar Documentos 
                                <i data-lucide="arrow-right" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                </div>
            @elseif(auth()->user()->role === 'Usuario')
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    
                    <a href="{{ route('applicants.create') }}" class="modern-card group">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="user-plus" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Añadir Solicitante</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Registra nuevas empresas, RFCs y sus domicilios fiscales correspondientes.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Registrar RFC 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('applicants.index') }}" class="modern-card group">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="briefcase" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Mis Solicitantes</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Visualiza y administra los RFCs registrados por ti.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Ver solicitantes 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>
                    
                    <button type="button" onclick="openMveModal()" class="modern-card group border-t-4 border-t-[#003399] text-left w-full">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="file-text" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Crear Manifestación</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Inicia el proceso de creación de documentos de valor aduanal electrónico.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Crear documento 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </button>

                    {{-- NUEVA TARJETA: DIGITALIZAR DOCUMENTO (Para usuarios normales también) --}}
                    <a href="{{ route('digitalizacion.create') }}" class="modern-card group border-t-4 border-t-[#003399]">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="scan-line" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Digitalizar Documento</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Sube PDFs, firma con e.firma y genera nuevos eDocuments.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Nuevo Trámite 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('edocument.consulta.index') }}" class="modern-card group border-t-4 border-t-[#003399]">
                        <div class="card-content">
                            <div class="icon-box bg-blue-50 text-[#003399] group-hover:bg-[#003399] group-hover:text-white transition-all duration-500">
                                <i data-lucide="search" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#001a4d] mt-6">Consulta eDocument (VUCEM)</h3>
                            <p class="text-slate-500 text-sm mt-3 leading-relaxed">
                                Consulta eDocuments con e.firma y descarga temporal de archivos asociados.
                            </p>
                            <div class="mt-8 flex items-center text-[#003399] font-bold text-sm">
                                Consultar ahora 
                                <i data-lucide="move-right" class="w-4 h-4 ml-2 group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                </div>
            @endif

        </main>

        {{-- Modal para elegir tipo de MVE --}}
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button type="button" onclick="selectMveManual()" class="mve-option-card">
                            <div class="mve-option-icon">
                                <i data-lucide="pencil" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-xl font-bold text-[#001a4d] mb-2">Manual</h4>
                            <p class="text-sm text-slate-500">Complete el formulario manualmente con los datos requeridos</p>
                        </button>
                        
                        <button type="button" onclick="selectMveArchivoM()" class="mve-option-card">
                            <div class="mve-option-icon">
                                <i data-lucide="file-up" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-xl font-bold text-[#001a4d] mb-2">Archivo M</h4>
                            <p class="text-sm text-slate-500">Complete automáticamente los datos desde un archivo M</p>
                        </button>
                        
                        {{-- Tercera opción: MVE Pendientes --}}
                        <button type="button" onclick="selectMvePendientes()" class="mve-option-card mve-option-pendientes">
                            <div class="mve-option-icon mve-icon-warning">
                                <i data-lucide="file-clock" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-xl font-bold text-[#001a4d] mb-2">MVE Pendientes</h4>
                            <p class="text-sm text-slate-500">Continuar con manifestaciones guardadas en borrador</p>
                            @if($mvePendientesCount > 0)
                                <div class="mve-badge">{{ $mvePendientesCount }}</div>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>