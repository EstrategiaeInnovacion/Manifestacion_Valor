<x-app-layout>
    <x-slot name="title">Editar Usuario</x-slot>
    @vite(['resources/css/add-user.css', 'resources/js/add-user.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Editar Usuario</span>
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
                <a href="{{ route('users.index') }}" class="inline-flex items-center text-slate-500 hover:text-[#003399] font-semibold mb-4 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Volver a la lista
                </a>
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Editar <span class="text-[#003399]">Usuario</span>
                </h2>
                <p class="text-slate-500 mt-2">Modifica la información del usuario en el sistema.</p>
            </div>

            @if(session('success'))
                <div class="success-alert mb-8">
                    <div class="flex items-start gap-4">
                        <div class="success-icon">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg mb-2">¡Usuario actualizado!</h3>
                            <p class="text-sm">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="form-card">
                <form method="POST" action="{{ route('users.update', $user->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="full_name" class="form-label">Nombre Completo</label>
                        <input id="full_name" 
                               name="full_name" 
                               type="text" 
                               class="form-input" 
                               value="{{ old('full_name', $user->full_name) }}" 
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
                                   value="{{ old('username', $user->username) }}" 
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
                                   value="{{ old('email', $user->email) }}" 
                                   required 
                                   placeholder="Ej: juan.perez@ejemplo.com">
                            @error('email')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">Rol del Usuario</label>
                        <select id="role" name="role" class="form-input" required {{ auth()->user()->role !== 'SuperAdmin' ? 'disabled' : '' }}>
                            @if(auth()->user()->role === 'SuperAdmin')
                                <option value="SuperAdmin" {{ old('role', $user->role) == 'SuperAdmin' ? 'selected' : '' }}>Super Administrador</option>
                                <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Administrador</option>
                            @endif
                            <option value="Usuario" {{ old('role', $user->role) == 'Usuario' ? 'selected' : '' }}>Usuario</option>
                        </select>
                        @if(auth()->user()->role !== 'SuperAdmin')
                            <input type="hidden" name="role" value="{{ $user->role }}">
                        @endif
                        @error('role')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="password-info-box">
                        <div class="flex items-start gap-3">
                            <i data-lucide="lock" class="w-5 h-5 text-[#003399] mt-0.5"></i>
                            <div class="w-full">
                                <h4 class="font-bold text-sm text-[#001a4d] mb-1">Cambiar Contraseña (Opcional)</h4>
                                <p class="text-xs text-slate-600 mb-3">
                                    Si dejas estos campos vacíos, se mantendrá la contraseña actual.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <input id="password" 
                                            name="password" 
                                            type="password" 
                                            class="form-input" 
                                            placeholder="Nueva Contraseña">
                                    </div>
                                    <div>
                                        <input id="password_confirmation" 
                                            name="password_confirmation" 
                                            type="password" 
                                            class="form-input" 
                                            placeholder="Confirmar Contraseña">
                                    </div>
                                </div>
                                @error('password')
                                    <p class="error-message mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="btn-primary flex-1">
                            <i data-lucide="save" class="w-5 h-5 inline-block mr-2"></i>
                            Guardar Cambios
                        </button>
                        <a href="{{ route('users.index') }}" class="btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-app-layout>
