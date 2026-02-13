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
         data-pedimento="{{ isset($datosExtraidos) ? ($datosExtraidos['datos_manifestacion']['pedimento'] ?? '') : (optional($datosManifestacion)->pedimento ?? '') }}"
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
         data-desde-archivo-m="{{ isset($datosExtraidos) ? 'true' : 'false' }}">
    </div>

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">MVE Manual</span>
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

        <main class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('mve.select-applicant', ['mode' => 'manual']) }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Cambiar Solicitante
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Crear MVE <span class="text-[#003399]">Manual</span>
                </h2>
                <p class="text-slate-500 mt-2">Complete el formulario manualmente con los datos de la manifestación</p>
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

            {{-- 1. Datos de Manifestación --}}
            <div class="mve-section-card">
                <div class="mve-card-header">
                    <div class="mve-card-icon">
                        <i data-lucide="file-text" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="mve-card-title">Datos de Manifestación</h3>
                        <p class="mve-card-description">Información general de la manifestación de valor</p>
                    </div>
                </div>
                    <div class="mve-card-body">
                        <form class="mve-form">
                            <div class="form-group">
                                <label class="form-label">
                                    RFC DEL IMPORTADOR
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="form-input form-input-readonly" value="{{ strtoupper($applicant->applicant_rfc) }}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Registro Federal de Contribuyentes
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="form-input form-input-readonly" value="{{ strtoupper($applicant->applicant_rfc) }}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Nombre o Razón social
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="form-input form-input-readonly" value="{{ strtoupper($applicant->business_name) }}" readonly>
                            </div>

                            {{-- Sección RFC's de consulta --}}
                            <div class="form-divider"></div>
                            <h4 class="form-section-title">RFC'S DE CONSULTA</h4>

                            <div class="form-row">
                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        RFC
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="rfcConsultaInput" class="form-input pr-10 text-uppercase" placeholder="Ingrese el RFC" maxlength="13" minlength="12" oninput="validateRfcInput(this)">
                                        <button type="button" onclick="searchRfcConsulta()" class="absolute right-2 top-1/2 -translate-y-1/2 text-[#003399] hover:text-[#001a4d] transition-colors">
                                            <i data-lucide="search" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        RAZÓN SOCIAL
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="razonSocialConsulta" class="form-input text-uppercase" placeholder="Ingrese la razón social">
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        TIPO DE FIGURA
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select id="tipoFiguraConsulta" class="form-select">
                                        <option value="">Seleccione un valor</option>
                                        @foreach($tiposFigura as $clave => $descripcion)
                                            <option value="{{ $clave }}">{{ $descripcion }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions-inline">
                                <button type="button" onclick="addRfcConsulta()" class="btn-accept">
                                    Aceptar
                                </button>
                                <button type="button" id="btnDeleteRfcConsulta" onclick="deleteSelectedRfcConsulta()" class="btn-delete hidden">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    Eliminar Seleccionados
                                </button>
                            </div>

                            {{-- Tabla de RFCs agregados --}}
                            <div class="table-container">
                                <table class="mve-table">
                                    <thead>
                                        <tr>
                                            <th class="table-checkbox">
                                                <input type="checkbox" id="selectAllRfcConsulta" class="table-checkbox-input" onchange="toggleAllRfcConsulta(this)">
                                            </th>
                                            <th>RFC de consulta</th>
                                            <th>Nombre o Razón Social</th>
                                            <th>Tipo Figura</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rfcConsultaTableBody">
                                        <tr>
                                            <td colspan="4" class="table-empty">
                                                <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                <p class="text-sm text-slate-400 mt-2">No hay RFC's agregados</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Botón Guardar Sección --}}
                            <div class="form-actions-save">
                                <button type="button" onclick="saveDatosManifestacion()" class="btn-save-draft">
                                    <i data-lucide="save" class="w-5 h-5"></i>
                                    GUARDAR DATOS DE MANIFESTACIÓN
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            {{-- 2. Información de Acuse de valor (Cove) --}}
            <div class="mve-section-card">
                    <div class="mve-card-header">
                        <div class="mve-card-icon bg-purple-50 text-purple-600">
                            <i data-lucide="receipt" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="mve-card-title">Información de Acuse de valor (Cove)</h3>
                            <p class="mve-card-description">Datos del Comprobante de Valor Electrónico</p>
                        </div>
                    </div>
                    <div class="mve-card-body">
                        <form class="mve-form">
                            <div class="form-row">
                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        Acuse de Valor (COVE)
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="coveInput" class="form-input" placeholder="Ingrese el COVE" maxlength="20" oninput="validateCoveInput(this)">
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        MÉTODO DE VALORACIÓN ADUANERA
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select id="metodoValoracionCove" class="form-select">
                                        <option value="">Seleccione un valor</option>
                                        @foreach($metodosValoracion as $clave => $descripcion)
                                            <option value="{{ $clave }}">{{ $descripcion }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        # Factura
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="facturaInput" class="form-input" placeholder="Número de factura">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        Fecha expedición
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" id="fechaExpedicionInput" class="form-input">
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        Emisor original
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="emisorOriginalInput" class="form-input" placeholder="Emisor original">
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        Destinatario
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="destinatarioInput" class="form-input" placeholder="Destinatario">
                                </div>
                            </div>

                            <div class="form-actions-inline">
                                <button type="button" onclick="addCoveToTable()" class="btn-add">
                                    AGREGAR
                                </button>
                                <button type="button" id="btnDeleteCove" onclick="deleteSelectedCove()" class="btn-delete hidden">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    ELIMINAR SELECCIONADOS
                                </button>
                                <button type="button" id="btnAddManifestacion" onclick="openManifestacionModal()" class="btn-primary hidden">
                                    <i data-lucide="file-plus" class="w-5 h-5"></i>
                                    AÑADIR INFORMACIÓN DE MVE
                                </button>
                            </div>



                            {{-- Tabla de COVE agregados --}}
                            <div class="table-container">
                                <table class="mve-table">
                                    <thead>
                                        <tr>
                                            <th class="table-checkbox">
                                                <input type="checkbox" id="selectAllCove" class="table-checkbox-input" onchange="toggleAllCove(this)">
                                            </th>
                                            <th>Acuse de Valor (COVE)</th>
                                            <th>Método Valoración</th>
                                            <th># Factura</th>
                                            <th>Fecha expedición</th>
                                            <th>Emisor original</th>
                                            <th>Destinatario</th>
                                        </tr>
                                    </thead>
                                    <tbody id="informacionCoveTableBody">
                                        <tr>
                                            <td colspan="7" class="table-empty">
                                                <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                <p class="text-sm text-slate-400 mt-2">No hay COVE agregados</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            {{-- Botón Guardar Información COVE --}}
                            <div class="form-actions-save mt-6">
                                <button type="button" onclick="saveInformacionCove()" class="btn-save-draft">
                                    <i data-lucide="save" class="w-5 h-5"></i>
                                    Guardar Información COVE
                                </button>
                            </div>

                            {{-- Formulario de Modificación (Oculto por defecto) --}}
                            <div id="modificacionCoveForm" class="hidden border-t border-slate-200 pt-6 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-900">Modificación de datos de acuse de valor (COVE)</h4>
                                            <p class="text-sm text-slate-500">Editar información específica del COVE seleccionado</p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="ocultarModificacionForm()" class="text-slate-400 hover:text-slate-600 transition-colors">
                                        <i data-lucide="x" class="w-6 h-6"></i>
                                    </button>
                                </div>

                                <div class="form-row">
                                    <div class="form-group flex-1">
                                        <label class="form-label">
                                            Acuse de Valor (COVE)
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="modalCoveDisplay" class="form-input bg-slate-100" readonly placeholder="Seleccione un COVE de la tabla">
                                    </div>

                                    <div class="form-group flex-1">
                                        <label class="form-label">
                                            MÉTODO DE VALORACIÓN ADUANERA
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <select id="modalMetodoValoracion" class="form-select">
                                            <option value="">Seleccione un valor</option>
                                            @foreach($metodosValoracion as $clave => $descripcion)
                                                <option value="{{ $clave }}">{{ $descripcion }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group flex-1">
                                        <label class="form-label">
                                            INCOTERM
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <select id="modalIncoterm" class="form-select">
                                            <option value="">Seleccione un valor</option>
                                            @foreach($incoterms as $clave => $descripcion)
                                                <option value="{{ $clave }}">{{ $descripcion }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group flex-1">
                                        <label class="form-label">
                                            ¿EXISTE VINCULACIÓN ENTRE IMPORTADOR Y VENDEDOR/PROVEEDOR?
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex items-center gap-6 mt-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="modalExisteVinculacion" value="1" class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                                <span class="text-sm text-slate-700">Sí</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="modalExisteVinculacion" value="0" class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                                <span class="text-sm text-slate-700">No</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Sección de Pedimentos --}}
                                <div class="border-t border-slate-200 pt-6 mt-6">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="file-text" class="w-5 h-5 text-purple-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-900">INFORMACIÓN DE PEDIMENTOS</h4>
                                            <p class="text-sm text-slate-500">AGREGAR PEDIMENTOS RELACIONADOS AL COVE</p>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                NÚMERO DE PEDIMENTO
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="numeroPedimento" class="form-input" placeholder="Ej: 25 480 4582 1569842" maxlength="23" oninput="formatPedimentoInput(this)">
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                PATENTE
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="patentePedimento" class="form-input" placeholder="Ingrese patente">
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                ADUANA
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="aduanaPedimento" class="form-select">
                                                <option value="">SELECCIONE UNA ADUANA</option>
                                                @foreach($aduanas as $codigo => $nombre)
                                                    <option value="{{ $codigo }}">{{ $nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-actions-inline">
                                        <button type="button" onclick="agregarPedimento()" class="btn-add">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            AGREGAR PEDIMENTO
                                        </button>
                                        <button type="button" id="btnDeletePedimentos" onclick="eliminarPedimentosSeleccionados()" class="btn-delete hidden">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            Eliminar Seleccionados
                                        </button>
                                    </div>

                                    {{-- Tabla de Pedimentos --}}
                                    <div class="table-container">
                                        <table class="mve-table">
                                            <thead>
                                                <tr>
                                                    <th class="table-header table-header-checkbox">
                                                        <input type="checkbox" id="selectAllPedimentos" onclick="toggleAllPedimentos(this)" class="table-checkbox-input">
                                                    </th>
                                                    <th class="table-header">NÚMERO DE PEDIMENTO</th>
                                                    <th class="table-header">PATENTE</th>
                                                    <th class="table-header">ADUANA</th>
                                                </tr>
                                            </thead>
                                            <tbody id="pedimentosTableBody">
                                                <tr>
                                                    <td colspan="4" class="table-empty">
                                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                        <p class="text-sm text-slate-400 mt-2">No hay pedimentos agregados</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                {{-- Sección de Incrementables --}}
                            <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-900">INCREMENTABLES</h4>
                                        <p class="text-sm text-slate-500">INCREMENTABLES CONFORME AL ARTÍCULO 65 DE LA LEY</p>
                                    </div>
                                </div>

                                {{-- Formulario de Incrementables --}}
                                <div class="mve-form">
                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Incrementable
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="incrementableSelect" class="form-select">
                                                <option value="">Seleccione un valor</option>
                                                @foreach($incrementables as $clave => $descripcion)
                                                    <option value="{{ $clave }}">{{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Fecha de la erogación
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaErogacionInput" class="form-input" data-exchange-date data-row="incrementable-form">
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Importe
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" id="importeIncrementableInput" class="form-input" placeholder="0.00" step="0.001" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de moneda
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="tipoMonedaIncrementableSelect" class="form-select" data-exchange-currency data-row="incrementable-form">
                                                <option value="">Seleccione un valor</option>
                                                @foreach(VucemCatalogs::$monedas as $codigo => $descripcion)
                                                    <option value="{{ $codigo }}">{{ $codigo }} - {{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de cambio
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="number" id="tipoCambioIncrementableInput" class="form-input pr-20" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999" oninput="validateExchangeRateInput(this)" data-exchange-rate data-row="incrementable-form">
                                                <button type="button" class="absolute right-1 top-1 bottom-1 px-3 py-1 text-xs bg-blue-50 text-blue-600 rounded border border-blue-200 hover:bg-blue-100 transition-colors" style="display: none;" data-exchange-auto data-row="incrementable-form">
                                                    Usar automático
                                                </button>
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500" data-exchange-status data-row="incrementable-form"></div>
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                ¿Está a cargo del importador?
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <div class="flex items-center gap-6 mt-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" name="aCargoImportador" value="1" class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                                    <span class="text-sm text-slate-700">Sí</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" name="aCargoImportador" value="0" class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                                    <span class="text-sm text-slate-700">No</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions-inline">
                                        <button type="button" onclick="addIncrementableToTable()" class="btn-add">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            AGREGAR
                                        </button>
                                        <button type="button" id="btnDeleteIncrementables" onclick="deleteSelectedIncrementables()" class="btn-delete hidden">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            ELIMINAR SELECCIONADOS
                                        </button>
                                    </div>

                                    {{-- Tabla de Incrementables agregados --}}
                                    <div class="table-container">
                                        <table class="mve-table">
                                            <thead>
                                                <tr>
                                                    <th class="table-checkbox">
                                                        <input type="checkbox" id="selectAllIncrementables" class="table-checkbox-input" onchange="toggleAllIncrementables(this)">
                                                    </th>
                                                    <th>Incrementable</th>
                                                    <th>Fecha Erogación</th>
                                                    <th>Importe</th>
                                                    <th>Moneda</th>
                                                    <th>Tipo Cambio</th>
                                                    <th>A Cargo Importador</th>
                                                </tr>
                                            </thead>
                                            <tbody id="incrementablesTableBody">
                                                <tr>
                                                    <td colspan="7" class="table-empty">
                                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                        <p class="text-sm text-slate-400 mt-2">No hay incrementables agregados</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Sección de Decrementables --}}
                            <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-900">DECREMENTABLES</h4>
                                        <p class="text-sm text-slate-500">INFORMACIÓN QUE NO INTEGRA EL VALOR DE TRANSACCIÓN CONFORME EL ARTÍCULO 66 DE LA LEY ADUANERA (DECREMENTABLES) (SE CONSIDERA QUE SE DISTINGUEN DEL PRECIO PAGADO LAS CANTIDADES QUE SE MENCIONAN, SE DETALLAN O ESPECIFICAN SEPARADAMENTE DEL PRECIO PAGADO EN EL COMPROBANTE FISCAL DIGITAL O EN EL DOCUMENTO EQUIVALENTE)</p>
                                    </div>
                                </div>

                                {{-- Formulario de Decrementables --}}
                                <div class="mve-form">
                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Decrementable
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="decrementableSelect" class="form-select">
                                                <option value="">Seleccione un valor</option>
                                                @foreach($decrementables as $clave => $descripcion)
                                                    <option value="{{ $clave }}">{{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Fecha de su erogación
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaErogacionDecrementableInput" class="form-input" data-exchange-date data-row="decrementable-form">
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Importe
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" id="importeDecrementableInput" class="form-input" placeholder="0.00" step="0.001" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de moneda
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="tipoMonedaDecrementableSelect" class="form-select" data-exchange-currency data-row="decrementable-form">
                                                <option value="">Seleccione un valor</option>
                                                @foreach(VucemCatalogs::$monedas as $codigo => $descripcion)
                                                    <option value="{{ $codigo }}">{{ $codigo }} - {{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de cambio
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="number" id="tipoCambioDecrementableInput" class="form-input pr-20" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999" oninput="validateExchangeRateInput(this)" data-exchange-rate data-row="decrementable-form">
                                                <button type="button" class="absolute right-1 top-1 bottom-1 px-3 py-1 text-xs bg-blue-50 text-blue-600 rounded border border-blue-200 hover:bg-blue-100 transition-colors" style="display: none;" data-exchange-auto data-row="decrementable-form">
                                                    Usar automático
                                                </button>
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500" data-exchange-status data-row="decrementable-form"></div>
                                        </div>

                                        <div class="form-group flex-1">
                                            <!-- Espacio vacío para mantener la estructura -->
                                        </div>
                                    </div>

                                    <div class="form-actions-inline">
                                        <button type="button" onclick="addDecrementableToTable()" class="btn-add">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            AGREGAR
                                        </button>
                                        <button type="button" id="btnDeleteDecrementables" onclick="deleteSelectedDecrementables()" class="btn-delete hidden">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            ELIMINAR SELECCIONADOS
                                        </button>
                                    </div>

                                    {{-- Tabla de Decrementables agregados --}}
                                    <div class="table-container">
                                        <table class="mve-table">
                                            <thead>
                                                <tr>
                                                    <th class="table-checkbox">
                                                        <input type="checkbox" id="selectAllDecrementables" class="table-checkbox-input" onchange="toggleAllDecrementables(this)">
                                                    </th>
                                                    <th>Decrementable</th>
                                                    <th>Fecha de su erogación</th>
                                                    <th>Importe</th>
                                                    <th>Tipo mon</th>
                                                </tr>
                                            </thead>
                                            <tbody id="decrementablesTableBody">
                                                <tr>
                                                    <td colspan="5" class="table-empty">
                                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                        <p class="text-sm text-slate-400 mt-2">NO HAY DECREMENTABLES AGREGADOS</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Sección de Precio Pagado --}}
                            <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-900">PRECIO PAGADO</h4>
                                    </div>
                                </div>

                                {{-- Formulario de Precio Pagado --}}
                                <div class="mve-form">
                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Fecha
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaPrecioPagadoInput" class="form-input" data-exchange-date data-row="precio-pagado-form">
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Importe
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" id="importePrecioPagadoInput" class="form-input" placeholder="0.00" step="0.001" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Forma de Pago
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="formaPagoPrecioPagadoSelect" class="form-select">
                                                <option value="">Seleccione un valor</option>
                                                @foreach($formasPago as $clave => $descripcion)
                                                    <option value="{{ $clave }}">{{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de moneda
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="tipoMonedaPrecioPagadoSelect" class="form-select" data-exchange-currency data-row="precio-pagado-form">
                                                <option value="">Seleccione un valor</option>
                                                @foreach(VucemCatalogs::$monedas as $codigo => $descripcion)
                                                    <option value="{{ $codigo }}">{{ $codigo }} - {{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de cambio
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="number" id="tipoCambioPrecioPagadoInput" class="form-input pr-20" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999" oninput="validateExchangeRateInput(this)" data-exchange-rate data-row="precio-pagado-form">
                                                <button type="button" class="absolute right-1 top-1 bottom-1 px-3 py-1 text-xs bg-blue-50 text-blue-600 rounded border border-blue-200 hover:bg-blue-100 transition-colors" style="display: none;" data-exchange-auto data-row="precio-pagado-form">
                                                    Usar automático
                                                </button>
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500" data-exchange-status data-row="precio-pagado-form"></div>
                                        </div>

                                        <div class="form-group flex-1">
                                            <!-- Espacio vacío para mantener la estructura -->
                                        </div>
                                    </div>

                                    <div class="form-actions-inline">
                                        <button type="button" onclick="addPrecioPagadoToTable()" class="btn-add">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            Agregar
                                        </button>
                                        <button type="button" id="btnDeletePrecioPagado" onclick="deleteSelectedPrecioPagado()" class="btn-delete hidden">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            Eliminar Seleccionados
                                        </button>
                                    </div>

                                    {{-- Tabla de Precio Pagado agregados --}}
                                    <div class="table-container">
                                        <table class="mve-table">
                                            <thead>
                                                <tr>
                                                    <th class="table-checkbox">
                                                        <input type="checkbox" id="selectAllPrecioPagado" class="table-checkbox-input" onchange="toggleAllPrecioPagado(this)">
                                                    </th>
                                                    <th>Fecha Pago</th>
                                                    <th>Importe</th>
                                                    <th>Forma de pago</th>
                                                    <th>Tipo moneda</th>
                                                    <th>Tipo cam</th>
                                                </tr>
                                            </thead>
                                            <tbody id="precioPagadoTableBody">
                                                <tr>
                                                    <td colspan="6" class="table-empty">
                                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                        <p class="text-sm text-slate-400 mt-2">No hay conceptos de precio pagado agregados</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Sección de Precio por Pagar --}}
                            <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-900">PRECIO POR PAGAR</h4>
                                    </div>
                                </div>

                                {{-- Formulario de Precio por Pagar --}}
                                <div class="mve-form">
                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Fecha
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaPrecioPorPagarInput" class="form-input" data-exchange-date data-row="precio-por-pagar-form">
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Importe
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" id="importePrecioPorPagarInput" class="form-input" placeholder="0.00" step="0.001" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Forma de Pago
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="formaPagoPrecioPorPagarSelect" class="form-select">
                                                <option value="">Seleccione un valor</option>
                                                @foreach($formasPago as $clave => $descripcion)
                                                    <option value="{{ $clave }}">{{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de moneda
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="tipoMonedaPrecioPorPagarSelect" class="form-select" data-exchange-currency data-row="precio-por-pagar-form">
                                                <option value="">Seleccione un valor</option>
                                                @foreach(VucemCatalogs::$monedas as $codigo => $descripcion)
                                                    <option value="{{ $codigo }}">{{ $codigo }} - {{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Tipo de cambio
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="number" id="tipoCambioPrecioPorPagarInput" class="form-input pr-20" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999" oninput="validateExchangeRateInput(this)" data-exchange-rate data-row="precio-por-pagar-form">
                                                <button type="button" class="absolute right-1 top-1 bottom-1 px-3 py-1 text-xs bg-blue-50 text-blue-600 rounded border border-blue-200 hover:bg-blue-100 transition-colors" style="display: none;" data-exchange-auto data-row="precio-por-pagar-form">
                                                    Usar automático
                                                </button>
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500" data-exchange-status data-row="precio-por-pagar-form"></div>
                                        </div>

                                        <div class="form-group flex-1">
                                            <!-- Espacio vacío para mantener la estructura -->
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            Momento(s) o situación(es) cuando se realizará el pago
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="momentoSituacionInput" class="form-input" rows="3" placeholder="Describa el momento o situación cuando se realizará el pago"></textarea>
                                    </div>

                                    <div class="form-actions-inline">
                                        <button type="button" onclick="addPrecioPorPagarToTable()" class="btn-add">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            Agregar
                                        </button>
                                        <button type="button" id="btnDeletePrecioPorPagar" onclick="deleteSelectedPrecioPorPagar()" class="btn-delete hidden">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            Eliminar Seleccionados
                                        </button>
                                    </div>

                                    {{-- Tabla de Precio por Pagar agregados --}}
                                    <div class="table-container">
                                        <table class="mve-table">
                                            <thead>
                                                <tr>
                                                    <th class="table-checkbox">
                                                        <input type="checkbox" id="selectAllPrecioPorPagar" class="table-checkbox-input" onchange="toggleAllPrecioPorPagar(this)">
                                                    </th>
                                                    <th>Fecha Pago</th>
                                                    <th>Importe</th>
                                                    <th>Forma de pago</th>
                                                    <th>Momento(s) o situación(es) cuando se realizará el pago</th>
                                                    <th>Tipo mon</th>
                                                </tr>
                                            </thead>
                                            <tbody id="precioPorPagarTableBody">
                                                <tr>
                                                    <td colspan="6" class="table-empty">
                                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                        <p class="text-sm text-slate-400 mt-2">NO HAY CONCEPTOS DE PRECIO POR PAGAR AGREGADOS</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Sección de Compenso Pago --}}
                            <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-slate-900">COMPENSO PAGO</h4>
                                    </div>
                                </div>

                                {{-- Formulario de Compenso Pago --}}
                                <div class="mve-form">
                                    <div class="form-row">
                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Fecha
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaCompensoPagoInput" class="form-input">
                                        </div>

                                        <div class="form-group flex-1">
                                            <label class="form-label">
                                                Forma de Pago
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select id="formaPagoCompensoPagoSelect" class="form-select">
                                                <option value="">Seleccione un valor</option>
                                                @foreach($formasPago as $clave => $descripcion)
                                                    <option value="{{ $clave }}">{{ $descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            Motivo por lo que se realizó
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="motivoCompensoPagoInput" class="form-input" rows="3" placeholder="Describa el motivo por lo que se realizó"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            Prestación de la mercancía
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="prestacionMercanciaInput" class="form-input" rows="3" placeholder="Describa la prestación de la mercancía"></textarea>
                                    </div>

                                    <div class="form-actions-inline">
                                        <button type="button" onclick="addCompensoPagoToTable()" class="btn-add">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            Agregar
                                        </button>
                                        <button type="button" id="btnDeleteCompensoPago" onclick="deleteSelectedCompensoPago()" class="btn-delete hidden">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            Eliminar Seleccionados
                                        </button>
                                    </div>

                                    {{-- Tabla de Compenso Pago agregados --}}
                                    <div class="table-container">
                                        <table class="mve-table">
                                            <thead>
                                                <tr>
                                                    <th class="table-checkbox">
                                                        <input type="checkbox" id="selectAllCompensoPago" class="table-checkbox-input" onchange="toggleAllCompensoPago(this)">
                                                    </th>
                                                    <th>Fecha Pago</th>
                                                    <th>Motivo</th>
                                                    <th>Prestación de la mercancía</th>
                                                    <th>Forma de pago</th>
                                                </tr>
                                            </thead>
                                            <tbody id="compensoPagoTableBody">
                                                <tr>
                                                    <td colspan="5" class="table-empty">
                                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                        <p class="text-sm text-slate-400 mt-2">NO HAY CONCEPTOS DE COMPENSO PAGO AGREGADOS</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                                {{-- Botones de acción --}}
                                <div class="form-actions-save">
                                        <button type="button" onclick="guardarModificacionesCove()" class="btn-save-draft">
                                            <i data-lucide="check" class="w-5 h-5"></i>
                                            Guardar Modificaciones
                                        </button>
                                        <button type="button" onclick="ocultarModificacionForm()" class="btn-secondary">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                            Cancelar
                                        </button>
                                </div>
                            </div>

                            
                        </form>
                    </div>
                </div>

            {{-- 3. Valor en Aduana --}}
            <div class="mve-section-card">
                    <div class="mve-card-header">
                        <div class="mve-card-icon bg-emerald-50 text-emerald-600">
                            <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="mve-card-title">VALOR EN ADUANA</h3>
                            <p class="mve-card-description">INFORMACIÓN DE VALORES Y MONTOS ADUANALES</p>
                        </div>
                    </div>
                    <div class="mve-card-body">
                        <form class="mve-form">
                            <div class="form-row">
                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        Importe total del precio pagado (Sumatoria de los conceptos y deberán ser declarados en M N)
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="totalPrecioPagado" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        IMPORTE TOTAL DEL PRECIO POR PAGAR (SUMATORIA DE LOS CONCEPTOS Y DEBERÁN SER DECLARADOS EN M N)
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="totalPrecioPorPagar" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        Importe total de incrementables (Sumatoria de los conceptos y deberán ser declarados en M N)
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="totalIncrementables" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label">
                                        Importe total de decrementables (Sumatoria de los conceptos y deberán ser declarados en M N)
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="totalDecrementables" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    TOTAL DEL VALOR EN ADUANA (SUMATORIA DE LOS CONCEPTOS Y DEBERÁN SER DECLARADOS EN M N)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="totalValorAduana" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                            </div>

                            <div class="form-note">
                                <i data-lucide="info" class="w-4 h-4"></i>
                                <span>* Campos obligatorios</span>
                            </div>

                            {{-- Botón Guardar Sección --}}
                            <div class="form-actions-save">
                                <button type="button" onclick="saveValorAduana()" class="btn-save-draft">
                                    <i data-lucide="save" class="w-5 h-5"></i>
                                    GUARDAR VALOR EN ADUANA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            {{-- 4. Documentos (eDocument) - Digitalización Integrada --}}
            <div class="mve-section-card">
                    <div class="mve-card-header">
                        <div class="mve-card-icon bg-amber-50 text-amber-600">
                            <i data-lucide="paperclip" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="mve-card-title">DOCUMENTOS (eDocument)</h3>
                            <p class="mve-card-description">Digitaliza y asocia documentos a VUCEM para el envío de la manifestación</p>
                        </div>
                    </div>
                    <div class="mve-card-body">
                        <form class="mve-form" id="formDigitalizacion" enctype="multipart/form-data">

                            {{-- Nombre del documento --}}
                            <div class="form-group">
                                <label class="form-label">
                                    NOMBRE DEL DOCUMENTO
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="form-input" id="documentName" placeholder="Ej. Factura Comercial 2026-001" maxlength="45">
                                <p class="text-xs text-slate-400 mt-1">Máximo 45 caracteres. Este nombre se registrará en VUCEM.</p>
                            </div>

                            {{-- Tipo de documento VUCEM --}}
                            <div class="form-group">
                                <label class="form-label">
                                    TIPO DE DOCUMENTO VUCEM
                                    <span class="text-red-500">*</span>
                                </label>
                                <select class="form-input" id="documentTypeSelect">
                                    <option value="">Seleccione tipo de documento...</option>
                                    @foreach($tiposDocumento as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Carga de archivo PDF --}}
                            <div class="form-group">
                                <label class="form-label">
                                    ARCHIVO PDF
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="file" class="form-input" id="pdfFileInput" accept=".pdf">
                                <p class="text-xs text-slate-400 mt-1">Solo archivos PDF (máx. 20MB). Se convertirá automáticamente al formato VUCEM si es necesario.</p>
                            </div>

                            {{-- Status de validación del PDF --}}
                            <div id="pdfValidationStatus" class="hidden mb-4">
                                {{-- Se llena dinámicamente por JS --}}
                            </div>

                            {{-- RFC Consulta (opcional) --}}
                            <div class="form-group">
                                <label class="form-label">
                                    RFC DE CONSULTA (OPCIONAL)
                                </label>
                                <input type="text" class="form-input" id="rfcConsultaDigit" placeholder="Solo si es Agente Aduanal" maxlength="13">
                                <p class="text-xs text-slate-400 mt-1">Déjelo vacío si el trámite es propio. Solo para agentes aduanales.</p>
                            </div>

                            {{-- Sección de firma manual (solo si NO tiene credenciales almacenadas) --}}
                            <div id="digitFirmaManualSection" class="{{ $applicant->hasVucemCredentials() && $applicant->hasWebserviceKey() ? 'hidden' : '' }}">
                                <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 border-dashed mb-4">
                                    <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center">
                                        <i data-lucide="key" class="w-4 h-4 mr-2 text-amber-600"></i>
                                        Firma Electrónica para Digitalización
                                    </h4>
                                    <p class="text-xs text-slate-500 mb-4">Para digitalizar y enviar el documento a VUCEM, se requiere firmar con su e.firma (FIEL).</p>

                                    @if(!$applicant->hasWebserviceKey())
                                    <div class="form-group">
                                        <label class="form-label text-xs">Contraseña Web Service VUCEM</label>
                                        <input type="password" class="form-input" id="digitClaveWS" placeholder="Contraseña del portal VUCEM">
                                    </div>
                                    @endif

                                    @if(!$applicant->hasVucemCredentials())
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="form-label text-xs">Certificado (.cer)</label>
                                            <input type="file" class="form-input text-sm" id="digitCertFile" accept=".cer,.crt,.pem">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label text-xs">Llave Privada (.key)</label>
                                            <input type="file" class="form-input text-sm" id="digitKeyFile" accept=".key,.pem">
                                        </div>
                                        <div class="form-group md:col-span-2">
                                            <label class="form-label text-xs">Contraseña de la Llave Privada</label>
                                            <input type="password" class="form-input" id="digitKeyPassword" placeholder="••••••••">
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Badge de credenciales detectadas --}}
                            @if($applicant->hasVucemCredentials() && $applicant->hasWebserviceKey())
                            <div class="rounded-xl border border-green-200 bg-green-50 p-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-green-800">Firma electrónica configurada</p>
                                        <p class="text-xs text-green-600">Los sellos y clave WS se usarán automáticamente para firmar y digitalizar.</p>
                                    </div>
                                </div>
                            </div>
                            @elseif($applicant->hasVucemCredentials() || $applicant->hasWebserviceKey())
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-amber-800">Credenciales parciales</p>
                                        <p class="text-xs text-amber-600">Complete los campos manuales faltantes arriba para poder digitalizar.</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Botón Digitalizar --}}
                            <div class="form-actions-inline">
                                <button type="button" id="btnDigitalizar" onclick="digitalizarDocumento()" class="btn-add flex items-center gap-2">
                                    <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                    Digitalizar y Enviar a VUCEM
                                </button>
                            </div>

                            <hr class="my-6 border-slate-200">

                            {{-- Tabla de documentos digitalizados --}}
                            <h4 class="text-sm font-bold text-slate-700 mb-3 flex items-center">
                                <i data-lucide="list" class="w-4 h-4 mr-2 text-slate-500"></i>
                                Documentos Asociados
                            </h4>
                            <div class="table-container">
                                <table class="mve-table">
                                    <thead>
                                        <tr>
                                            <th>TIPO DE DOCUMENTO</th>
                                            <th>FOLIO eDocument</th>
                                            <th>NOMBRE</th>
                                            <th>FECHA</th>
                                            <th>ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody id="edocumentsTableBody">
                                        <tr>
                                            <td colspan="5" class="table-empty">
                                                <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                                <p class="text-sm text-slate-400 mt-2">NO HAY DOCUMENTOS ASOCIADOS</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Agregar folio manualmente (fallback) --}}
                            <details class="mt-4">
                                <summary class="text-xs text-slate-400 cursor-pointer hover:text-slate-600">
                                    ¿Ya tiene un folio eDocument? Agregar manualmente
                                </summary>
                                <div class="mt-3 p-4 bg-slate-50 rounded-lg border border-slate-200">
                                    <div class="form-row gap-4">
                                        <div class="form-group flex-1">
                                            <label class="form-label text-xs">Tipo de Documento</label>
                                            <input type="text" class="form-input" id="documentType" placeholder="Ej. Factura">
                                        </div>
                                        <div class="form-group flex-1">
                                            <label class="form-label text-xs">Folio eDocument</label>
                                            <input type="text" class="form-input" id="edocumentFolio" list="edocumentSuggestions" placeholder="Folio">
                                        </div>
                                    </div>
                                    <datalist id="edocumentSuggestions">
                                        @foreach($edocumentSuggestions ?? [] as $folioSuggestion)
                                            <option value="{{ $folioSuggestion }}"></option>
                                        @endforeach
                                    </datalist>
                                    <button type="button" id="btnAddEdocument" class="btn-add mt-2" onclick="addEdocument()">
                                        Agregar folio manual
                                    </button>
                                </div>
                            </details>

                            {{-- Botón Guardar Sección --}}
                            <div class="form-actions-save">
                                <button type="button" onclick="saveDocumentos()" class="btn-save-draft">
                                    <i data-lucide="save" class="w-5 h-5"></i>
                                    Guardar Documentos
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            {{-- Botones de Acción --}}
            <div class="form-actions-bottom">
                <div class="flex gap-4">
                    <a href="{{ route('dashboard') }}" class="btn-secondary-large">
                        <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                        Volver al Dashboard
                    </a>
                    
                    <button type="button" onclick="confirmarBorrarBorrador()" class="btn-danger-large">
                        <i data-lucide="trash-2" class="w-5 h-5 mr-2"></i>
                        Borrar Borrador
                    </button>

                </div>
                
                <button type="button" id="btnGuardarManifestacion" onclick="guardarManifestacionCompleta()" class="btn-primary-large" disabled>
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                    Guardar Manifestación
                </button>
                {{-- Debug Cache Tools - Solo visible en desarrollo --}}
                <div class="text-xs text-slate-400 mt-2 space-x-2 hidden" style="font-size: 10px;">
                    <a href="javascript:void(0)" onclick="showExchangeRateCacheInfo()" class="hover:text-slate-600">Cache Info</a>
                    <span>|</span>
                    <a href="javascript:void(0)" onclick="clearExchangeRateCache()" class="hover:text-red-600">Limpiar Cache</a>
                </div>
            </div>
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
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-blue-600 to-blue-700 text-white">
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
                    showNotification('success', 'Borrador Eliminado', 'El borrador se ha eliminado correctamente.');
                    
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
                showNotification('error', 'Error', 'Ocurrió un error al borrar el borrador. Por favor, intenta de nuevo.');
            }
        }

        // Función auxiliar para mostrar notificaciones (si no existe ya)
        function showNotification(type, title, message) {
            const modal = document.getElementById('notificationModal');
            const titleEl = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');
            const iconEl = document.querySelector('#notificationIcon i');
            const headerEl = document.getElementById('notificationHeader');
            
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            // Configurar estilos según el tipo
            if (type === 'success') {
                iconEl.setAttribute('data-lucide', 'check-circle');
                headerEl.className = 'px-6 py-4 border-b border-slate-200 flex items-center gap-3 bg-green-50';
            } else if (type === 'error') {
                iconEl.setAttribute('data-lucide', 'x-circle');
                headerEl.className = 'px-6 py-4 border-b border-slate-200 flex items-center gap-3 bg-red-50';
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Re-inicializar lucide para los nuevos iconos
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    </script>

</x-app-layout>
