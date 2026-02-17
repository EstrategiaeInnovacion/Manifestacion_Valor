{{-- Step 4: Documentos (eDocument) --}}
<div id="step-4" class="step-content hidden" data-step="4">
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

                {{-- Vista previa del PDF --}}
                <div id="pdfPreviewContainer" class="hidden mb-4">
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2 bg-slate-50 border-b border-slate-200">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-slate-500"></i>
                                <span class="text-sm font-medium text-slate-700" id="pdfPreviewFilename">Vista previa</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btnTogglePreview" onclick="togglePdfPreview()" class="text-xs text-slate-500 hover:text-slate-700 flex items-center gap-1 transition-colors">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span id="togglePreviewText">Ocultar</span>
                                </button>
                                <button type="button" onclick="closePdfPreview()" class="text-xs text-red-400 hover:text-red-600 flex items-center gap-1 transition-colors ml-2">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    Cerrar
                                </button>
                            </div>
                        </div>
                        <div id="pdfPreviewFrame" class="relative" style="height: 500px;">
                            <iframe id="pdfPreviewIframe" class="w-full h-full border-0" title="Vista previa del PDF"></iframe>
                        </div>
                    </div>
                </div>

                {{-- RFC Consulta - No se muestra, se asigna automáticamente --}}
                <input type="hidden" id="rfcConsultaDigit" value="">

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

                {{-- Navegación Stepper --}}
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep()" class="btn-secondary-large">
                        <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                        Anterior
                    </button>
                    <button type="button" onclick="guardarYVistaPrevia()" class="btn-primary-large bg-green-600 hover:bg-green-700">
                        <i data-lucide="eye" class="w-5 h-5 mr-2"></i>
                        Guardar y Vista Previa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>{{-- /step-4 --}}
