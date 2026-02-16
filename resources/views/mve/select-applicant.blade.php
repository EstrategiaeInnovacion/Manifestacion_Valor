<x-app-layout>
    <x-slot name="title">Seleccionar Solicitante</x-slot>
    @vite(['resources/css/mve-select.css', 'resources/js/mve-select.js'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">
                            {{ $mode === 'manual' ? 'MVE Manual' : 'MVE con Archivo M' }}
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

        <main class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Seleccione el <span class="text-[#003399]">Solicitante</span>
                </h2>
                <p class="text-slate-500 mt-2">
                    @if($mode === 'manual')
                        Escoja el solicitante para crear la Manifestación de Valor manualmente
                    @else
                        Escoja el solicitante para cargar el archivo M y completar los datos automáticamente
                    @endif
                </p>
            </div>

            @if($applicants->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">
                        <i data-lucide="inbox" class="w-16 h-16"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[#001a4d] mt-6">No hay solicitantes registrados</h3>
                    <p class="text-slate-500 mt-2 mb-6">Debe registrar al menos un solicitante antes de crear una MVE</p>
                    <a href="{{ route('applicants.create') }}" class="btn-primary">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                        Registrar Solicitante
                    </a>
                </div>
            @else
                <div class="applicants-grid">
                    @foreach($applicants as $applicant)
                        <div class="applicant-card" onclick="selectApplicant({{ $applicant->id }}, '{{ $mode }}')">
                            <div class="applicant-header">
                                <div class="applicant-icon">
                                    <i data-lucide="building-2" class="w-6 h-6"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Razón Social</p>
                                    <h3 class="applicant-name">{{ $applicant->business_name }}</h3>
                                    <p class="applicant-rfc">RFC: {{ $applicant->applicant_rfc }}</p>
                                </div>
                            </div>
                            
                            
                            <div class="applicant-action">
                                <span class="text-[#003399] font-bold text-sm">Seleccionar</span>
                                <i data-lucide="chevron-right" class="w-5 h-5 text-[#003399]"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </main>
    </div>
</x-app-layout>
