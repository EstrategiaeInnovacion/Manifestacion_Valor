{{-- Step 2: Información COVE --}}
<div id="step-2" class="step-content hidden" data-step="2">
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
                                <th class="w-16 text-center">Editar</th>
                            </tr>
                        </thead>
                        <tbody id="informacionCoveTableBody">
                            <tr>
                                <td colspan="8" class="table-empty">
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900">INCREMENTABLES</h4>
                            <p class="text-sm text-slate-500">INCREMENTABLES CONFORME AL ARTÍCULO 65 DE LA LEY</p>
                        </div>
                        <button type="button" onclick="toggleSection('incrementables')" id="toggleIncrementablesBtn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4" id="toggleIncrementablesIcon"></i>
                            <span id="toggleIncrementablesText">Agregar Incrementable</span>
                        </button>
                    </div>

                    {{-- Formulario de Incrementables --}}
                    <div id="incrementablesContent" class="hidden mt-4">
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
                                <input type="date" id="fechaErogacionInput" class="form-input">
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
                                <select id="tipoMonedaIncrementableSelect" class="form-select">
                                    <option value="">Seleccione un valor</option>
                                    @foreach(App\Constants\VucemCatalogs::$monedas as $codigo => $descripcion)
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
                                <input type="number" id="tipoCambioIncrementableInput" class="form-input" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999">
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900">DECREMENTABLES</h4>
                            <p class="text-sm text-slate-500">INFORMACIÓN QUE NO INTEGRA EL VALOR DE TRANSACCIÓN CONFORME EL ARTÍCULO 66 DE LA LEY ADUANERA (DECREMENTABLES) (SE CONSIDERA QUE SE DISTINGUEN DEL PRECIO PAGADO LAS CANTIDADES QUE SE MENCIONAN, SE DETALLAN O ESPECIFICAN SEPARADAMENTE DEL PRECIO PAGADO EN EL COMPROBANTE FISCAL DIGITAL O EN EL DOCUMENTO EQUIVALENTE)</p>
                        </div>
                        <button type="button" onclick="toggleSection('decrementables')" id="toggleDecrementablesBtn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors flex-shrink-0">
                            <i data-lucide="plus" class="w-4 h-4" id="toggleDecrementablesIcon"></i>
                            <span id="toggleDecrementablesText">Agregar Decrementable</span>
                        </button>
                    </div>

                    {{-- Formulario de Decrementables --}}
                    <div id="decrementablesContent" class="hidden mt-4">
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
                                <input type="date" id="fechaErogacionDecrementableInput" class="form-input">
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
                                <select id="tipoMonedaDecrementableSelect" class="form-select">
                                    <option value="">Seleccione un valor</option>
                                    @foreach(App\Constants\VucemCatalogs::$monedas as $codigo => $descripcion)
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
                                <input type="number" id="tipoCambioDecrementableInput" class="form-input" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999">
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900">PRECIO PAGADO</h4>
                        </div>
                        <button type="button" onclick="toggleSection('precioPagado')" id="togglePrecioPagadoBtn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4" id="togglePrecioPagadoIcon"></i>
                            <span id="togglePrecioPagadoText">Agregar Precio Pagado</span>
                        </button>
                    </div>

                    {{-- Formulario de Precio Pagado --}}
                    <div id="precioPagadoContent" class="hidden mt-4">
                        <div class="form-row">
                            <div class="form-group flex-1">
                                <label class="form-label">
                                    Fecha
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="fechaPrecioPagadoInput" class="form-input">
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
                                <select id="tipoMonedaPrecioPagadoSelect" class="form-select">
                                    <option value="">Seleccione un valor</option>
                                    @foreach(App\Constants\VucemCatalogs::$monedas as $codigo => $descripcion)
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
                                <input type="number" id="tipoCambioPrecioPagadoInput" class="form-input" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999">
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900">PRECIO POR PAGAR</h4>
                        </div>
                        <button type="button" onclick="toggleSection('precioPorPagar')" id="togglePrecioPorPagarBtn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4" id="togglePrecioPorPagarIcon"></i>
                            <span id="togglePrecioPorPagarText">Agregar Precio por Pagar</span>
                        </button>
                    </div>

                    {{-- Formulario de Precio por Pagar --}}
                    <div id="precioPorPagarContent" class="hidden mt-4">
                        <div class="form-row">
                            <div class="form-group flex-1">
                                <label class="form-label">
                                    Fecha
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="fechaPrecioPorPagarInput" class="form-input">
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
                                <select id="tipoMonedaPrecioPorPagarSelect" class="form-select">
                                    <option value="">Seleccione un valor</option>
                                    @foreach(App\Constants\VucemCatalogs::$monedas as $codigo => $descripcion)
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
                                <input type="number" id="tipoCambioPrecioPorPagarInput" class="form-input" placeholder="0.0000" step="0.0001" min="0" max="9999999999999.9999">
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900">COMPENSO PAGO</h4>
                        </div>
                        <button type="button" onclick="toggleSection('compensoPago')" id="toggleCompensoPagoBtn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4" id="toggleCompensoPagoIcon"></i>
                            <span id="toggleCompensoPagoText">Agregar Compenso Pago</span>
                        </button>
                    </div>

                    {{-- Formulario de Compenso Pago --}}
                    <div id="compensoPagoContent" class="hidden mt-4">
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

{{-- Navegación Stepper --}}
<div class="flex justify-between mt-6">
    <button type="button" onclick="prevStep()" class="btn-secondary-large">
        <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
        Anterior
    </button>
    <button type="button" onclick="nextStep()" class="btn-primary-large">
        Siguiente
        <i data-lucide="arrow-right" class="w-5 h-5 ml-2"></i>
    </button>
</div>
</div>{{-- /step-2 --}}
