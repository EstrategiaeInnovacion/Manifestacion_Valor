<x-app-layout>
    @vite(['resources/css/users-list.css'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">MVE Completadas</span>
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
            {{-- Header con tabs --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                        MVE <span class="text-green-600">Completadas</span>
                    </h2>
                    <p class="text-slate-500 mt-2">Manifestaciones de Valor enviadas a VUCEM</p>
                </div>
                
                <div class="flex items-center gap-3">
                    {{-- Tabs de navegación --}}
                    <a href="{{ route('mve.pendientes') }}" 
                       class="inline-flex items-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-all">
                        <i data-lucide="clock" class="w-5 h-5 mr-2"></i>
                        Pendientes
                    </a>
                    <a href="{{ route('mve.completadas') }}" 
                       class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white font-semibold rounded-lg">
                        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                        Completadas
                    </a>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-2.5 bg-[#003399] hover:bg-[#001a4d] text-white font-bold rounded-lg transition-all">
                        <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                        Volver al Dashboard
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg">
                    <p class="text-green-700 font-semibold">{{ session('success') }}</p>
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                    <p class="text-red-700 font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Lista de MVE Completadas --}}
            <div class="space-y-4">
                @forelse($mveCompletadas as $acuse)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                {{-- Info del solicitante --}}
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg text-[#001a4d]">
                                            {{ $acuse->applicant->business_name ?? 'Sin nombre' }}
                                        </h3>
                                        <p class="text-slate-500 text-sm">RFC: {{ $acuse->applicant->applicant_rfc ?? 'N/A' }}</p>
                                        
                                        {{-- Folio --}}
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                                @if($acuse->status === 'ACEPTADO') bg-green-100 text-green-700
                                                @elseif($acuse->status === 'RECHAZADO') bg-red-100 text-red-700
                                                @elseif($acuse->status === 'PRUEBA') bg-blue-100 text-blue-700
                                                @else bg-amber-100 text-amber-700
                                                @endif">
                                                <i data-lucide="@if($acuse->status === 'ACEPTADO')check-circle @elseif($acuse->status === 'RECHAZADO')x-circle @else info @endif" class="w-3 h-3 mr-1"></i>
                                                {{ $acuse->status }}
                                            </span>
                                            
                                            @if($acuse->folio_manifestacion)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                                    <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                                                    Folio: {{ $acuse->folio_manifestacion }}
                                                </span>
                                            @endif
                                            
                                            @if($acuse->numero_pedimento)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                                    <i data-lucide="file-text" class="w-3 h-3 mr-1"></i>
                                                    Pedimento: {{ $acuse->numero_pedimento }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        {{-- Fecha de envío --}}
                                        <p class="text-xs text-slate-400 mt-2 flex items-center">
                                            <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                            Enviado: {{ $acuse->fecha_envio ? $acuse->fecha_envio->format('d/m/Y H:i') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                
                                {{-- Acciones --}}
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if($acuse->datosManifestacion)
                                        <a href="{{ route('mve.acuse', $acuse->datosManifestacion->id) }}" 
                                           class="inline-flex items-center px-4 py-2.5 bg-[#003399] hover:bg-[#001a4d] text-white font-bold rounded-lg transition-all">
                                            <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                            Ver Acuse
                                        </a>
                                    @endif
                                    
                                    @if($acuse->xml_enviado)
                                        <button onclick="verXml({{ $acuse->id }})" 
                                           class="inline-flex items-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-all">
                                            <i data-lucide="code" class="w-4 h-4 mr-2"></i>
                                            XML
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Mensaje de VUCEM si existe --}}
                            @if($acuse->mensaje_vucem)
                                <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                    <p class="text-sm text-slate-600">
                                        <strong>Mensaje VUCEM:</strong> {{ $acuse->mensaje_vucem }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="inbox" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-700 mb-2">No hay manifestaciones completadas</h3>
                        <p class="text-slate-500 mb-6">Las manifestaciones enviadas a VUCEM aparecerán aquí.</p>
                        <a href="{{ route('mve.select-applicant') }}" class="inline-flex items-center px-6 py-3 bg-[#003399] hover:bg-[#001a4d] text-white font-bold rounded-lg transition-all">
                            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                            Crear Nueva Manifestación
                        </a>
                    </div>
                @endforelse
            </div>
        </main>
    </div>

    {{-- Modal para ver XML --}}
    <div id="xmlModal" class="fixed inset-0 z-[1000] hidden">
        <div class="fixed inset-0 bg-black/50" onclick="cerrarXmlModal()"></div>
        <div class="fixed inset-4 md:inset-10 bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-bold text-[#001a4d]">XML Enviado a VUCEM</h3>
                <button onclick="cerrarXmlModal()" class="p-2 hover:bg-slate-200 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-4">
                <pre id="xmlContent" class="text-sm font-mono bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto whitespace-pre-wrap"></pre>
            </div>
        </div>
    </div>

    <script>
        // Dropdown de usuario
        document.addEventListener('DOMContentLoaded', function() {
            const avatarButton = document.getElementById('avatarButton');
            const dropdownMenu = document.getElementById('dropdownMenu');
            
            if (avatarButton && dropdownMenu) {
                avatarButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });
                
                document.addEventListener('click', function(e) {
                    if (!dropdownMenu.contains(e.target) && !avatarButton.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }
            
            lucide.createIcons();
        });
        
        // Datos de XML (se llena desde PHP)
        const xmlData = {
            @foreach($mveCompletadas as $acuse)
                {{ $acuse->id }}: @json($acuse->xml_enviado ?? ''),
            @endforeach
        };
        
        function verXml(acuseId) {
            const xml = xmlData[acuseId];
            if (xml) {
                document.getElementById('xmlContent').textContent = xml;
                document.getElementById('xmlModal').classList.remove('hidden');
            }
        }
        
        function cerrarXmlModal() {
            document.getElementById('xmlModal').classList.add('hidden');
        }
        
        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarXmlModal();
            }
        });
    </script>
</x-app-layout>
