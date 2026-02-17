@php
    use App\Constants\VucemCatalogs;
@endphp

<x-app-layout>
    <x-slot name="title">Nueva Manifestación de Valor</x-slot>
    @vite(['resources/css/mve-manual.css', 'resources/js/mve-manual.js'])

    {{-- Data attributes para JavaScript --}}
    <div id="mveManualData"
         class="mve-data-container"
         data-applicant-rfc="{{ $applicant->applicant_rfc }}"
         data-applicant-id="{{ $applicant->id }}"
         data-search-url="{{ route('mve.rfc-consulta.search') }}"
         data-store-url="{{ route('mve.rfc-consulta.store') }}"
         data-delete-url="{{ route('mve.rfc-consulta.delete') }}"
         data-persona-consulta='@json(optional($datosManifestacion)->persona_consulta ?? [])'
         data-metodo-valoracion="{{ optional($datosManifestacion)->metodo_valoracion ?? '' }}"
         data-existe-vinculacion="{{ optional($datosManifestacion)->existe_vinculacion ?? '' }}"
         data-pedimento="{{ isset($datosExtraidos) ? ($datosExtraidos['datos_manifestacion']['pedimento_completo'] ?? $datosExtraidos['datos_manifestacion']['pedimento'] ?? '') : (optional($datosManifestacion)->pedimento ?? '') }}"
         data-patente="{{ isset($datosExtraidos) ? ($datosExtraidos['datos_manifestacion']['patente'] ?? '') : (optional($datosManifestacion)->patente ?? '') }}"
         data-aduana="{{ isset($datosExtraidos) ? ($datosExtraidos['datos_manifestacion']['aduana'] ?? '') : (optional($datosManifestacion)->aduana ?? '') }}"
         data-informacion-cove='@json(isset($datosExtraidos) ? $datosExtraidos["informacion_cove"] : (optional($informacionCove)->informacion_cove ?? []))'
         data-pedimentos='@json(optional($informacionCove)->pedimentos ?? [])'
         data-incrementables='@json(optional($informacionCove)->incrementables ?? [])'
         data-decrementables='@json(optional($informacionCove)->decrementables ?? [])'
         data-precio-pagado='@json(optional($informacionCove)->precio_pagado ?? [])'
         data-precio-por-pagar='@json(optional($informacionCove)->precio_por_pagar ?? [])'
         data-compenso-pago='@json(optional($informacionCove)->compenso_pago ?? [])'
         data-valor-aduana='@json(optional($informacionCove)->valor_en_aduana)'
         data-documentos='@json(isset($datosExtraidos) ? $datosExtraidos["documentos"] : (optional($documentos)->documentos ?? []))'
         data-tipos-documento='@json($tiposDocumento ?? [])'
         data-has-vucem-credentials='{{ $applicant->hasVucemCredentials() ? "true" : "false" }}'
         data-has-webservice-key='{{ $applicant->hasWebserviceKey() ? "true" : "false" }}'
         data-incoterm-archivo-m="{{ isset($datosExtraidos) && isset($datosExtraidos['informacion_cove'][0]) ? ($datosExtraidos['informacion_cove'][0]['incoterm'] ?? '') : '' }}"
         data-vinculacion-archivo-m="{{ isset($datosExtraidos) ? ($datosExtraidos['vinculacion'] ?? '') : '' }}"
         data-rfc-agente-archivo-m="{{ isset($datosExtraidos) ? ($datosExtraidos['datos_manifestacion']['rfc_agente_aduanal'] ?? '') : '' }}"
         data-tipo-cambio-archivo-m="{{ isset($datosExtraidos) ? ($datosExtraidos['datos_manifestacion']['tipo_cambio'] ?? '') : '' }}"
         data-desde-archivo-m="{{ isset($datosExtraidos) ? 'true' : 'false' }}"
         data-initial-step="{{ $initialStep }}">
    </div>

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegaci&oacute;n --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">MVE</span>
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
                                        <span class="font-semibold text-sm">Cerrar Sesi&oacute;n</span>
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
                <div class="flex items-center gap-4 mb-6">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                        Volver al Dashboard
                    </a>
                    <span class="text-slate-300">|</span>
                    <a href="{{ route('mve.select-applicant', ['mode' => 'manual']) }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors">
                        <i data-lucide="repeat" class="w-4 h-4 mr-2"></i>
                        Cambiar Solicitante
                    </a>
                </div>

                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Crear <span class="text-[#003399]">MVE</span>
                </h2>
                <p class="text-slate-500 mt-2">Complete el formulario con los datos de la manifestación</p>
            </div>

            {{-- Información del Solicitante --}}
            <div class="applicant-info-card">
                <div class="flex items-center gap-4">
                    <div class="applicant-info-icon">
                        <i data-lucide="building-2" class="w-6 h-6"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-[#001a4d]">{{ $applicant->business_name }}</h3>
                        <p class="text-sm text-slate-500 mt-1">RFC: <span class="font-semibold text-[#003399]">{{ $applicant->applicant_rfc }}</span></p>
                    </div>
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- STEPPER INDICATOR --}}
            {{-- ============================================ --}}
            <div class="mve-stepper mb-8" id="mveStepperIndicator">
                <div class="flex items-center justify-between relative">
                    {{-- Línea conectora --}}
                    <div class="absolute top-5 left-0 right-0 h-0.5 bg-slate-200 z-0"></div>
                    <div class="absolute top-5 left-0 h-0.5 bg-[#003399] z-0 transition-all duration-500" id="stepperProgressLine" style="width: 0%"></div>

                    @php
                        $steps = [
                            ['num' => 1, 'label' => 'Datos de Manifestación', 'icon' => 'file-text'],
                            ['num' => 2, 'label' => 'Información COVE', 'icon' => 'receipt'],
                            ['num' => 3, 'label' => 'Valor en Aduana', 'icon' => 'dollar-sign'],
                            ['num' => 4, 'label' => 'Documentos', 'icon' => 'paperclip'],
                            ['num' => 5, 'label' => 'Vista Previa', 'icon' => 'eye'],
                        ];
                    @endphp

                    @foreach($steps as $step)
                    <button type="button" onclick="goToStep({{ $step['num'] }})"
                            class="stepper-step relative z-10 flex flex-col items-center group"
                            id="stepIndicator{{ $step['num'] }}"
                            data-step="{{ $step['num'] }}">
                        <div class="stepper-circle w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300
                            {{ $step['num'] === 1 ? 'border-[#003399] bg-[#003399] text-white' : 'border-slate-300 bg-white text-slate-400' }}">
                            <i data-lucide="{{ $step['icon'] }}" class="w-5 h-5"></i>
                        </div>
                        <span class="stepper-label text-[10px] font-bold uppercase tracking-wide mt-2 transition-colors duration-300 text-center leading-tight max-w-[80px]
                            {{ $step['num'] === 1 ? 'text-[#003399]' : 'text-slate-400' }}">
                            {{ $step['label'] }}
                        </span>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- STEPS (modularizados en archivos separados) --}}
            {{-- ============================================ --}}
            @include('mve.steps.step-1-datos-manifestacion')
            @include('mve.steps.step-2-informacion-cove')
            @include('mve.steps.step-3-valor-aduana')
            @include('mve.steps.step-4-documentos')
            @include('mve.steps.step-5-vista-previa')

        </main>
    </div>

    {{-- Modal de Notificaciones --}}
    <div id="notificationModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-[60]">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
            <div id="notificationHeader" class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                <div id="notificationIcon" class="w-10 h-10 rounded-full flex items-center justify-center">
                    <i data-lucide="info" class="w-6 h-6"></i>
                </div>
                <h3 id="notificationTitle" class="text-lg font-semibold text-slate-900">Notificación</h3>
            </div>
            <div class="px-6 py-5">
                <p id="notificationMessage" class="text-slate-600 leading-relaxed"></p>
            </div>
            <div class="px-6 py-4 bg-slate-50 rounded-b-xl flex justify-end">
                <button type="button" onclick="closeNotificationModal()" id="notificationCloseBtn" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal de Vista Previa --}}
    <div id="previewModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-[70]">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full mx-4 my-8 max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header del Modal -->
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-slate-600 to-slate-700 text-white">
                <div class="flex items-center gap-3">
                    <i data-lucide="eye" class="w-6 h-6"></i>
                    <h3 class="text-xl font-semibold">Vista Previa - Manifestación de Valor</h3>
                </div>
                <button type="button" onclick="closePreviewModal()" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="previewContent" class="space-y-8">
                    <!-- El contenido se llenará dinámicamente -->
                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                <p class="text-sm text-slate-500">
                    <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                    Revise cuidadosamente todos los datos antes de guardar la manifestación final
                </p>
                <div class="flex gap-3">
                    <button type="button" onclick="closePreviewModal()" class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="confirmarGuardadoFinal()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <i data-lucide="check" class="w-4 h-4 inline mr-2"></i>
                        Confirmar y Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Confirmación para Borrar Borrador --}}
    <div id="confirmDeleteDraftModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-[60]">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">Confirmar Eliminación</h3>
            </div>
            <div class="px-6 py-5">
                <p class="text-slate-600 leading-relaxed">¿Estás seguro de que quieres borrar este borrador? Esta acción no se puede deshacer y perderás todo el progreso guardado.</p>
            </div>
            <div class="px-6 py-4 bg-slate-50 rounded-b-xl flex justify-end gap-3">
                <button type="button" onclick="cerrarModalBorrarBorrador()" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="button" onclick="ejecutarBorrarBorrador()" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                    Sí, Borrar
                </button>
            </div>
        </div>
    </div>

    {{-- Modales --}}
    {{-- Modal: RFC No Encontrado --}}
    <div id="rfcNotFoundModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target === this) closeRfcNotFoundModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">RFC No Encontrado</h3>
                </div>
            </div>
            <div class="p-6">
                <p class="text-slate-700 text-center mb-6">
                    El RFC no se encuentra registrado en la BD del sistema
                </p>
                <div class="flex justify-center">
                    <button onclick="closeRfcNotFoundModal()" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded-lg transition-colors">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: RFC Encontrado --}}
    <div id="rfcFoundModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target === this) closeRfcFoundModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">RFC Encontrado</h3>
                </div>
            </div>
            <div class="p-6">
                <p class="text-slate-700 text-center mb-6">
                    El RFC de consulta existe en la BD del sistema
                </p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeRfcFoundModal()" class="px-6 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button onclick="confirmAddRfcConsulta()" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts para la funcionalidad de borrar borrador --}}
    <script>
        function confirmarBorrarBorrador() {
            const modal = document.getElementById('confirmDeleteDraftModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalBorrarBorrador() {
            const modal = document.getElementById('confirmDeleteDraftModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function ejecutarBorrarBorrador() {
            try {
                // Mostrar indicador de carga
                const btnBorrar = document.querySelector('#confirmDeleteDraftModal button[onclick="ejecutarBorrarBorrador()"]');
                btnBorrar.disabled = true;
                btnBorrar.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i>Borrando...';

                // Realizar petición para borrar el borrador
                const response = await fetch('{{ route("mve.borrar-borrador") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        applicant_id: {{ $applicant->id }}
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Cerrar modal
                    cerrarModalBorrarBorrador();

                    // Mostrar notificación de éxito
                    window.showNotification('El borrador se ha eliminado correctamente.', 'success', 'Borrador Eliminado');

                    // Redirigir al dashboard después de un breve delay
                    setTimeout(() => {
                        window.location.href = '{{ route("dashboard") }}';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Error al borrar el borrador');
                }
            } catch (error) {
                console.error('Error:', error);

                // Restaurar el botón
                const btnBorrar = document.querySelector('#confirmDeleteDraftModal button[onclick="ejecutarBorrarBorrador()"]');
                btnBorrar.disabled = false;
                btnBorrar.innerHTML = 'Sí, Borrar';

                // Mostrar notificación de error
                window.showNotification('Ocurrió un error al borrar el borrador. Por favor, intenta de nuevo.', 'error', 'Error');
            }
        }

        // showNotification se usa la versión global definida en mve-manual.js
        // Firma: window.showNotification(message, type, title)
    </script>

</x-app-layout>
