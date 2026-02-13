<x-app-layout>
    <x-slot name="title">Solicitantes</x-slot>
    @vite(['resources/css/applicants-list.css', 'resources/js/applicants-list.js'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Solicitantes MV</span>
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
                
                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">Solicitantes <span class="text-[#003399]">MV</span></h2>
                        <p class="text-slate-500 mt-2">Gestión de datos fiscales para Manifestación de Valor en VUCEM</p>
                    </div>
                    <a href="{{ route('applicants.create') }}" class="btn-primary">
                        <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i> Registrar Solicitante
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert-success">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="section-card">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Correo Electrónico</th>
                                <th>RFC</th>
                                <th>Razón Social</th>
                                <th class="text-center">Sellos VUCEM</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($applicants as $applicant)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar-small">{{ substr($applicant->business_name, 0, 1) }}</div>
                                            <span class="font-medium">{{ $applicant->user_email }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge-rfc">{{ $applicant->applicant_rfc }}</span></td>
                                    <td class="font-semibold text-[#001a4d]">{{ $applicant->business_name }}</td>
                                    <td class="text-center">
                                        @if($applicant->hasVucemCredentials())
                                            <span class="inline-flex items-center gap-1 text-green-700 bg-green-50 px-2 py-1 rounded-lg text-xs font-semibold">
                                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Configurados
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-slate-500 bg-slate-100 px-2 py-1 rounded-lg text-xs font-semibold">
                                                <i data-lucide="shield-off" class="w-3.5 h-3.5"></i> Sin configurar
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('applicants.show', $applicant) }}" class="btn-action btn-view" title="Ver detalles">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                            <a href="{{ route('applicants.edit', $applicant) }}" class="btn-action btn-edit" title="Editar">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                            <button type="button" data-delete-applicant="{{ $applicant->id }}" class="btn-action btn-delete" title="Eliminar">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                            <form id="delete-form-{{ $applicant->id }}" action="{{ route('applicants.destroy', $applicant) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-12">
                                        <div class="empty-state">
                                            <i data-lucide="inbox" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
                                            <p class="text-lg font-semibold text-slate-400">No hay solicitantes registrados</p>
                                            <p class="text-sm text-slate-400 mt-2">Comienza registrando un nuevo solicitante</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</x-app-layout>
