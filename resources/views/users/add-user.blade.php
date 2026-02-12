<x-app-layout>
    <x-slot name="title">Agregar Usuario</x-slot>
    @vite(['resources/css/add-user.css', 'resources/js/add-user.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Añadir Usuario</span>
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
                                
                                <a href="{{ route('dashboard') }}" class="dropdown-item">
                                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                                    <span class="font-semibold text-sm">Dashboard</span>
                                </a>

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
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-slate-500 hover:text-[#003399] font-semibold mb-4 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Volver al Dashboard
                </a>
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Añadir <span class="text-[#003399]">Nuevo Usuario</span>
                </h2>
                <p class="text-slate-500 mt-2">Registra un nuevo usuario en el sistema. Se generará una contraseña aleatoria.</p>
            </div>

            @if(session('success'))
                <div class="success-alert mb-8">
                    <div class="flex items-start gap-4">
                        <div class="success-icon">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg mb-2">¡Usuario creado exitosamente!</h3>
                            <p class="text-sm mb-3">Comparte las siguientes credenciales con el nuevo usuario:</p>
                            <div class="credentials-box">
                                <div class="credential-item">
                                    <span class="credential-label">Correo:</span>
                                    <span class="credential-value">{{ session('email') }}</span>
                                </div>
                                <div class="credential-item">
                                    <span class="credential-label">Contraseña:</span>
                                    <div class="flex items-center gap-2">
                                        <span id="passwordDisplay" class="credential-value">{{ session('password') }}</span>
                                        <button type="button" id="copyPassword" class="copy-button" data-password="{{ session('password') }}">
                                            <i data-lucide="copy" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs mt-3 text-emerald-700">
                                <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                                El usuario podrá cambiar su contraseña desde su perfil.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            
            @if($errors->has('limit'))
                <div class="error-alert mb-8">
                    <div class="flex items-start gap-4">
                        <div class="error-icon">
                            <i data-lucide="alert-circle" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg mb-2">¡Límite alcanzado!</h3>
                            <p class="text-sm">{{ $errors->first('limit') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="form-card">
                <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                    @csrf

                    <div class="form-group">
                        <label for="full_name" class="form-label">Nombre Completo</label>
                        <input id="full_name" 
                               name="full_name" 
                               type="text" 
                               class="form-input" 
                               value="{{ old('full_name') }}" 
                               required 
                               autofocus 
                               placeholder="Ej: Juan Pérez García">
                        @error('full_name')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="username" class="form-label">Nombre de Usuario</label>
                            <input id="username" 
                                   name="username" 
                                   type="text" 
                                   class="form-input" 
                                   value="{{ old('username') }}" 
                                   required 
                                   placeholder="Ej: jperez">
                            @error('username')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input id="email" 
                                   name="email" 
                                   type="email" 
                                   class="form-input" 
                                   value="{{ old('email') }}" 
                                   required 
                                   placeholder="Ej: juan.perez@ejemplo.com">
                            @error('email')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">Rol del Usuario</label>
                        <select id="role" name="role" class="form-input" required>
                            <option value="">Selecciona un rol</option>
                            @if(auth()->user()->role === 'SuperAdmin')
                                <option value="SuperAdmin" {{ old('role') == 'SuperAdmin' ? 'selected' : '' }}>Super Administrador</option>
                                <option value="Admin" {{ old('role') == 'Admin' ? 'selected' : '' }}>Administrador</option>
                            @endif
                            <option value="Usuario" {{ old('role') == 'Usuario' ? 'selected' : '' }}>Usuario</option>
                        </select>
                        @error('role')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-slate-500 mt-2 pl-1">
                            <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
                            @if(auth()->user()->role === 'Admin')
                                Como administrador, solo puedes crear usuarios regulares (máximo 5).
                            @else
                                Define el nivel de acceso del usuario en el sistema.
                            @endif
                        </p>
                    </div>

                    <div class="password-info-box">
                        <div class="flex items-start gap-3">
                            <i data-lucide="shield-check" class="w-5 h-5 text-[#003399] mt-0.5"></i>
                            <div>
                                <h4 class="font-bold text-sm text-[#001a4d] mb-1">Contraseña Automática</h4>
                                <p class="text-xs text-slate-600">
                                    Se generará automáticamente una contraseña segura de 12 caracteres. El usuario recibirá las credenciales y podrá cambiar su contraseña desde su perfil.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="btn-primary flex-1">
                            <i data-lucide="user-plus" class="w-5 h-5 inline-block mr-2"></i>
                            Crear Usuario
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-app-layout>
