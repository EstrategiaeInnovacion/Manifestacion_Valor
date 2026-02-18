<x-app-layout>
    <x-slot name="title">Mi Perfil</x-slot>
    @vite(['resources/css/profile.css', 'resources/js/profile.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Mi Perfil</span>
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
            
            <div class="mb-8">
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Mi <span class="text-[#003399]">Perfil</span>
                </h2>
                <p class="text-slate-500 mt-2">Gestiona tu información personal y configuración de seguridad.</p>
            </div>

            <div class="space-y-8">
                {{-- Información de Licencia (Admin y Usuario) --}}
                @if(auth()->user()->role !== 'SuperAdmin')
                    @php
                        $license = auth()->user()->getEffectiveLicense();
                    @endphp
                    <div class="profile-card">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#001a4d] to-[#003399] flex items-center justify-center">
                                <i data-lucide="key-round" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-[#001a4d]">Licencia</h3>
                                <p class="text-xs text-slate-400">Información de tu licencia de acceso al sistema.</p>
                            </div>
                        </div>

                        @if($license && $license->isActive())
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-slate-50 rounded-xl p-4 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Clave de Licencia</p>
                                    <p class="text-sm font-mono font-bold text-[#003399] mt-1.5">{{ $license->license_key }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Fecha de Vencimiento</p>
                                    <p class="text-sm font-bold text-[#001a4d] mt-1.5">{{ $license->expires_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tiempo Restante</p>
                                    @php
                                        $diff = now()->diff($license->expires_at);
                                        $totalDays = $diff->days;
                                    @endphp
                                    <p class="text-sm font-bold mt-1.5 {{ $totalDays <= 7 ? 'text-amber-600' : 'text-emerald-600' }}">
                                        @if($totalDays > 0)
                                            {{ $totalDays }} días, {{ $diff->h }}h {{ $diff->i }}m
                                        @else
                                            {{ $diff->h }}h {{ $diff->i }}m
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="bg-red-50 border border-red-200 rounded-xl p-5 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-red-700">Sin licencia activa</p>
                                    <p class="text-xs text-red-500 mt-0.5">Contacta a tu administrador para obtener o renovar tu licencia de acceso.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="profile-card">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="profile-card">
                    @include('profile.partials.update-password-form')
                </div>

                <div class="profile-card">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
