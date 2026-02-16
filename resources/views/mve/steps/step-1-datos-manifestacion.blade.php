{{-- Step 1: Datos de Manifestación --}}
<div id="step-1" class="step-content" data-step="1">
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

                {{-- Navegación Stepper --}}
                <div class="flex justify-end mt-6">
                    <button type="button" onclick="nextStep()" class="btn-primary-large">
                        Siguiente
                        <i data-lucide="arrow-right" class="w-5 h-5 ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>{{-- /step-1 --}}
