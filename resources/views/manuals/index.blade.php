<x-app-layout>
    <x-slot name="title">Manuales de Uso</x-slot>
    @vite(['resources/css/users-list.css'])

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navbar --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Manuales de Uso</span>
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

        <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-[#003399] transition-colors mb-5">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-600 to-violet-400 flex items-center justify-center shadow-lg">
                        <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-[#001a4d] tracking-tight">Manuales de Uso</h1>
                        <p class="text-slate-500 text-sm mt-1">Consulta y descarga los manuales oficiales del sistema FILE.</p>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-600"></i>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Grid de manuales --}}
            @if($manuals->isEmpty())
                <div class="text-center py-24">
                    <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="book-open" class="w-10 h-10 text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-400">No hay manuales disponibles</h3>
                    <p class="text-slate-400 text-sm mt-2">El administrador aún no ha subido ningún manual.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($manuals as $manual)
                        <div class="manual-version-card group" onclick="openManual({{ $manual->id }})">
                            {{-- Portada del manual --}}
                            <div class="manual-card-cover">
                                <div class="manual-card-logo">FILE</div>
                                <div class="manual-card-version">{{ $manual->version }}</div>
                                <div class="manual-card-type">Manual de Usuario</div>
                                <div class="manual-card-icon-pdf">
                                    <i data-lucide="file-text" class="w-8 h-8 text-white/60"></i>
                                </div>
                            </div>
                            {{-- Info footer --}}
                            <div class="manual-card-footer">
                                <div class="flex-1 min-w-0">
                                    <p class="manual-card-title truncate">FILE {{ $manual->version }}</p>
                                    <p class="manual-card-date">{{ $manual->created_at->format('d/m/Y') }}</p>
                                </div>
                                <div class="manual-open-btn">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </main>
    </div>

    <style>
        .manual-version-card {
            background: white;
            border-radius: 1.25rem;
            border: 1px solid #e8eef6;
            box-shadow: 0 2px 16px -4px rgba(0,26,77,0.06);
            overflow: hidden;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
        }
        .manual-version-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px -8px rgba(91,33,182,0.18), 0 2px 16px -4px rgba(0,26,77,0.08);
            border-color: #c4b5fd;
        }
        .manual-card-cover {
            height: 160px;
            background: linear-gradient(135deg, #2e1065 0%, #5b21b6 55%, #7c3aed 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .manual-card-cover::before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 120px; height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .manual-card-cover::after {
            content: '';
            position: absolute;
            bottom: -20px; left: -20px;
            width: 90px; height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .manual-card-logo {
            font-size: 0.6rem;
            font-weight: 900;
            letter-spacing: 0.3em;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        .manual-card-version {
            font-size: 1.875rem;
            font-weight: 900;
            color: white;
            letter-spacing: -0.02em;
            line-height: 1;
            position: relative;
            z-index: 1;
        }
        .manual-card-type {
            font-size: 0.65rem;
            font-weight: 600;
            color: rgba(255,255,255,0.55);
            letter-spacing: 0.08em;
            margin-top: 0.5rem;
            position: relative;
            z-index: 1;
        }
        .manual-card-icon-pdf {
            position: absolute;
            bottom: 10px; right: 12px;
            z-index: 1;
        }
        .manual-card-footer {
            padding: 0.875rem 1.125rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .manual-card-title {
            font-size: 0.875rem;
            font-weight: 800;
            color: #001a4d;
        }
        .manual-card-date {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.15rem;
        }
        .manual-open-btn {
            width: 32px; height: 32px;
            border-radius: 0.625rem;
            background: #f5f3ff;
            color: #7c3aed;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .manual-version-card:hover .manual-open-btn {
            background: #7c3aed;
            color: white;
        }
        /* Dropdown reutilizado */
        .bg-ei-gradient { background: linear-gradient(135deg, #001a4d 0%, #002b80 100%); }
        .user-dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; top: 100%; right: 0; margin-top: 0.75rem;
            background: white; border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
            border: 1px solid #eef2f6; min-width: 240px;
            opacity: 0; visibility: hidden; transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1); z-index: 50;
        }
        .dropdown-menu.active { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-menu::before {
            content: ''; position: absolute; top: -6px; right: 20px;
            width: 12px; height: 12px; background: white;
            border-left: 1px solid #eef2f6; border-top: 1px solid #eef2f6;
            transform: rotate(45deg);
        }
        .dropdown-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #eef2f6; }
        .dropdown-item {
            padding: 1rem 1.5rem; display: flex; align-items: center;
            gap: 0.75rem; color: #475569; transition: all 0.2s;
            cursor: pointer; text-decoration: none;
        }
        .dropdown-item:hover { background: #f8fafc; color: #001a4d; }
        .dropdown-item:last-child { border-radius: 0 0 1.25rem 1.25rem; }
        .dropdown-item.logout { color: #ef4444; border-top: 1px solid #eef2f6; }
        .dropdown-item.logout:hover { background: #fef2f2; color: #dc2626; }
        .avatar-button { cursor: pointer; transition: all 0.3s; }
        .avatar-button:hover { transform: scale(1.05); }
    </style>

    <script>
        // Generar las URLs base desde PHP
        const manualRouteBase = "{{ url('/manuals') }}/";

        function openManual(id) {
            window.open(manualRouteBase + id, '_blank');
        }

        // Dropdown
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            const btn = document.getElementById('avatarButton');
            const menu = document.getElementById('dropdownMenu');
            if (btn && menu) {
                btn.addEventListener('click', e => { e.stopPropagation(); menu.classList.toggle('active'); });
                document.addEventListener('click', () => menu.classList.remove('active'));
                document.addEventListener('keydown', e => { if (e.key === 'Escape') menu.classList.remove('active'); });
            }
        });
    </script>
</x-app-layout>
