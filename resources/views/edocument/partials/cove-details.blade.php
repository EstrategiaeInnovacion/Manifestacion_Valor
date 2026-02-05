<div class="space-y-6 animate-fade-in-up">
    
    {{-- 1. ENCABEZADO: DATOS GENERALES DEL COVE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
        <div class="bg-[#003399] px-6 py-4 border-b border-[#002266] flex justify-between items-center">
            <h3 class="text-lg font-bold text-white flex items-center">
                <i data-lucide="file-text" class="w-5 h-5 mr-2"></i>
                Información General del COVE
            </h3>
            <span class="bg-blue-800 text-blue-100 text-xs font-mono py-1 px-3 rounded-full border border-blue-700">
                {{ $cove['eDocument'] ?? 'SIN FOLIO' }}
            </span>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-y-6 gap-x-4">
            {{-- Fila 1 --}}
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tipo de Operación</p>
                <div class="font-bold text-slate-800">{{ $cove['tipoOperacion'] ?? '---' }}</div>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Fecha Expedición</p>
                <div class="font-bold text-slate-800 flex items-center">
                    <i data-lucide="calendar" class="w-3 h-3 mr-1.5 text-slate-400"></i>
                    {{ $cove['fechaExpedicion'] ?? '---' }}
                </div>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Factura / Relación</p>
                <div class="font-bold text-[#003399]">{{ $cove['numeroFacturaRelacionFacturas'] ?? '---' }}</div>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tipo de Figura</p>
                <div class="font-bold text-slate-800">{{ $cove['tipoFigura'] ?? '---' }}</div>
            </div>

            {{-- Fila 2: Patentes y RFCs (Manejo de Arrays) --}}
            <div class="md:col-span-2">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Patentes Aduanales</p>
                <div class="flex flex-wrap gap-2">
                    @php
                        $patentes = [];
                        if (isset($cove['patentesAduanales']['patenteAduanal'])) {
                            $data = $cove['patentesAduanales']['patenteAduanal'];
                            $patentes = is_array($data) ? $data : [$data];
                        }
                    @endphp
                    @forelse($patentes as $patente)
                        <span class="bg-slate-100 text-slate-600 text-xs font-mono px-2 py-1 rounded border border-slate-200">
                            {{ is_array($patente) ? implode('', $patente) : $patente }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-400 italic">Sin patentes registradas</span>
                    @endforelse
                </div>
            </div>

            <div class="md:col-span-2">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">RFCs de Consulta</p>
                <div class="flex flex-wrap gap-2">
                    @php
                        $rfcs = [];
                        if (isset($cove['rfcsConsulta']['rfcConsulta'])) {
                            $data = $cove['rfcsConsulta']['rfcConsulta'];
                            $rfcs = is_array($data) ? $data : [$data];
                        }
                    @endphp
                    @forelse($rfcs as $rfcItem)
                        <span class="bg-emerald-50 text-emerald-700 text-xs font-mono px-2 py-1 rounded border border-emerald-100">
                            {{ is_array($rfcItem) ? implode('', $rfcItem) : $rfcItem }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-400 italic">Sin RFCs adicionales</span>
                    @endforelse
                </div>
            </div>
        </div>

        @if(!empty($cove['observaciones']))
            <div class="px-6 pb-6 pt-2 border-t border-slate-100 mt-2">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Observaciones</p>
                <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-100 text-sm text-yellow-800 italic flex items-start">
                    <i data-lucide="message-square" class="w-4 h-4 mr-2 mt-0.5 shrink-0"></i>
                    {{ $cove['observaciones'] }}
                </div>
            </div>
        @endif
    </div>

    {{-- 2. EMISOR Y DESTINATARIO --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Emisor --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 relative overflow-hidden group hover:border-blue-300 transition-colors">
            <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
                <i data-lucide="upload-cloud" class="w-20 h-20 text-blue-600"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-lg mr-3">
                        <i data-lucide="store" class="w-5 h-5"></i>
                    </div>
                    <h4 class="font-bold text-slate-700 uppercase text-sm">Emisor (Proveedor)</h4>
                </div>
                
                @if(isset($cove['emisor']))
                    <p class="text-lg font-bold text-[#001a4d] leading-tight mb-1">{{ $cove['emisor']['nombre'] ?? 'N/A' }}</p>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded">
                            {{ $cove['emisor']['tipoIdentificador'] == 0 ? 'TAX ID' : 'RFC' }}
                        </span>
                        <span class="text-sm font-mono text-slate-500">{{ $cove['emisor']['identificacion'] ?? 'N/A' }}</span>
                    </div>
                    
                    @if(isset($cove['emisor']['domicilio']))
                        <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 space-y-1">
                            <p class="font-semibold">{{ $cove['emisor']['domicilio']['calle'] ?? '' }} {{ $cove['emisor']['domicilio']['numeroExterior'] ?? '' }} {{ $cove['emisor']['domicilio']['numeroInterior'] ?? '' }}</p>
                            <p>{{ $cove['emisor']['domicilio']['colonia'] ?? '' }}</p>
                            <p>{{ $cove['emisor']['domicilio']['municipio'] ?? '' }}, {{ $cove['emisor']['domicilio']['entidadFederativa'] ?? '' }}</p>
                            <p class="font-bold">{{ $cove['emisor']['domicilio']['pais'] ?? '' }} <span class="font-normal text-slate-400">|</span> CP: {{ $cove['emisor']['domicilio']['codigoPostal'] ?? '' }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-slate-400 italic text-sm">No hay información del emisor.</p>
                @endif
            </div>
        </div>

        {{-- Destinatario --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 relative overflow-hidden group hover:border-emerald-300 transition-colors">
            <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
                <i data-lucide="download-cloud" class="w-20 h-20 text-emerald-600"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg mr-3">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <h4 class="font-bold text-slate-700 uppercase text-sm">Destinatario (Importador)</h4>
                </div>

                @if(isset($cove['destinatario']))
                    <p class="text-lg font-bold text-[#001a4d] leading-tight mb-1">{{ $cove['destinatario']['nombre'] ?? 'N/A' }}</p>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded">
                            {{ $cove['destinatario']['tipoIdentificador'] == 0 ? 'TAX ID' : 'RFC' }}
                        </span>
                        <span class="text-sm font-mono text-slate-500">{{ $cove['destinatario']['identificacion'] ?? 'N/A' }}</span>
                    </div>

                    @if(isset($cove['destinatario']['domicilio']))
                        <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 space-y-1">
                            <p class="font-semibold">{{ $cove['destinatario']['domicilio']['calle'] ?? '' }} {{ $cove['destinatario']['domicilio']['numeroExterior'] ?? '' }} {{ $cove['destinatario']['domicilio']['numeroInterior'] ?? '' }}</p>
                            <p>{{ $cove['destinatario']['domicilio']['colonia'] ?? '' }}</p>
                            <p>{{ $cove['destinatario']['domicilio']['municipio'] ?? '' }}, {{ $cove['destinatario']['domicilio']['entidadFederativa'] ?? '' }}</p>
                            <p class="font-bold">{{ $cove['destinatario']['domicilio']['pais'] ?? '' }} <span class="font-normal text-slate-400">|</span> CP: {{ $cove['destinatario']['domicilio']['codigoPostal'] ?? '' }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-slate-400 italic text-sm">No hay información del destinatario.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- 3. DATOS DE FACTURACIÓN --}}
    @php
        // Intentar obtener la primera factura para datos extra
        $facturaInfo = null;
        if (isset($cove['facturas']['factura'])) {
            $fData = $cove['facturas']['factura'];
            // Si es array de facturas, tomamos la primera, si no, tomamos el objeto directo
            $facturaInfo = (isset($fData[0]) && is_array($fData[0])) ? $fData[0] : $fData;
        }
    @endphp

    @if($facturaInfo)
    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cert. Origen</p>
            <p class="font-semibold text-slate-700 text-sm">{{ isset($facturaInfo['certificadoOrigen']) && $facturaInfo['certificadoOrigen'] == '1' ? 'SÍ' : 'NO' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">No. Exp. Confiable</p>
            <p class="font-semibold text-slate-700 text-sm">{{ $facturaInfo['numeroExportadorConfiable'] ?? 'N/A' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subdivisión</p>
            <p class="font-semibold text-slate-700 text-sm">{{ isset($facturaInfo['subdivision']) && $facturaInfo['subdivision'] == '1' ? 'SÍ' : 'NO' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Factura Origen</p>
            <p class="font-semibold text-slate-700 text-sm">{{ $facturaInfo['numeroFactura'] ?? 'N/A' }}</p>
        </div>
    </div>
    @endif

    {{-- 4. MERCANCÍAS --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-[#001a4d] flex items-center">
                <i data-lucide="package" class="w-5 h-5 mr-2 text-[#003399]"></i>
                Detalle de Mercancías
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4 text-center">Cantidad</th>
                        <th class="px-6 py-4 text-right">Valor Unitario</th>
                        <th class="px-6 py-4 text-right">Valor Total</th>
                        <th class="px-6 py-4 text-right">Valor USD</th>
                        <th class="px-6 py-4 text-center">Moneda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        // Lógica robusta para extraer mercancías (puede venir anidado o plano)
                        $mercancias = [];
                        if (isset($cove['facturas']['factura'])) {
                            $facturas = $cove['facturas']['factura'];
                            // Normalizamos a array de facturas
                            if (!isset($facturas[0])) $facturas = [$facturas];
                            
                            foreach ($facturas as $factura) {
                                if (isset($factura['mercancias']['mercancia'])) {
                                    $m = $factura['mercancias']['mercancia'];
                                    // Normalizamos a array de mercancías
                                    if (!isset($m[0])) $m = [$m];
                                    $mercancias = array_merge($mercancias, $m);
                                }
                            }
                        }
                    @endphp

                    @forelse($mercancias as $item)
                    <tr class="hover:bg-blue-50/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700 group-hover:text-[#003399] transition-colors">
                                {{ $item['descripcionGenerica'] ?? 'S/D' }}
                            </div>
                            
                            {{-- Descripciones Específicas (Marca, Modelo, Serie) --}}
                            @if(isset($item['descripcionesEspecificas']['descripcionEspecifica']))
                                @php
                                    $specs = $item['descripcionesEspecificas']['descripcionEspecifica'];
                                    if (!isset($specs[0])) $specs = [$specs];
                                @endphp
                                <div class="mt-2 space-y-1">
                                    @foreach($specs as $spec)
                                        <div class="text-xs text-slate-500 flex flex-wrap gap-2 items-center bg-slate-50 p-1.5 rounded border border-slate-100">
                                            @if(!empty($spec['marca'])) 
                                                <span class="font-semibold text-slate-600">Marca: {{ $spec['marca'] }}</span> 
                                            @endif
                                            @if(!empty($spec['modelo'])) 
                                                <span class="text-slate-400">|</span> 
                                                <span>Mod: {{ $spec['modelo'] }}</span> 
                                            @endif
                                            @if(!empty($spec['subModelo'])) 
                                                <span class="text-slate-400">|</span> 
                                                <span>Sub: {{ $spec['subModelo'] }}</span> 
                                            @endif
                                            @if(!empty($spec['numeroSerie'])) 
                                                <span class="text-slate-400">|</span> 
                                                <span class="font-mono text-slate-600">SN: {{ $spec['numeroSerie'] }}</span> 
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="font-bold text-slate-800">{{ number_format((float)($item['cantidad'] ?? 0), 2) }}</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase">{{ $item['claveUnidadMedida'] ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-slate-600">
                            ${{ number_format((float)($item['valorUnitario'] ?? 0), 4) }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-[#003399] font-mono text-base">
                            ${{ number_format((float)($item['valorTotal'] ?? 0), 2) }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-emerald-600 text-xs">
                            ${{ number_format((float)($item['valorDolares'] ?? 0), 2) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                {{ $item['tipoMoneda'] ?? 'USD' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <i data-lucide="package-open" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                            <p>No hay mercancías detalladas en la respuesta.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($mercancias) > 0)
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right font-bold text-slate-600 uppercase text-xs tracking-wider">Total Valor:</td>
                        <td class="px-6 py-3 text-right font-black text-[#003399] text-lg">
                            ${{ number_format(collect($mercancias)->sum('valorTotal'), 2) }}
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-emerald-600 text-sm">
                            USD ${{ number_format(collect($mercancias)->sum('valorDolares'), 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>