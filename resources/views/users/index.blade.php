<x-app-layout>
    @vite(['resources/css/users-list.css', 'resources/js/users-list.js'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Jerarquía de Usuarios</span>
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
                        <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">Control de <span class="text-[#003399]">Accesos</span></h2>
                        <p class="text-slate-500 mt-2">Los usuarios se muestran agrupados por el administrador que los dio de alta.</p>
                    </div>
                    <a href="{{ route('users.create') }}" class="btn-primary">
                        <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i> Añadir Nuevo
                    </a>
                </div>
            </div>

            <div class="space-y-8">
                {{-- SECCIÓN SUPER ADMINS --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="flex items-center gap-3">
                            <div class="role-icon super-admin"><i data-lucide="shield-check" class="w-6 h-6"></i></div>
                            <h3 class="section-title">Super Administradores</h3>
                        </div>
                    </div>
                    
                    <div class="admin-accordion">
                        @foreach($superAdmins as $admin)
                            <div class="accordion-item shadow-sm">
                                <div class="accordion-header-static">
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="user-avatar super-admin">{{ substr($admin->full_name, 0, 1) }}</div>
                                        <div class="flex-1 text-left">
                                            <h4 class="user-name">{{ $admin->full_name }}</h4>
                                            <p class="user-email">{{ $admin->email }} | <b>{{ $admin->username }}</b></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- SECCIÓN ADMINISTRADORES Y SUS USUARIOS --}}
                <div class="section-card">
                    <div class="section-header">
                        <div class="flex items-center gap-3">
                            <div class="role-icon admin"><i data-lucide="user-cog" class="w-6 h-6"></i></div>
                            <h3 class="section-title">Administradores y Cuentas Creadas</h3>
                        </div>
                    </div>
                    
                    <div class="admin-accordion">
                        @foreach($admins as $admin)
                            <div class="accordion-item shadow-sm">
                                <button class="accordion-header" onclick="toggleAccordion({{ $admin->id }})">
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="user-avatar admin">{{ substr($admin->full_name, 0, 1) }}</div>
                                        <div class="flex-1 text-left">
                                            <h4 class="user-name">{{ $admin->full_name }}</h4>
                                            <p class="user-email">{{ $admin->email }} | <b>{{ $admin->username }}</b></p>
                                        </div>
                                        <div class="flex items-center gap-6">
                                            <div class="text-center">
                                                <span class="user-stat-label">Usuarios Creados</span>
                                                <span class="user-stat-value">{{ $admin->createdUsers->count() }}/5</span>
                                            </div>
                                            @if(auth()->user()->role === 'SuperAdmin')
                                                <button onclick="confirmDeleteUser({{ $admin->id }})" class="btn-delete-user" title="Eliminar administrador">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                                <form id="delete-user-form-{{ $admin->id }}" action="{{ route('users.destroy', $admin) }}" method="POST" class="hidden">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                            <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform" id="icon-{{ $admin->id }}"></i>
                                        </div>
                                    </div>
                                </button>
                                
                                <div class="accordion-content" id="content-{{ $admin->id }}">
                                    @if($admin->createdUsers->count() > 0)
                                        <div class="compact-user-list">
                                            @foreach($admin->createdUsers as $user)
                                                <div class="compact-user-item">
                                                    <div class="flex items-center gap-3">
                                                        <div class="avatar-mini">{{ substr($user->full_name, 0, 1) }}</div>
                                                        <div>
                                                            <p class="text-sm font-bold text-[#001a4d]">{{ $user->full_name }}</p>
                                                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $user->username }}</span>
                                                        @if(auth()->user()->role === 'SuperAdmin' || (auth()->user()->role === 'Admin' && $user->created_by === auth()->user()->id))
                                                            <button onclick="confirmDeleteUser({{ $user->id }})" class="btn-delete-mini" title="Eliminar usuario">
                                                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                            </button>
                                                            <form id="delete-user-form-{{ $user->id }}" action="{{ route('users.destroy', $user) }}" method="POST" class="hidden">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="p-6 text-center text-sm text-slate-400 italic">Este administrador no ha registrado usuarios.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>