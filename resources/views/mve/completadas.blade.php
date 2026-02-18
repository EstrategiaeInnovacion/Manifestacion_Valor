<x-app-layout>
    <x-slot name="title">MVE Completadas</x-slot>
    @vite(['resources/css/users-list.css'])

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegación --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
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
                                        
                                        {{-- Folio y Número de MV --}}
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                                @if($acuse->status === 'ACEPTADO') bg-green-100 text-green-700
                                                @elseif($acuse->status === 'RECHAZADO') bg-red-100 text-red-700
                                                @elseif($acuse->status === 'PRUEBA') bg-blue-100 text-blue-700
                                                @else bg-amber-100 text-amber-700
                                                @endif">
                                                <i data-lucide="@if($acuse->status === 'ACEPTADO')check-circle @elseif($acuse->status === 'RECHAZADO')x-circle @else{{ 'info' }}@endif" class="w-3 h-3 mr-1"></i>
                                                {{ $acuse->status }}
                                            </span>

                                            @if($acuse->folio_manifestacion)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                                    <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                                                    Folio: {{ $acuse->folio_manifestacion }}
                                                </span>
                                            @endif

                                            @if($acuse->numero_cove)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                                    <i data-lucide="award" class="w-3 h-3 mr-1"></i>
                                                    MV: {{ $acuse->numero_cove }}
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
                                    {{-- Botón Consultar VUCEM --}}
                                    <button onclick="abrirModalConsulta({{ $acuse->id }}, '{{ $acuse->applicant->applicant_rfc }}')"
                                       class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition-all">
                                        <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                        Consultar
                                    </button>

                                    @if($acuse->acuse_pdf)
                                        <a href="{{ route('mve.acuse.pdf', ['manifestacion' => $acuse->datos_manifestacion_id]) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-all">
                                            <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                            Acuse PDF
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

    {{-- Modal de Vista Previa Completa --}}
    <div id="vistaPreviaModal" class="fixed inset-0 z-[1100] hidden">
        <div class="fixed inset-0 bg-black/50" onclick="cerrarVistaPreviaModal()"></div>
        <div class="fixed inset-y-4 inset-x-4 md:inset-x-auto md:left-1/2 md:-translate-x-1/2 md:w-full md:max-w-5xl bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-slate-600 to-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i data-lucide="file-text" class="w-6 h-6 mr-3"></i>
                            Vista Previa - Manifestación de Valor
                        </h3>
                        <p class="text-white/90 text-sm mt-2">Documento oficial obtenido de VUCEM</p>
                    </div>
                    <button onclick="cerrarVistaPreviaModal()" class="text-white/80 hover:text-white transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-auto p-6" id="vistaPreviaContenido">
                <!-- El contenido se llenará dinámicamente -->
            </div>

            <div class="p-6 border-t border-slate-200 bg-slate-50">
                <button onclick="cerrarVistaPreviaModal()"
                    class="w-full px-6 py-3 bg-slate-600 hover:bg-slate-700 text-white font-bold rounded-lg transition-all">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal para Consultar Manifestación --}}
    <div id="consultaModal" class="fixed inset-0 z-[1000] hidden">
        <div class="fixed inset-0 bg-black/50" onclick="cerrarConsultaModal()"></div>
        <div class="fixed inset-y-10 inset-x-4 md:inset-x-auto md:left-1/2 md:-translate-x-1/2 md:w-full md:max-w-lg bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-emerald-500 to-teal-600">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i data-lucide="search" class="w-6 h-6 mr-3"></i>
                    Consultar Manifestación en VUCEM
                </h3>
                <p class="text-emerald-50 text-sm mt-2">Obtenga el Número de MV y acuse sellado</p>
            </div>

            <div class="flex-1 overflow-auto p-6">
                {{-- Formulario de consulta --}}
                <form id="formConsulta" onsubmit="consultarManifestacion(event)" class="space-y-4">
                    <input type="hidden" id="consultaAcuseId" name="acuse_id">

                    {{-- RFC (solo lectura) --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            RFC del Importador
                        </label>
                        <input
                            type="text"
                            id="consultaRfc"
                            readonly
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg bg-slate-50 font-mono text-slate-700"
                        />
                    </div>

                    {{-- Folio (editable) --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Folio de Manifestación
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="consultaFolio"
                            name="folio"
                            required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all font-mono"
                            placeholder="Ej: MNVA26001W5O7 o número de operación"
                        />
                        <p class="text-xs text-slate-500 mt-2">
                            <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
                            Puede usar el Número de MV (MNVA...) o el número de operación
                        </p>
                    </div>

                    {{-- Clave Web Service --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Clave Web Service VUCEM
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="password"
                            id="consultaClaveWs"
                            name="clave_webservice"
                            required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all font-mono"
                            placeholder="Ingrese su clave de web service"
                        />
                        <p class="text-xs text-slate-500 mt-2">
                            <i data-lucide="lock" class="w-3 h-3 inline mr-1"></i>
                            La clave no se almacena en el sistema
                        </p>
                    </div>

                    {{-- Área de resultados --}}
                    <div id="consultaResultado" class="hidden mt-4"></div>

                    {{-- Botones --}}
                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            onclick="cerrarConsultaModal()"
                            class="flex-1 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-all">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            id="btnConsultar"
                            class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition-all flex items-center justify-center">
                            <span id="btnConsultarTexto"><i data-lucide="search" class="w-5 h-5 mr-2"></i>Consultar</span>
                        </button>
                    </div>
                </form>
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
                cerrarConsultaModal();
                cerrarVistaPreviaModal();
            }
        });

        // ==========================================
        // Funcionalidad de Consulta VUCEM
        // ==========================================

        // Datos de acuses (se llena desde PHP)
        const acusesData = {
            @foreach($mveCompletadas as $acuse)
                {{ $acuse->id }}: {
                    id: {{ $acuse->id }},
                    applicant_id: {{ $acuse->applicant_id }},
                    rfc: '{{ $acuse->applicant->applicant_rfc }}',
                    folio: '{{ $acuse->folio_manifestacion }}',
                    business_name: '{{ $acuse->applicant->business_name }}'
                },
            @endforeach
        };

        // Variable global para saber si se usan credenciales almacenadas en consulta
        let consultaUsaCredencialesAlmacenadas = false;

        function abrirModalConsulta(acuseId, rfc) {
            const acuse = acusesData[acuseId];
            if (!acuse) {
                alert('No se encontró información del acuse');
                return;
            }

            // Llenar datos en el modal
            document.getElementById('consultaAcuseId').value = acuseId;
            document.getElementById('consultaRfc').value = acuse.rfc;
            document.getElementById('consultaFolio').value = '';

            // Limpiar campos
            const wsInput = document.getElementById('consultaClaveWs');
            const wsContainer = wsInput.closest('div');
            wsInput.value = '';
            document.getElementById('consultaResultado').classList.add('hidden');
            document.getElementById('consultaResultado').innerHTML = '';

            // Verificar si tiene clave WS almacenada
            consultaUsaCredencialesAlmacenadas = false;
            fetch(`/cove/credenciales/${acuse.applicant_id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.has_webservice_key) {
                    // Ocultar campo y quitar required
                    wsContainer.style.display = 'none';
                    wsInput.removeAttribute('required');
                    consultaUsaCredencialesAlmacenadas = true;
                } else {
                    // Mostrar campo y poner required
                    wsContainer.style.display = '';
                    wsInput.setAttribute('required', 'required');
                    consultaUsaCredencialesAlmacenadas = false;
                }
            })
            .catch(() => {
                // En caso de error, mostrar el campo
                wsContainer.style.display = '';
                wsInput.setAttribute('required', 'required');
                consultaUsaCredencialesAlmacenadas = false;
            });

            // Mostrar modal
            document.getElementById('consultaModal').classList.remove('hidden');

            // Re-render icons
            lucide.createIcons();
        }

        function cerrarConsultaModal() {
            document.getElementById('consultaModal').classList.add('hidden');
        }

        function cerrarVistaPreviaModal() {
            document.getElementById('vistaPreviaModal').classList.add('hidden');
        }

        function abrirVistaPreviaCompleta() {
            const data = window.consultaActual;
            if (!data || !data.datos_manifestacion) {
                alert('No hay datos de manifestación disponibles');
                return;
            }

            const dm = data.datos_manifestacion;
            const contenido = document.getElementById('vistaPreviaContenido');

            // Función helper para formatear fechas
            const formatFecha = (fecha) => {
                if (!fecha) return 'N/A';
                try {
                    const date = new Date(fecha);
                    return date.toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' });
                } catch(e) {
                    return fecha;
                }
            };

            let html = `
                <!-- Documento Oficial gob.mx -->
                <div class="bg-white shadow-lg" style="font-family: 'Montserrat', 'Segoe UI', sans-serif;">
                    <!-- Header gob.mx -->
                    <div class="bg-slate-600 text-white px-8 py-6">
                        <div class="text-sm font-light mb-4">gob.mx</div>
                        <h1 class="text-2xl font-bold mb-2">MANIFESTACIÓN DE VALOR</h1>
                        <p class="text-sm font-light">Ventanilla Digital Mexicana de Comercio Exterior</p>
                        <p class="text-xs font-light mt-1">Promoción y defensa en materia de Comercio exterior</p>
                    </div>

                    <!-- Contenido del documento -->
                    <div class="px-8 py-6 space-y-6">
                        <!-- Datos de la Manifestación de valor -->
                        <div>
                            <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                Datos de la Manifestación de valor
                            </h2>
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">RFC del importador</th>
                                        <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Nombre o Razón social</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-slate-300 px-4 py-2 text-sm">${acusesData[window.consultaActual.acuse_id]?.rfc || 'N/A'}</td>
                                        <td class="border border-slate-300 px-4 py-2 text-sm">${acusesData[window.consultaActual.acuse_id]?.business_name || 'N/A'}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Persona Consulta -->
                        ${dm.persona_consulta ? `
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Persona que Consulta
                                </h2>
                                <table class="w-full border-collapse mb-4">
                                    <tbody>
                                        ${dm.persona_consulta.rfc ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 w-1/3">
                                                    RFC
                                                </td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono">
                                                    ${dm.persona_consulta.rfc}
                                                </td>
                                            </tr>
                                        ` : ''}
                                        ${dm.persona_consulta.tipo_figura ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                    Tipo de Figura
                                                </td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">
                                                    ${dm.persona_consulta.tipo_figura}
                                                </td>
                                            </tr>
                                        ` : ''}
                                    </tbody>
                                </table>
                            </div>
                        ` : ''}

                        <!-- Documentos -->
                        ${dm.documentos && dm.documentos.length > 0 ? `
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Documentos (eDocuments)
                                </h2>
                                <div class="space-y-2">
                                    ${dm.documentos.map(doc => `
                                        <div class="bg-blue-50 border border-blue-200 rounded px-4 py-2">
                                            <span class="font-mono text-sm text-blue-900">${doc}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}

                        <!-- Información General de la MV -->
                        <div>
                            <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                Información General
                            </h2>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="bg-slate-50 border border-slate-200 rounded p-3">
                                    <p class="text-xs font-semibold text-slate-600 mb-1">Número de MV</p>
                                    <p class="text-sm font-bold text-slate-900">${data.numero_mv || 'N/A'}</p>
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded p-3">
                                    <p class="text-xs font-semibold text-slate-600 mb-1">Estado</p>
                                    <p class="text-sm font-bold text-green-700">${data.status || 'N/A'}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Información de COVEs (todos) -->
                        ${dm.informacion_coves && dm.informacion_coves.length > 0 ? dm.informacion_coves.map((cove, idx) => `
                            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h2 class="text-base font-bold text-blue-900 mb-3 pb-2 border-b-2 border-blue-300">
                                    Información COVE ${idx + 1} ${cove.cove ? '- ' + cove.cove : ''}
                                </h2>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    ${cove.cove ? `
                                        <div class="bg-white border border-slate-200 rounded p-3">
                                            <p class="text-xs font-semibold text-slate-600 mb-1">COVE</p>
                                            <p class="text-sm font-mono text-slate-900">${cove.cove}</p>
                                        </div>
                                    ` : ''}
                                    ${cove.pedimento_numero ? `
                                        <div class="bg-white border border-slate-200 rounded p-3">
                                            <p class="text-xs font-semibold text-slate-600 mb-1">Pedimento</p>
                                            <p class="text-sm font-mono text-slate-900">${cove.pedimento_numero}</p>
                                        </div>
                                    ` : ''}
                                    ${cove.patente ? `
                                        <div class="bg-white border border-slate-200 rounded p-3">
                                            <p class="text-xs font-semibold text-slate-600 mb-1">Patente</p>
                                            <p class="text-sm font-mono text-slate-900">${cove.patente}</p>
                                        </div>
                                    ` : ''}
                                    ${cove.aduana ? `
                                        <div class="bg-white border border-slate-200 rounded p-3">
                                            <p class="text-xs font-semibold text-slate-600 mb-1">Aduana</p>
                                            <p class="text-sm font-mono text-slate-900">${cove.aduana}</p>
                                        </div>
                                    ` : ''}
                                </div>

                                <table class="w-full border-collapse mb-4">
                                    <tbody>
                                        ${cove.metodo_valoracion ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 w-1/3">Método de valoración aduanera</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${cove.metodo_valoracion}</td>
                                            </tr>
                                        ` : ''}
                                        ${cove.incoterm ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">Incoterm</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${cove.incoterm}</td>
                                            </tr>
                                        ` : ''}
                                        ${cove.existe_vinculacion ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">Existe Vinculación</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${cove.existe_vinculacion}</td>
                                            </tr>
                                        ` : ''}
                                    </tbody>
                                </table>

                                ${cove.precio_pagado ? `
                                    <h3 class="text-sm font-bold text-slate-800 mb-2 mt-3">Precio Pagado</h3>
                                    <table class="w-full border-collapse">
                                        <tbody>
                                            ${cove.precio_pagado.fecha_pago ? `
                                                <tr>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 w-1/3">Fecha de Pago</td>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm">${formatFecha(cove.precio_pagado.fecha_pago)}</td>
                                                </tr>
                                            ` : ''}
                                            ${cove.precio_pagado.total ? `
                                                <tr>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">Total</td>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm font-mono">$${cove.precio_pagado.total} ${cove.precio_pagado.moneda || ''}</td>
                                                </tr>
                                            ` : ''}
                                            ${cove.precio_pagado.tipo_cambio ? `
                                                <tr>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">Tipo de Cambio</td>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm font-mono">${cove.precio_pagado.tipo_cambio}</td>
                                                </tr>
                                            ` : ''}
                                            ${cove.precio_pagado.tipo_pago ? `
                                                <tr>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">Tipo de Pago</td>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm">${cove.precio_pagado.tipo_pago}</td>
                                                </tr>
                                            ` : ''}
                                            ${cove.precio_pagado.especifique ? `
                                                <tr>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">Especifique</td>
                                                    <td class="border border-slate-300 px-4 py-2 text-sm">${cove.precio_pagado.especifique}</td>
                                                </tr>
                                            ` : ''}
                                        </tbody>
                                    </table>
                                ` : ''}
                            </div>
                        `).join('') : `
                            <!-- Fallback: mostrar datos del primer COVE si no hay array -->
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Información General
                                </h2>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="bg-slate-50 border border-slate-200 rounded p-3">
                                        <p class="text-xs font-semibold text-slate-600 mb-1">COVE</p>
                                        <p class="text-sm font-mono text-slate-900">${dm.cove || 'N/A'}</p>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-200 rounded p-3">
                                        <p class="text-xs font-semibold text-slate-600 mb-1">Pedimento</p>
                                        <p class="text-sm font-mono text-slate-900">${dm.pedimento_numero || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                        `}

                        <!-- Precios Por Pagar -->
                        ${dm.precios_por_pagar && dm.precios_por_pagar.length > 0 ? `
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Precios Por Pagar
                                </h2>
                                ${dm.precios_por_pagar.map((ppp, idx) => `
                                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded">
                                        <h3 class="text-sm font-bold text-amber-900 mb-2">Precio Por Pagar ${idx + 1}</h3>
                                        <table class="w-full border-collapse">
                                            <tbody>
                                                ${ppp.fecha_pago ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 w-1/3">
                                                            Fecha de Pago
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${formatFecha(ppp.fecha_pago)}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${ppp.total ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Total
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-mono">
                                                            $${ppp.total} ${ppp.moneda || ''}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${ppp.tipo_cambio ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Tipo de Cambio
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-mono">
                                                            ${ppp.tipo_cambio}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${ppp.tipo_pago ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Tipo de Pago
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${ppp.tipo_pago}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${ppp.situacion_no_fecha ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Situación sin Fecha de Pago
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${ppp.situacion_no_fecha}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${ppp.especifique ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Especifique
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${ppp.especifique}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                            </tbody>
                                        </table>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}

                        <!-- Compensos de Pago -->
                        ${dm.compensos_pago && dm.compensos_pago.length > 0 ? `
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Compensos de Pago
                                </h2>
                                ${dm.compensos_pago.map((cp, idx) => `
                                    <div class="mb-4 p-4 bg-purple-50 border border-purple-200 rounded">
                                        <h3 class="text-sm font-bold text-purple-900 mb-2">Compenso ${idx + 1}</h3>
                                        <table class="w-full border-collapse">
                                            <tbody>
                                                ${cp.fecha ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 w-1/3">
                                                            Fecha
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${formatFecha(cp.fecha)}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${cp.motivo ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Motivo
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${cp.motivo}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${cp.prestacion_mercancia ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Prestación Mercancía
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${cp.prestacion_mercancia}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${cp.tipo_pago ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Tipo de Pago
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${cp.tipo_pago}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                                ${cp.especifique ? `
                                                    <tr>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                            Especifique
                                                        </td>
                                                        <td class="border border-slate-300 px-4 py-2 text-sm">
                                                            ${cp.especifique}
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                            </tbody>
                                        </table>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}

                        <!-- Incrementables -->
                        ${dm.incrementables && dm.incrementables.length > 0 ? `
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Incrementables
                                </h2>
                                <table class="w-full border-collapse mb-4">
                                    <thead>
                                        <tr class="bg-slate-100">
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Tipo</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Fecha Erogación</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Importe</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Moneda</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Tipo Cambio</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">A Cargo Importador</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${dm.incrementables.map(incr => `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${incr.tipo || 'N/A'}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${formatFecha(incr.fecha_erogacion)}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono">$${incr.importe || '0'}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${incr.moneda || 'N/A'}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono">${incr.tipo_cambio || 'N/A'}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${incr.a_cargo_importador || 'N/A'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : ''}

                        <!-- Decrementables -->
                        ${dm.decrementables && dm.decrementables.length > 0 ? `
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Decrementables
                                </h2>
                                <table class="w-full border-collapse mb-4">
                                    <thead>
                                        <tr class="bg-slate-100">
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Tipo</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Importe</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Moneda</th>
                                            <th class="border border-slate-300 px-4 py-2 text-left text-sm font-semibold text-slate-700">Tipo Cambio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${dm.decrementables.map(decr => `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${decr.tipo || 'N/A'}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono">$${decr.importe || '0'}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm">${decr.moneda || 'N/A'}</td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono">${decr.tipo_cambio || 'N/A'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : ''}

                        <!-- Valor en Aduana -->
                        ${dm.valor_aduana ? `
                            <div>
                                <h2 class="text-base font-bold text-slate-800 mb-3 pb-2 border-b-2 border-slate-300">
                                    Valor en Aduana
                                </h2>
                                <table class="w-full border-collapse mb-4">
                                    <tbody>
                                        ${dm.valor_aduana.precio_pagado ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 w-1/3">
                                                    Total Precio Pagado
                                                </td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono font-bold text-green-700">
                                                    $${dm.valor_aduana.precio_pagado}
                                                </td>
                                            </tr>
                                        ` : ''}
                                        ${dm.valor_aduana.precio_por_pagar ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                    Total Precio por Pagar
                                                </td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono">
                                                    $${dm.valor_aduana.precio_por_pagar}
                                                </td>
                                            </tr>
                                        ` : ''}
                                        ${dm.valor_aduana.incrementables ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                    Total Incrementables
                                                </td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono text-green-700">
                                                    $${dm.valor_aduana.incrementables}
                                                </td>
                                            </tr>
                                        ` : ''}
                                        ${dm.valor_aduana.decrementables ? `
                                            <tr>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">
                                                    Total Decrementables
                                                </td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono text-red-700">
                                                    $${dm.valor_aduana.decrementables}
                                                </td>
                                            </tr>
                                        ` : ''}
                                        ${dm.valor_aduana.total ? `
                                            <tr class="bg-green-50">
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-bold text-slate-800 bg-slate-200">
                                                    VALOR EN ADUANA TOTAL
                                                </td>
                                                <td class="border border-slate-300 px-4 py-2 text-sm font-mono font-bold text-green-800 text-lg">
                                                    $${dm.valor_aduana.total}
                                                </td>
                                            </tr>
                                        ` : ''}
                                    </tbody>
                                </table>
                            </div>
                        ` : ''}

                        <!-- Pie de página oficial -->
                        <div class="mt-8 pt-4 border-t border-slate-300 text-center text-xs text-slate-500">
                            <p>Documento generado desde el sistema de Manifestación de Valor</p>
                            <p class="mt-1">Ventanilla Digital Mexicana de Comercio Exterior - VUCEM</p>
                        </div>
                    </div>
                </div>
            `;

            contenido.innerHTML = html;
            document.getElementById('vistaPreviaModal').classList.remove('hidden');
            lucide.createIcons();
        }

        async function consultarManifestacion(event) {
            event.preventDefault();

            const acuseId = document.getElementById('consultaAcuseId').value;
            const folio = document.getElementById('consultaFolio').value.trim();
            const claveWs = document.getElementById('consultaClaveWs').value;
            const btnConsultar = document.getElementById('btnConsultar');
            const btnTexto = document.getElementById('btnConsultarTexto');
            const resultadoDiv = document.getElementById('consultaResultado');

            // Validar
            if (!folio) {
                mostrarResultadoConsulta('error', 'Debe ingresar el folio de manifestación');
                return;
            }

            if (!claveWs && !consultaUsaCredencialesAlmacenadas) {
                mostrarResultadoConsulta('error', 'Debe ingresar la clave de web service');
                return;
            }

            // Deshabilitar botón
            btnConsultar.disabled = true;
            btnTexto.innerHTML = '<i data-lucide="loader" class="w-5 h-5 mr-2 animate-spin"></i>Consultando...';
            lucide.createIcons();
            resultadoDiv.classList.add('hidden');

            try {
                const response = await fetch(`/mve/consultar/${acuseId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        folio: folio,
                        clave_webservice: claveWs
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Guardar datos para vista previa (incluir acuse_id)
                    window.consultaActual = {
                        ...data.data,
                        acuse_id: acuseId
                    };

                    // Mostrar resultado
                    mostrarResultadoConsulta('success', data.message, { ...data.data, acuse_id: acuseId });
                } else {
                    mostrarResultadoConsulta('error', data.message, null, data.errores);
                }

            } catch (error) {
                console.error('Error:', error);
                mostrarResultadoConsulta('error', 'Error de conexión al consultar la manifestación');
            } finally {
                // Re-habilitar botón
                btnConsultar.disabled = false;
                btnTexto.innerHTML = '<i data-lucide="search" class="w-5 h-5 mr-2"></i>Consultar';
                lucide.createIcons();
            }
        }

        function mostrarResultadoConsulta(tipo, mensaje, data = null, errores = null) {
            const resultadoDiv = document.getElementById('consultaResultado');

            if (tipo === 'success') {
                resultadoDiv.innerHTML = `
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i data-lucide="check-circle" class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5"></i>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-green-800 mb-2">¡Consulta Exitosa!</h4>
                                <p class="text-sm text-green-700 mb-3">${mensaje}</p>
                                ${data ? `
                                    <div class="space-y-2 text-sm mb-4">
                                        ${data.numero_mv ? `
                                            <div class="flex items-center">
                                                <i data-lucide="award" class="w-4 h-4 text-green-600 mr-2"></i>
                                                <span class="font-semibold text-green-800">Número de MV:</span>
                                                <span class="ml-2 font-mono text-green-900">${data.numero_mv}</span>
                                            </div>
                                        ` : ''}
                                        ${data.status ? `
                                            <div class="flex items-center">
                                                <i data-lucide="info" class="w-4 h-4 text-green-600 mr-2"></i>
                                                <span class="font-semibold text-green-800">Estado:</span>
                                                <span class="ml-2">${data.status}</span>
                                            </div>
                                        ` : ''}
                                        </div>
                                    ${data.datos_manifestacion ? `
                                        <div class="flex flex-wrap gap-2 mt-4">
                                            <button onclick="abrirVistaPreviaCompleta()"
                                                class="flex-1 min-w-[140px] px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-all flex items-center justify-center">
                                                <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                                Ver Datos Completos
                                            </button>
                                            <a href="/mve/consultar/${data.acuse_id}/xml" target="_blank"
                                                class="flex-1 min-w-[140px] px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition-all flex items-center justify-center no-underline">
                                                <i data-lucide="file-down" class="w-4 h-4 mr-2"></i>
                                                Descargar XML Acuse
                                            </a>
                                            <button onclick="window.location.reload()"
                                                class="flex-1 min-w-[140px] px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-all flex items-center justify-center">
                                                <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                                                Actualizar Lista
                                            </button>
                                        </div>
                                    ` : `
                                        <div class="flex flex-wrap gap-2 mt-4">
                                            <a href="/mve/consultar/${data.acuse_id}/xml" target="_blank"
                                                class="flex-1 min-w-[140px] px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition-all flex items-center justify-center no-underline">
                                                <i data-lucide="file-down" class="w-4 h-4 mr-2"></i>
                                                Descargar XML Acuse
                                            </a>
                                            <button onclick="window.location.reload()"
                                                class="flex-1 min-w-[140px] px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-all flex items-center justify-center">
                                                <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                                                Actualizar Lista
                                            </button>
                                        </div>
                                    `}
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                resultadoDiv.innerHTML = `
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mr-3 flex-shrink-0 mt-0.5"></i>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-red-800 mb-2">Error en Consulta</h4>
                                <p class="text-sm text-red-700">${mensaje}</p>
                                ${errores && errores.length > 0 ? `
                                    <ul class="mt-2 text-xs text-red-600 list-disc list-inside">
                                        ${errores.map(e => `<li>${e.descripcion || e}</li>`).join('')}
                                    </ul>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            resultadoDiv.classList.remove('hidden');
            lucide.createIcons();
        }
    </script>
</x-app-layout>
