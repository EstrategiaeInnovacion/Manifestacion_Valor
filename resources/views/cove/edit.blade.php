<x-app-layout>
    <x-slot name="title">Editar Borrador de COVE</x-slot>

    <div class="min-h-screen bg-[#F8FAFC] py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-10">
                <a href="{{ route('coves.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Listado
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Revisar & Editar <span class="text-[#003399]">COVE</span>
                </h2>
                <p class="text-slate-500 mt-2">Valida la información del COVE contra los documentos físicos antes de firmar.</p>
            </div>

            @if($cove->status === 'error')
                <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-md text-red-700 flex items-start gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <h4 class="font-bold text-sm">Error de transmisión VUCEM:</h4>
                        <p class="text-sm mt-1 font-mono text-xs">{{ $cove->error_mensaje }}</p>
                    </div>
                </div>
            @endif

            <form action="{{ route('coves.update', $cove) }}" method="POST" class="space-y-8">
                @csrf
                @method('PUT')

                @php
                    $json = $cove->cove_json;
                    $factura = $json['facturas'][0] ?? [];
                    $emisor = $factura['emisor'] ?? [];
                    $dest = $factura['destinatario'] ?? [];
                    $mercancias = $factura['mercancias'] ?? [];
                @endphp

                {{-- Sección 1: Datos del Acuse --}}
                <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <h3 class="text-lg font-bold text-[#001a4d] border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-5 h-5 text-[#003399]"></i>
                        Datos Generales del Acuse / Factura
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tipo Operación</label>
                            <input type="text" name="cove_json[tipoOperacion]" value="{{ $json['tipoOperacion'] ?? 'IMPORTACION' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Relación Facturas</label>
                            <input type="text" name="cove_json[relacionFacturas]" value="{{ $json['relacionFacturas'] ?? 'SIN RELACION DE FACTURAS' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. de Factura</label>
                            <input type="text" name="cove_json[facturas][0][numeroFactura]" value="{{ $factura['numeroFactura'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">e-Document Original (Solo para Adendas)</label>
                            <input type="text" name="cove_json[eDocumentOriginal]" value="{{ $json['eDocumentOriginal'] ?? '' }}" placeholder="Ej. COVE2683ZWZP1" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tipo Figura</label>
                            <input type="text" name="cove_json[tipoFigura]" value="{{ $json['tipoFigura'] ?? 'IMPORTADOR' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">RFC de Consulta (Agente Aduanal)</label>
                            <input type="text" name="cove_json[rfcsConsulta][0]" value="{{ $json['rfcsConsulta'][0] ?? 'RIRV691116P84' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Subdivisión</label>
                            <select name="cove_json[facturas][0][subdivision]" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                                <option value="0" {{ ($factura['subdivision'] ?? 0) == 0 ? 'selected' : '' }}>Sin subdivisión (0)</option>
                                <option value="1" {{ ($factura['subdivision'] ?? 0) == 1 ? 'selected' : '' }}>Con subdivisión (1)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Certificado de Origen</label>
                            <select name="cove_json[facturas][0][certificadoOrigen]" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                                <option value="0" {{ ($factura['certificadoOrigen'] ?? 0) == 0 ? 'selected' : '' }}>No funge como Certificado de Origen (0)</option>
                                <option value="1" {{ ($factura['certificadoOrigen'] ?? 0) == 1 ? 'selected' : '' }}>Sí funge como Certificado de Origen (1)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. de Exportador Confiable (Opcional)</label>
                            <input type="text" name="cove_json[facturas][0][numeroExportadorConfiable]" value="{{ $factura['numeroExportadorConfiable'] ?? '' }}" pattern="[A-Za-z0-9\-]*" title="Solo se permiten letras, números y guiones" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Ej. A-12345">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Correo de Notificación (VUCEM)</label>
                            <input type="email" name="cove_json[correoElectronico]" value="{{ $json['correoElectronico'] ?? $cove->applicant->applicant_email ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="ejemplo@correo.com">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Fecha Expedición (AAAA-MM-DD)</label>
                            <input type="date" name="cove_json[fechaExpedicion]" value="{{ $json['fechaExpedicion'] ?? date('Y-m-d') }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Patente Aduanal</label>
                            <input type="text" name="cove_json[patentesAduanales][0]" value="{{ $json['patentesAduanales'][0] ?? '1628' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Observaciones</label>
                            <input type="text" name="cove_json[observaciones]" value="{{ $json['observaciones'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                    </div>
                </div>

                {{-- Sección 2: Proveedor (Emisor) --}}
                <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <h3 class="text-lg font-bold text-[#001a4d] border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i data-lucide="truck" class="w-5 h-5 text-[#003399]"></i>
                        Datos Generales del Proveedor (Emisor)
                    </h3>
                           <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tipo Identificador (0=TAX_ID, 1=RFC, 3=SIN TAX ID)</label>
                            <input type="number" name="cove_json[facturas][0][emisor][tipoIdentificador]" value="{{ $emisor['tipoIdentificador'] ?? 0 }}" min="0" max="3" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tax ID/RFC</label>
                            <input type="text" name="cove_json[facturas][0][emisor][identificacion]" value="{{ $emisor['identificacion'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Razón Social</label>
                            <input type="text" name="cove_json[facturas][0][emisor][nombre]" value="{{ $emisor['nombre'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Calle</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][calle]" value="{{ $emisor['domicilio']['calle'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. Exterior</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][numeroExterior]" value="{{ $emisor['domicilio']['numeroExterior'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. Interior</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][numeroInterior]" value="{{ $emisor['domicilio']['numeroInterior'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Colonia</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][colonia]" value="{{ $emisor['domicilio']['colonia'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Localidad</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][localidad]" value="{{ $emisor['domicilio']['localidad'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Municipio</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][municipio]" value="{{ $emisor['domicilio']['municipio'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">C.P.</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][codigoPostal]" value="{{ $emisor['domicilio']['codigoPostal'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Estado / Provincia</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][entidadFederativa]" value="{{ $emisor['domicilio']['entidadFederativa'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">País</label>
                            <input type="text" name="cove_json[facturas][0][emisor][domicilio][pais]" value="{{ $emisor['domicilio']['pais'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                    </div>
                </div>

                {{-- Sección 3: Destinatario --}}
                <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <h3 class="text-lg font-bold text-[#001a4d] border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i data-lucide="building" class="w-5 h-5 text-[#003399]"></i>
                        Datos Generales del Destinatario
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tipo Identificador (1=RFC, 3=SIN TAX ID)</label>
                            <input type="number" name="cove_json[facturas][0][destinatario][tipoIdentificador]" value="{{ $dest['tipoIdentificador'] ?? 1 }}" min="1" max="3" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">RFC Importer</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][identificacion]" value="{{ $dest['identificacion'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Razón Social</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][nombre]" value="{{ $dest['nombre'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Calle</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][calle]" value="{{ $dest['domicilio']['calle'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. Exterior</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][numeroExterior]" value="{{ $dest['domicilio']['numeroExterior'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. Interior</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][numeroInterior]" value="{{ $dest['domicilio']['numeroInterior'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Colonia</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][colonia]" value="{{ $dest['domicilio']['colonia'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Localidad</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][localidad]" value="{{ $dest['domicilio']['localidad'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">C.P.</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][codigoPostal]" value="{{ $dest['domicilio']['codigoPostal'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Municipio</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][municipio]" value="{{ $dest['domicilio']['municipio'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Estado</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][entidadFederativa]" value="{{ $dest['domicilio']['entidadFederativa'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">País</label>
                            <input type="text" name="cove_json[facturas][0][destinatario][domicilio][pais]" value="{{ $dest['domicilio']['pais'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm">
                        </div>
                    </div>
                </div>

                {{-- Sección 4: Partidas (Mercancías) --}}
                <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <h3 class="text-lg font-bold text-[#001a4d] border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i data-lucide="package" class="w-5 h-5 text-[#003399]"></i>
                        Partidas de Mercancía
                    </h3>
                    <div class="space-y-6" id="mercancias-container">
                        @foreach($mercancias as $idx => $merc)
                            <div class="p-4 bg-slate-50 rounded border border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-4 mercancia-row relative" data-index="{{ $idx }}">
                                <button type="button" onclick="eliminarPartida(this)" class="absolute top-2 right-2 text-xs font-bold text-red-600 hover:text-red-800 transition-colors" title="Eliminar partida">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Descripción Genérica</label>
                                    <input type="text" name="cove_json[facturas][0][mercancias][{{ $idx }}][descripcionGenerica]" value="{{ $merc['descripcionGenerica'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Clave UMC (Medida)</label>
                                    <input type="text" name="cove_json[facturas][0][mercancias][{{ $idx }}][claveUnidadMedida]" value="{{ $merc['claveUnidadMedida'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Moneda</label>
                                    <input type="text" name="cove_json[facturas][0][mercancias][{{ $idx }}][tipoMoneda]" value="{{ $merc['tipoMoneda'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cantidad</label>
                                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][{{ $idx }}][cantidad]" value="{{ $merc['cantidad'] ?? 0 }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Valor Unitario</label>
                                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][{{ $idx }}][valorUnitario]" value="{{ $merc['valorUnitario'] ?? 0 }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Valor Total</label>
                                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][{{ $idx }}][valorTotal]" value="{{ $merc['valorTotal'] ?? 0 }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Valor Dólares</label>
                                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][{{ $idx }}][valorDolares]" value="{{ $merc['valorDolares'] ?? 0 }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                                </div>
                                
                                {{-- Descripciones específicas (Marca, Modelo, Submodelo/Lote, Serie/No. de Parte) --}}
                                <div class="md:col-span-4 mt-2 pt-2 border-t border-slate-200 grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Marca</label>
                                        <input type="text" name="cove_json[facturas][0][mercancias][{{ $idx }}][descripcionesEspecificas][0][marca]" value="{{ $merc['descripcionesEspecificas'][0]['marca'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Modelo</label>
                                        <input type="text" name="cove_json[facturas][0][mercancias][{{ $idx }}][descripcionesEspecificas][0][modelo]" value="{{ $merc['descripcionesEspecificas'][0]['modelo'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Submodelo / Lote</label>
                                        <input type="text" name="cove_json[facturas][0][mercancias][{{ $idx }}][descripcionesEspecificas][0][subModelo]" value="{{ $merc['descripcionesEspecificas'][0]['subModelo'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional / Lote">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Serie / Parte / ID</label>
                                        <input type="text" name="cove_json[facturas][0][mercancias][{{ $idx }}][descripcionesEspecificas][0][numeroSerie]" value="{{ $merc['descripcionesEspecificas'][0]['numeroSerie'] ?? '' }}" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional / Parte / ID">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="button" onclick="agregarPartida()" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-md text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 shadow-sm transition-colors">
                            <i data-lucide="plus" class="w-4 h-4 mr-1.5 text-emerald-600"></i>
                            Agregar Partida de Mercancía
                        </button>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="flex justify-between items-center bg-white border border-slate-200 rounded-lg p-6 shadow-sm">
                    <a href="{{ route('coves.index') }}" class="px-5 py-2.5 border border-slate-300 text-sm font-bold text-slate-700 rounded-md hover:bg-slate-50">
                        Volver sin guardar
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-[#003399] text-sm font-bold text-white rounded-md hover:bg-[#002266]">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function eliminarPartida(button) {
            const container = document.getElementById('mercancias-container');
            const rows = container.getElementsByClassName('mercancia-row');
            if (rows.length <= 1) {
                alert('El COVE debe contener al menos una partida de mercancía.');
                return;
            }
            if (confirm('¿Está seguro de que desea eliminar esta partida?')) {
                const row = button.closest('.mercancia-row');
                row.remove();
            }
        }

        function agregarPartida() {
            const container = document.getElementById('mercancias-container');
            const rows = container.getElementsByClassName('mercancia-row');
            let nextIndex = 0;
            if (rows.length > 0) {
                const lastRow = rows[rows.length - 1];
                nextIndex = parseInt(lastRow.getAttribute('data-index')) + 1;
            }

            const html = `
            <div class="p-4 bg-slate-50 rounded border border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-4 mercancia-row relative" data-index="${nextIndex}">
                <button type="button" onclick="eliminarPartida(this)" class="absolute top-2 right-2 text-xs font-bold text-red-600 hover:text-red-800 transition-colors" title="Eliminar partida">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Descripción Genérica</label>
                    <input type="text" name="cove_json[facturas][0][mercancias][${nextIndex}][descripcionGenerica]" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Clave UMC (Medida)</label>
                    <input type="text" name="cove_json[facturas][0][mercancias][${nextIndex}][claveUnidadMedida]" value="piece" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Moneda</label>
                    <input type="text" name="cove_json[facturas][0][mercancias][${nextIndex}][tipoMoneda]" value="USD" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cantidad</label>
                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][${nextIndex}][cantidad]" value="1.0" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Valor Unitario</label>
                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][${nextIndex}][valorUnitario]" value="0.0" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Valor Total</label>
                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][${nextIndex}][valorTotal]" value="0.0" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Valor Dólares</label>
                    <input type="number" step="any" name="cove_json[facturas][0][mercancias][${nextIndex}][valorDolares]" value="0.0" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" required>
                </div>
                
                <div class="md:col-span-4 mt-2 pt-2 border-t border-slate-200 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Marca</label>
                        <input type="text" name="cove_json[facturas][0][mercancias][${nextIndex}][descripcionesEspecificas][0][marca]" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Modelo</label>
                        <input type="text" name="cove_json[facturas][0][mercancias][${nextIndex}][descripcionesEspecificas][0][modelo]" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Submodelo / Lote</label>
                        <input type="text" name="cove_json[facturas][0][mercancias][${nextIndex}][descripcionesEspecificas][0][subModelo]" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional / Lote">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Serie / Parte / ID</label>
                        <input type="text" name="cove_json[facturas][0][mercancias][${nextIndex}][descripcionesEspecificas][0][numeroSerie]" class="w-full border-slate-200 rounded focus:border-[#003399] focus:ring-[#003399] text-sm" placeholder="Opcional / Parte / ID">
                    </div>
                </div>
            </div>`;

            container.insertAdjacentHTML('beforeend', html);
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    </script>
</x-app-layout>
