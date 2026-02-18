<x-app-layout>
    <x-slot name="title">Panel de Licencias</x-slot>
    @vite(['resources/css/users-list.css', 'resources/css/license-panel.css', 'resources/js/users-list.js', 'resources/js/license-panel.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegación (mismo estilo que users/index) --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Panel de Licencias</span>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Super Administrador</p>
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

                @if(session('success'))
                    <div class="alert-success mb-6">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-error mb-6">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <div>
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">Gestión de <span class="text-[#003399]">Licencias</span></h2>
                        <p class="text-slate-500 mt-2">Administra licencias, límites de usuarios y solicitantes para cada administrador.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                {{-- SECCIÓN: ADMINISTRADORES Y SUS LICENCIAS --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="flex items-center gap-3">
                            <div class="role-icon admin"><i data-lucide="key-round" class="w-6 h-6"></i></div>
                            <h3 class="section-title">Administradores y Licencias</h3>
                        </div>
                    </div>

                    <div class="admin-accordion">
                        @forelse($admins as $admin)
                            @php
                                $license = $admin->activeLicense;
                                $isActive = $license && $license->isActive();
                                $usersCount = $admin->createdUsers->count();
                                $applicantsCount = \App\Models\MvClientApplicant::where('user_email', $admin->email)->count();
                            @endphp
                            <div class="accordion-item shadow-sm {{ $isActive ? 'border-l-4 border-l-emerald-500' : 'border-l-4 border-l-red-400' }}">
                                {{-- Header del admin --}}
                                <div class="accordion-header" onclick="toggleAccordion({{ $admin->id }})" style="cursor:pointer;">
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="user-avatar admin">{{ substr($admin->full_name, 0, 1) }}</div>
                                        <div class="flex-1 text-left min-w-0">
                                            <h4 class="user-name">{{ $admin->full_name }}</h4>
                                            <p class="user-email">{{ $admin->email }} | <b>{{ $admin->username }}</b></p>
                                        </div>
                                        <div class="flex items-center gap-6">
                                            {{-- Estado de licencia --}}
                                            <div class="text-center hidden sm:block">
                                                @if($isActive)
                                                    <span class="user-stat-label">Licencia</span>
                                                    <span class="inline-flex items-center gap-1 mt-1">
                                                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                                        <span class="text-xs font-bold text-emerald-600">Activa</span>
                                                    </span>
                                                @else
                                                    <span class="user-stat-label">Licencia</span>
                                                    <span class="inline-flex items-center gap-1 mt-1">
                                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                        <span class="text-xs font-bold text-red-500">Inactiva</span>
                                                    </span>
                                                @endif
                                            </div>
                                            {{-- Usuarios --}}
                                            <div class="text-center">
                                                <span class="user-stat-label">Usuarios</span>
                                                <span class="user-stat-value text-lg">{{ $usersCount }}/{{ $admin->max_users }}</span>
                                            </div>
                                            {{-- Solicitantes --}}
                                            <div class="text-center hidden md:block">
                                                <span class="user-stat-label">Solicitantes</span>
                                                <span class="user-stat-value text-lg">{{ $applicantsCount }}/{{ $admin->max_applicants }}</span>
                                            </div>
                                            <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" id="icon-{{ $admin->id }}"></i>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contenido expandible --}}
                                <div class="accordion-content" id="content-{{ $admin->id }}">
                                    {{-- Info de licencia + acciones --}}
                                    <div class="mb-6">
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                                            <div class="bg-slate-50 rounded-xl p-3 text-center">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Clave</p>
                                                <p class="text-xs font-mono font-bold text-[#003399] mt-1">{{ $license ? $license->license_key : '—' }}</p>
                                            </div>
                                            <div class="bg-slate-50 rounded-xl p-3 text-center">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Expira</p>
                                                <p class="text-xs font-bold {{ $isActive ? 'text-emerald-600' : 'text-red-500' }} mt-1">
                                                    {{ $license ? $license->expires_at->format('d/m/Y H:i') : '—' }}
                                                </p>
                                            </div>
                                            <div class="bg-slate-50 rounded-xl p-3 text-center">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Restante</p>
                                                <p class="text-xs font-bold text-slate-600 mt-1">
                                                    {{ $license && $isActive ? $license->timeRemaining() : '—' }}
                                                </p>
                                            </div>
                                            <div class="bg-slate-50 rounded-xl p-3 text-center">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Duración</p>
                                                <p class="text-xs font-bold text-slate-600 mt-1">
                                                    {{ $license ? (\App\Models\License::DURATIONS[$license->duration_type]['label'] ?? $license->duration_type) : '—' }}
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Botones de acción --}}
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" onclick="event.stopPropagation(); openLicenseModal({{ $admin->id }}, '{{ $admin->full_name }}')" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#003399] hover:bg-[#001a4d] text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                                <i data-lucide="key-round" class="w-3.5 h-3.5"></i>
                                                {{ $isActive ? 'Renovar Licencia' : 'Asignar Licencia' }}
                                            </button>
                                            <button type="button" onclick="event.stopPropagation(); openLimitsModal({{ $admin->id }}, '{{ $admin->full_name }}', {{ $admin->max_users }}, {{ $admin->max_applicants }})"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                                                Configurar Límites
                                            </button>
                                            @if($isActive)
                                                <form action="{{ route('admin.licenses.revoke', $license) }}" method="POST" onsubmit="return confirm('¿Revocar licencia de {{ $admin->full_name }}? El admin y sus usuarios perderán acceso inmediatamente.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 hover:bg-red-500 text-red-600 hover:text-white text-xs font-bold rounded-xl transition-all">
                                                        <i data-lucide="shield-off" class="w-3.5 h-3.5"></i>
                                                        Revocar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Usuarios del admin --}}
                                    @if($admin->createdUsers->count() > 0)
                                        <div class="border-t border-slate-200 pt-4">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                                Usuarios de {{ $admin->full_name }} ({{ $admin->createdUsers->count() }})
                                            </p>
                                            <div class="compact-user-list">
                                                @foreach($admin->createdUsers as $user)
                                                    @php
                                                        $userApplicants = \App\Models\MvClientApplicant::where('user_email', $user->email)->count();
                                                    @endphp
                                                    <div class="compact-user-item">
                                                        <div class="flex items-center gap-3">
                                                            <div class="avatar-mini">{{ substr($user->full_name, 0, 1) }}</div>
                                                            <div>
                                                                <p class="text-sm font-bold text-[#001a4d]">{{ $user->full_name }}</p>
                                                                <p class="text-xs text-slate-500">{{ $user->email }} · Solicitantes: {{ $userApplicants }}/{{ $user->max_applicants }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $user->username }}</span>
                                                            <button type="button" 
                                                                onclick="event.stopPropagation(); openLimitsModal({{ $user->id }}, '{{ $user->full_name }}', null, {{ $user->max_applicants }})"
                                                                class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-[#003399] px-2 py-1 bg-white border border-slate-200 rounded-lg transition-all">
                                                                <i data-lucide="sliders-horizontal" class="w-3 h-3"></i>Límites
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-center text-sm text-slate-400 italic border-t border-slate-200 pt-4">Este administrador no ha registrado usuarios.</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center p-12">
                                <i data-lucide="users" class="w-12 h-12 text-slate-300 mx-auto mb-4"></i>
                                <p class="text-slate-400 font-semibold">No hay administradores registrados.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- SECCIÓN: HISTORIAL DE LICENCIAS --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="flex items-center gap-3">
                            <div class="role-icon" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%); color: white;">
                                <i data-lucide="scroll-text" class="w-6 h-6"></i>
                            </div>
                            <h3 class="section-title">Historial de Licencias</h3>
                        </div>
                    </div>

                    <div class="overflow-x-auto -mx-2">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-slate-100">
                                    <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Clave</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Admin</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Duración</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Inicio</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Expiración</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allLicenses as $lic)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-3 font-mono font-bold text-[#003399] text-xs">{{ $lic->license_key }}</td>
                                        <td class="px-4 py-3 font-bold text-[#001a4d] text-sm">{{ $lic->admin->full_name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-slate-600 text-sm">{{ \App\Models\License::DURATIONS[$lic->duration_type]['label'] ?? $lic->duration_type }}</td>
                                        <td class="px-4 py-3 text-slate-500 text-sm">{{ $lic->starts_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 text-slate-500 text-sm">{{ $lic->expires_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            @if($lic->isActive())
                                                <span class="badge badge-green text-[10px]">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full inline-block mr-1"></span>Activa
                                                </span>
                                            @elseif($lic->status === 'revoked')
                                                <span class="badge" style="background: #fef3c7; color: #d97706;">Revocada</span>
                                            @else
                                                <span class="badge" style="background: #fee2e2; color: #dc2626;">Expirada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-slate-400 italic">No hay licencias generadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    {{-- MODAL: ASIGNAR/RENOVAR LICENCIA --}}
    <div id="licenseModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
            <div class="bg-gradient-to-r from-[#001a4d] to-[#003399] px-6 py-5">
                <h3 class="text-lg font-bold text-white">Asignar Licencia</h3>
                <p class="text-blue-200 text-sm mt-1" id="licenseModalSubtitle">Admin</p>
            </div>
            <form id="licenseForm" method="POST" action="{{ route('admin.licenses.store') }}" class="p-6">
                @csrf
                <input type="hidden" name="admin_id" id="licenseAdminId">

                <div class="mb-5">
                    <label class="block text-sm font-bold text-[#001a4d] mb-2">Duración de la Licencia</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="license-duration-option">
                            <input type="radio" name="duration_type" value="1min" class="sr-only peer">
                            <div class="peer-checked:border-[#003399] peer-checked:bg-blue-50 border-2 border-slate-200 rounded-xl p-3 text-center cursor-pointer transition-all hover:border-slate-300">
                                <p class="text-lg font-black text-[#001a4d]">1 min</p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Prueba</p>
                            </div>
                        </label>
                        <label class="license-duration-option">
                            <input type="radio" name="duration_type" value="1month" class="sr-only peer">
                            <div class="peer-checked:border-[#003399] peer-checked:bg-blue-50 border-2 border-slate-200 rounded-xl p-3 text-center cursor-pointer transition-all hover:border-slate-300">
                                <p class="text-lg font-black text-[#001a4d]">1 Mes</p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">30 días</p>
                            </div>
                        </label>
                        <label class="license-duration-option">
                            <input type="radio" name="duration_type" value="6months" class="sr-only peer" checked>
                            <div class="peer-checked:border-[#003399] peer-checked:bg-blue-50 border-2 border-slate-200 rounded-xl p-3 text-center cursor-pointer transition-all hover:border-slate-300">
                                <p class="text-lg font-black text-[#001a4d]">6 Meses</p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">182 días</p>
                            </div>
                        </label>
                        <label class="license-duration-option">
                            <input type="radio" name="duration_type" value="1year" class="sr-only peer">
                            <div class="peer-checked:border-[#003399] peer-checked:bg-blue-50 border-2 border-slate-200 rounded-xl p-3 text-center cursor-pointer transition-all hover:border-slate-300">
                                <p class="text-lg font-black text-[#001a4d]">1 Año</p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">365 días</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="licenseNotes" class="block text-sm font-bold text-[#001a4d] mb-2">Notas (opcional)</label>
                    <textarea id="licenseNotes" name="notes" rows="2" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#003399] focus:border-transparent" placeholder="Notas sobre esta licencia..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-6 py-3 bg-[#003399] hover:bg-[#001a4d] text-white font-bold rounded-xl transition-all shadow-sm">
                        <i data-lucide="key-round" class="w-4 h-4 inline mr-2"></i>Generar Licencia
                    </button>
                    <button type="button" onclick="closeLicenseModal()" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-all">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: CONFIGURAR LÍMITES --}}
    <div id="limitsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-600 to-slate-700 px-6 py-5">
                <h3 class="text-lg font-bold text-white">Configurar Límites</h3>
                <p class="text-slate-300 text-sm mt-1" id="limitsModalSubtitle">Usuario</p>
            </div>
            <form id="limitsForm" method="POST" action="" class="p-6">
                @csrf
                @method('PATCH')

                <div id="limitsMaxUsersGroup" class="mb-5">
                    <label for="limitsMaxUsers" class="block text-sm font-bold text-[#001a4d] mb-2">Máximo de Usuarios que puede crear</label>
                    <input type="number" id="limitsMaxUsers" name="max_users" min="0" max="100" 
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-slate-500 focus:border-transparent">
                    <p class="text-xs text-slate-400 mt-1">Cantidad máxima de cuentas de usuario que este admin puede registrar.</p>
                </div>

                <div class="mb-6">
                    <label for="limitsMaxApplicants" class="block text-sm font-bold text-[#001a4d] mb-2">Máximo de Solicitantes</label>
                    <input type="number" id="limitsMaxApplicants" name="max_applicants" min="0" max="500"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-slate-500 focus:border-transparent">
                    <p class="text-xs text-slate-400 mt-1">Cantidad máxima de solicitantes (RFCs) que puede añadir.</p>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-6 py-3 bg-slate-700 hover:bg-slate-800 text-white font-bold rounded-xl transition-all shadow-sm">
                        <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>Guardar Límites
                    </button>
                    <button type="button" onclick="closeLimitsModal()" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-all">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
