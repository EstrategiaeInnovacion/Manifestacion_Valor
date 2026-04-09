<x-app-layout>
    <x-slot name="title">Mi Perfil</x-slot>
    @vite(['resources/css/profile.css', 'resources/js/profile.js'])

    <div class="min-h-screen" style="background:#f0f4f8;">

        {{-- Navegación --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-9 w-auto">
                        </a>
                        <div class="hidden md:flex items-center gap-2 text-xs text-slate-400 font-medium">
                            <span>/</span>
                            <a href="{{ route('dashboard') }}" class="hover:text-[#003399] transition-colors">Dashboard</a>
                            <span>/</span>
                            <span class="font-bold text-[#001a4d]">Mi Perfil</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="user-dropdown">
                            <div id="avatarButton" class="avatar-button h-9 w-9 bg-ei-gradient rounded-full flex items-center justify-center text-white font-bold text-sm shadow-lg">
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

        {{-- HERO BANNER --}}
        <div class="profile-hero">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-blue-300 hover:text-white text-xs font-semibold mb-6 transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Regresar al Dashboard
                </a>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <div class="profile-avatar-xl">
                        {{ substr(auth()->user()->full_name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            <span class="profile-role-badge">{{ auth()->user()->role }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-tight">{{ auth()->user()->full_name }}</h1>
                        <p class="text-blue-300 text-sm mt-1">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="hidden sm:block text-right">
                        <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">Miembro desde</p>
                        <p class="text-white font-black mt-1">{{ auth()->user()->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- CONTENIDO PRINCIPAL --}}
        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-5 pb-16 relative z-10">
            <div class="space-y-5">

                {{-- Licencia --}}
                @if(auth()->user()->role !== 'SuperAdmin')
                @php
                    $license = auth()->user()->getEffectiveLicense();
                @endphp
                <div class="profile-section-card" id="section-license">
                    <div class="section-card-header">
                        <div class="section-icon-wrap" style="background:#fef3c7;">
                            <i data-lucide="key-round" class="w-5 h-5" style="color:#d97706;"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="section-card-title">Licencia de Acceso</h3>
                            <p class="section-card-desc">Información de tu licencia activa en el sistema.</p>
                        </div>
                        @if($license && $license->isActive())
                            <span class="license-active-badge">
                                <span class="license-dot"></span>
                                Activa
                            </span>
                        @endif
                    </div>
                    @if($license && $license->isActive())
                        @php
                            $diff = now()->diff($license->expires_at);
                            $totalDays = (int) now()->diffInDays($license->expires_at);
                            $maxDays = max(1, (int) $license->created_at->diffInDays($license->expires_at));
                            $pct = min(100, max(2, round(($totalDays / $maxDays) * 100)));
                            $isWarn = $totalDays <= 7;
                        @endphp
                        <div class="license-grid">
                            <div class="license-stat">
                                <p class="license-stat-label">Clave de Licencia</p>
                                <p class="license-stat-value font-mono" style="color:#003399;">{{ $license->license_key }}</p>
                            </div>
                            <div class="license-stat">
                                <p class="license-stat-label">Fecha de Vencimiento</p>
                                <p class="license-stat-value">{{ $license->expires_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="license-stat">
                                <p class="license-stat-label">Tiempo Restante</p>
                                <p class="license-stat-value {{ $isWarn ? 'text-amber-600' : 'text-emerald-600' }}">
                                    @if($totalDays > 0)
                                        {{ $totalDays }} días, {{ $diff->h }}h {{ $diff->i }}m
                                    @else
                                        {{ $diff->h }}h {{ $diff->i }}m
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="license-progress-wrap">
                            <div class="license-progress-meta">
                                <span>Vigencia restante</span>
                                <span class="{{ $isWarn ? 'text-amber-600' : 'text-emerald-600' }} font-bold">{{ $pct }}%</span>
                            </div>
                            <div class="license-progress-bg">
                                <div class="license-progress-fill {{ $isWarn ? 'progress-warn' : 'progress-ok' }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    @else
                        <div class="license-inactive-box">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                            <div>
                                <p class="font-bold text-red-700 text-sm">Sin licencia activa</p>
                                <p class="text-xs text-red-500 mt-0.5">Contacta a tu administrador para obtener o renovar tu licencia de acceso.</p>
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                {{-- Información del Perfil + Contraseña (2 cols en lg+) --}}
                <div class="profile-two-col">

                    {{-- Información del Perfil --}}
                    <div class="profile-section-card" id="section-info">
                        <div class="section-card-header">
                            <div class="section-icon-wrap" style="background:#eff6ff;">
                                <i data-lucide="user-round" class="w-5 h-5" style="color:#2563eb;"></i>
                            </div>
                            <div>
                                <h3 class="section-card-title">Información del Perfil</h3>
                                <p class="section-card-desc">Nombre y correo electrónico.</p>
                            </div>
                        </div>
                        @include('profile.partials.update-profile-information-form')
                    </div>

                    {{-- Contraseña --}}
                    <div class="profile-section-card" id="section-password">
                        <div class="section-card-header">
                            <div class="section-icon-wrap" style="background:#f0fdf4;">
                                <i data-lucide="lock-keyhole" class="w-5 h-5" style="color:#16a34a;"></i>
                            </div>
                            <div>
                                <h3 class="section-card-title">Seguridad</h3>
                                <p class="section-card-desc">Actualiza tu contraseña.</p>
                            </div>
                        </div>
                        @include('profile.partials.update-password-form')
                    </div>

                </div>



            </div>
        </main>
    </div>
</x-app-layout>
