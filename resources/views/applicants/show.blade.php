<x-app-layout>
    <x-slot name="title">Detalle del Solicitante</x-slot>
    @vite(['resources/css/applicant-form.css', 'resources/js/applicant-form.js'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Detalles del Solicitante</span>
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
                
                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">{{ $applicant->business_name }}</h2>
                        <p class="text-slate-500 mt-2">RFC: <span class="font-bold text-[#003399]">{{ $applicant->applicant_rfc }}</span></p>
                    </div>
                    <a href="{{ route('applicants.edit', $applicant) }}" class="btn-primary">
                        <i data-lucide="edit" class="w-5 h-5 mr-2"></i> Editar
                    </a>
                </div>
            </div>

            {{-- Datos del Solicitante --}}
            <div class="detail-card">
                <h3 class="detail-section-title">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                    Datos del Solicitante
                </h3>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Usuario Responsable</span>
                        <span class="detail-value">{{ $applicant->user->full_name ?? 'N/A' }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Correo Electrónico del Solicitante</span>
                        <span class="detail-value">{{ $applicant->applicant_email ?? 'No especificado' }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">RFC</span>
                        <span class="detail-value badge-rfc">{{ $applicant->applicant_rfc }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Razón Social</span>
                        <span class="detail-value">{{ $applicant->business_name }}</span>
                    </div>
                </div>
            </div>

            {{-- Estado de Sellos VUCEM --}}
            <div class="detail-card">
                <h3 class="detail-section-title">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                    Sellos VUCEM
                </h3>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Archivo .key</span>
                        <span class="detail-value">
                            @if($applicant->vucem_key_file)
                                <span class="inline-flex items-center gap-1 text-green-700 bg-green-50 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Cargado (encriptado)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-500 bg-slate-100 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="minus-circle" class="w-3.5 h-3.5"></i> No configurado
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Archivo .cer</span>
                        <span class="detail-value">
                            @if($applicant->vucem_cert_file)
                                <span class="inline-flex items-center gap-1 text-green-700 bg-green-50 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Cargado (encriptado)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-500 bg-slate-100 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="minus-circle" class="w-3.5 h-3.5"></i> No configurado
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Contraseña del Sello</span>
                        <span class="detail-value">
                            @if($applicant->vucem_password)
                                <span class="inline-flex items-center gap-1 text-green-700 bg-green-50 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Guardada (encriptada)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-500 bg-slate-100 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="minus-circle" class="w-3.5 h-3.5"></i> No configurada
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Clave Web Service</span>
                        <span class="detail-value">
                            @if($applicant->vucem_webservice_key)
                                <span class="inline-flex items-center gap-1 text-green-700 bg-green-50 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Guardada (encriptada)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-500 bg-slate-100 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="minus-circle" class="w-3.5 h-3.5"></i> No configurada
                                </span>
                            @endif
                        </span>
                    </div>
                </div>

                @if($applicant->hasVucemCredentials())
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-5 h-5 text-green-600"></i>
                            <span class="text-sm font-semibold text-green-800">Sellos VUCEM completos — Las operaciones se ejecutarán con credenciales automáticas</span>
                        </div>
                    </div>
                @else
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                            <span class="text-sm font-semibold text-amber-800">Sellos VUCEM incompletos — Deberá ingresar credenciales manualmente en cada operación</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Consentimiento de Privacidad --}}
            <div class="detail-card">
                <h3 class="detail-section-title">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                    Consentimiento de Privacidad
                </h3>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Estado del Consentimiento</span>
                        <span class="detail-value">
                            @if($applicant->privacy_consent)
                                <span class="inline-flex items-center gap-1 text-green-700 bg-green-50 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Autorizado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-amber-700 bg-amber-50 px-2 py-1 rounded-lg text-xs font-semibold">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Pendiente
                                </span>
                            @endif
                        </span>
                    </div>

                    @if($applicant->privacy_consent_at)
                        <div class="detail-item">
                            <span class="detail-label">Fecha de Autorización</span>
                            <span class="detail-value">{{ $applicant->privacy_consent_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Información del Sistema --}}
            <div class="detail-card">
                <h3 class="detail-section-title">
                    <i data-lucide="info" class="w-5 h-5"></i>
                    Información del Sistema
                </h3>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Fecha de Registro</span>
                        <span class="detail-value">{{ $applicant->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Última Actualización</span>
                        <span class="detail-value">{{ $applicant->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
