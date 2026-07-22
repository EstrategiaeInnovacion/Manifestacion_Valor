<x-app-layout>
    <x-slot name="title">Previsualizar Acuse de COVE</x-slot>

    @php
        $json = $cove->cove_json;
        $factura = $json['facturas'][0] ?? [];
        $emisor = $factura['emisor'] ?? [];
        $dest = $factura['destinatario'] ?? [];
        $mercancias = $factura['mercancias'] ?? [];
    @endphp

    <div class="min-h-screen bg-[#F1F5F9] py-8 print:bg-white print:py-0">
        <div class="max-w-4xl mx-auto px-4 print:px-0">
            
            {{-- Formato del Acuse idéntico a VUCEM --}}
            <div class="bg-white border border-slate-300 p-8 shadow-md font-sans text-slate-900 space-y-6 print:border-none print:shadow-none print:p-0">
                
                {{-- Encabezado Gob.mx --}}
                <div class="bg-[#4D4D4D] text-white px-6 py-4 flex justify-between items-center rounded-t print:rounded-none">
                    <span class="text-lg font-bold tracking-tight">gob<span class="text-[#D0021B]">.</span>mx</span>
                </div>

                {{-- Subencabezado de Ventanilla Digital --}}
                <div class="text-center bg-slate-100 py-3 border-y border-slate-200 text-xs font-semibold text-slate-600 leading-relaxed uppercase">
                    Información de Valor y de Comercialización<br>
                    Ventanilla digital mexicana de comercio exterior<br>
                    Promoción o solicitud en materia de comercio exterior
                </div>

                {{-- Datos del Acuse de Valor --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">Datos del Acuse de Valor: <span class="font-mono text-slate-900 font-black">{{ $cove->edocument ?: 'COVE2683ZWZP1 (PREVIO)' }}</span></h3>
                    
                    <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                        <div class="grid grid-cols-3 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Tipo de operación</span>
                                <span class="text-slate-800">{{ $json['tipoOperacion'] ?? 'Importación' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Relación de facturas</span>
                                <span class="text-slate-800">{{ $json['relacionFacturas'] ?? 'SIN RELACION DE FACTURAS' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">No. de factura</span>
                                <span class="text-slate-800 font-semibold font-mono">{{ $factura['numeroFactura'] ?? 'S001150505' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Tipo de figura</span>
                                <span class="text-slate-800">{{ $json['tipoFigura'] ?? 'Agente Aduanal' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Fecha Exp.</span>
                                <span class="text-slate-800">{{ $json['fechaExpedicion'] ?? '08/05/2026' }}</span>
                            </div>
                        </div>
                        <div class="p-2">
                            <span class="block text-[9px] font-bold text-slate-500 uppercase">Observaciones</span>
                            <span class="text-slate-800 min-h-[16px] block">{{ $json['observaciones'] ?? '' }}</span>
                        </div>
                    </div>
                </div>

                {{-- RFC con permisos de consulta --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">RFC con permisos de consulta</h3>
                    <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                        <div class="grid grid-cols-2 bg-slate-100 text-[10px] font-bold text-slate-600 divide-x divide-slate-300">
                            <div class="p-1.5">RFC de consulta</div>
                            <div class="p-1.5">Nombre o Razón social</div>
                        </div>
                        @foreach(($json['rfcsConsulta'] ?? ['NET070608EM9']) as $rfcC)
                            <div class="grid grid-cols-2 divide-x divide-slate-300">
                                <div class="p-2 font-mono text-slate-800">{{ $rfcC }}</div>
                                <div class="p-2 text-slate-800 uppercase">{{ $cove->applicant->business_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Número de patente aduanal --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">Número de patente aduanal</h3>
                    <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                        <div class="bg-slate-100 text-[10px] font-bold text-slate-600 p-1.5">Número autorización aduanal</div>
                        <div class="p-2 text-slate-800 font-mono">{{ $json['patentesAduanales'][0] ?? '3429' }}</div>
                    </div>
                </div>

                {{-- Datos de la factura --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">Datos de la factura</h3>
                    <div class="border border-slate-300 divide-x divide-slate-300 grid grid-cols-3 text-xs">
                        <div class="p-2">
                            <span class="block text-[9px] font-bold text-slate-500 uppercase">Subdivisión</span>
                            <span class="text-slate-800">{{ ($factura['subdivision'] ?? 0) == 1 ? 'Sí tiene subdivisión' : 'Sin subdivisión' }}</span>
                        </div>
                        <div class="p-2">
                            <span class="block text-[9px] font-bold text-slate-500 uppercase">Certificado de origen</span>
                            <span class="text-slate-800">{{ ($factura['certificadoOrigen'] ?? 0) == 1 ? 'Sí funge como certificado de origen' : 'No funge como certificado de origen' }}</span>
                        </div>
                        <div class="p-2">
                            <span class="block text-[9px] font-bold text-slate-500 uppercase">No. de exportador autorizado</span>
                            <span class="text-slate-800">{{ $factura['numeroExportadorConfiable'] ?? '' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Datos generales del proveedor --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">Datos generales del proveedor</h3>
                    <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                        <div class="grid grid-cols-2 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Tipo de identificador</span>
                                <span class="text-slate-800">{{ ($emisor['tipoIdentificador'] ?? 0) == 1 ? 'RFC' : 'TAX_ID' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Tax ID/Sin Tax ID/RFC/CURP</span>
                                <span class="text-slate-800 font-mono">{{ $emisor['identificacion'] ?? '28098567' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 divide-x divide-slate-300">
                            <div class="p-2 col-span-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Nombre(s) o Razón Social</span>
                                <span class="text-slate-800 uppercase font-semibold">{{ $emisor['nombre'] ?? 'MAGIC NANO TECHNOLOGY CO., LTD' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Apellido paterno / materno</span>
                                <span class="text-slate-800">{{ $emisor['apellidoPaterno'] ?? '' }} {{ $emisor['apellidoMaterno'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Domicilio del proveedor --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">Domicilio del proveedor</h3>
                    <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                        <div class="grid grid-cols-4 divide-x divide-slate-300">
                            <div class="p-2 col-span-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Calle</span>
                                <span class="text-slate-800">{{ $emisor['domicilio']['calle'] ?? 'LN 320, SEC 1 SHATIAN RD' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">No. exterior / interior</span>
                                <span class="text-slate-800">{{ $emisor['domicilio']['numeroExterior'] ?? '33-35' }} / {{ $emisor['domicilio']['numeroInterior'] ?? '' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Código postal</span>
                                <span class="text-slate-800 font-mono">{{ $emisor['domicilio']['codigoPostal'] ?? '43247' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Colonia</span>
                                <span class="text-slate-800">{{ $emisor['domicilio']['colonia'] ?? '' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Localidad</span>
                                <span class="text-slate-800">{{ $emisor['domicilio']['localidad'] ?? 'DADU DIST' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Entidad federativa</span>
                                <span class="text-slate-800">{{ $emisor['domicilio']['entidadFederativa'] ?? '' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Municipio</span>
                                <span class="text-slate-800">{{ $emisor['domicilio']['municipio'] ?? 'TAICHUNG CITY' }}</span>
                            </div>
                        </div>
                        <div class="p-2">
                            <span class="block text-[9px] font-bold text-slate-500 uppercase">País</span>
                            <span class="text-slate-800">{{ $emisor['domicilio']['pais'] ?? 'TAIWAN (REPUBLICA DE CHINA)' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Datos generales del destinatario --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">Datos generales del destinatario</h3>
                    <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                        <div class="grid grid-cols-2 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Tipo de identificador</span>
                                <span class="text-slate-800">{{ ($dest['tipoIdentificador'] ?? 1) == 1 ? 'RFC' : 'TAX_ID' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Tax ID/Sin Tax ID/RFC/CURP</span>
                                <span class="text-slate-800 font-mono">{{ $dest['identificacion'] ?? 'NET070608EM9' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 divide-x divide-slate-300">
                            <div class="p-2 col-span-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Nombre(s) o Razón Social</span>
                                <span class="text-slate-800 uppercase font-semibold">{{ $dest['nombre'] ?? 'NETXICO SA DE CV' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Apellido paterno / materno</span>
                                <span class="text-slate-800">{{ $dest['apellidoPaterno'] ?? '' }} {{ $dest['apellidoMaterno'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Domicilio del destinatario --}}
                <div class="space-y-1">
                    <h3 class="text-[11px] font-bold text-slate-800 uppercase tracking-wide">Domicilio del destinatario</h3>
                    <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                        <div class="grid grid-cols-4 divide-x divide-slate-300">
                            <div class="p-2 col-span-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Calle</span>
                                <span class="text-slate-800">{{ $dest['domicilio']['calle'] ?? 'AV SIERRA LEONA' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">No. exterior / interior</span>
                                <span class="text-slate-800">{{ $dest['domicilio']['numeroExterior'] ?? '360' }} / {{ $dest['domicilio']['numeroInterior'] ?? '9' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Código postal</span>
                                <span class="text-slate-800 font-mono">{{ $dest['domicilio']['codigoPostal'] ?? '78214' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Colonia</span>
                                <span class="text-slate-800">{{ $dest['domicilio']['colonia'] ?? 'VILLANTIGUA' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Localidad</span>
                                <span class="text-slate-800">{{ $dest['domicilio']['localidad'] ?? 'SAN LUIS POTOSI' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-slate-300">
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Entidad federativa</span>
                                <span class="text-slate-800">{{ $dest['domicilio']['entidadFederativa'] ?? 'SAN LUIS POTOSI' }}</span>
                            </div>
                            <div class="p-2">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Municipio</span>
                                <span class="text-slate-800">{{ $dest['domicilio']['municipio'] ?? 'SAN LUIS POTOSI' }}</span>
                            </div>
                        </div>
                        <div class="p-2">
                            <span class="block text-[9px] font-bold text-slate-500 uppercase">País</span>
                            <span class="text-slate-800">{{ $dest['domicilio']['pais'] ?? 'MEXICO' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Partidas de Mercancía --}}
                @foreach($mercancias as $idx => $merc)
                    <div class="space-y-1 pt-4 border-t border-slate-200">
                        <h3 class="text-[11px] font-bold text-slate-850 uppercase tracking-wide">Datos de la mercancía</h3>
                        <div class="border border-slate-300 divide-y divide-slate-300 text-xs">
                            <div class="grid grid-cols-3 divide-x divide-slate-300">
                                <div class="p-2 col-span-2">
                                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Descripción genérica de la mercancía</span>
                                    <span class="text-slate-800 uppercase font-semibold">{{ $merc['descripcionGenerica'] ?? 'CARBÓN EN GRÁNULOS' }}</span>
                                </div>
                                <div class="p-2">
                                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Cantidad de mercancía</span>
                                    <span class="text-slate-800 font-mono font-bold">{{ number_format((float)($merc['cantidad'] ?? 0), 3) }}</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 divide-x divide-slate-300">
                                <div class="p-2">
                                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Unidad de Medida</span>
                                    <span class="text-slate-800 uppercase">{{ $merc['claveUnidadMedida'] ?? 'piece' }}</span>
                                </div>
                                <div class="p-2">
                                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Tipo moneda</span>
                                    <span class="text-slate-800">{{ ($merc['tipoMoneda'] ?? 'USD') === 'USD' ? 'US Dollar' : $merc['tipoMoneda'] }}</span>
                                </div>
                                <div class="p-2">
                                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Valor unitario</span>
                                    <span class="text-slate-800 font-mono">$ {{ number_format((float)($merc['valorUnitario'] ?? 0), 6) }}</span>
                                </div>
                                <div class="p-2">
                                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Valor total / total dólares</span>
                                    <span class="text-slate-800 font-mono font-semibold">$ {{ number_format((float)($merc['valorTotal'] ?? 0), 6) }} / $ {{ number_format((float)($merc['valorDolares'] ?? 0), 4) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Descripciones específicas --}}
                        <div class="space-y-1 pt-1.5">
                            <div class="border border-slate-300 text-xs">
                                <div class="grid grid-cols-4 bg-slate-100 text-[9px] font-bold text-slate-500 divide-x divide-slate-300 border-b border-slate-300">
                                    <div class="p-1">Marca</div>
                                    <div class="p-1">Modelo</div>
                                    <div class="p-1">Submodelo</div>
                                    <div class="p-1">No. serie / Parte / Lote</div>
                                </div>
                                <div class="grid grid-cols-4 divide-x divide-slate-300">
                                    <div class="p-1.5 text-slate-800 uppercase">{{ $merc['descripcionesEspecificas'][0]['marca'] ?? '' }}</div>
                                    <div class="p-1.5 text-slate-800 uppercase">{{ $merc['descripcionesEspecificas'][0]['modelo'] ?? '' }}</div>
                                    <div class="p-1.5 text-slate-800 uppercase">{{ $merc['descripcionesEspecificas'][0]['subModelo'] ?? '' }}</div>
                                    <div class="p-1.5 text-slate-800 font-mono text-[10px]">{{ $merc['descripcionesEspecificas'][0]['numeroSerie'] ?? '' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Pie de página Institucional --}}
                <div class="border-t border-slate-200 pt-6 flex justify-between items-center text-[10px] text-slate-400">
                    <div class="flex gap-4 items-center">
                        <span class="font-bold text-[#003399]">MÉXICO</span>
                        <span class="border-l border-slate-300 h-4"></span>
                        <span class="text-slate-500 font-semibold">Ventanilla única</span>
                    </div>
                    <div class="text-right">
                        <span>Página 1 de 1</span>
                    </div>
                </div>

            </div>
            
            {{-- Acciones del Acuse --}}
            <div class="mt-6 flex justify-between items-center bg-white p-4 rounded-b-lg border border-slate-200 shadow-sm print:hidden">
                <button type="button" onclick="window.close()" class="px-5 py-2 border border-slate-300 text-xs font-bold text-slate-600 rounded bg-slate-50 hover:bg-slate-100">
                    Cerrar Previsualización
                </button>
                <button type="button" onclick="window.print()" class="px-5 py-2 bg-[#003399] text-xs font-bold text-white rounded hover:bg-[#002266] shadow">
                    Imprimir Acuse
                </button>
            </div>

        </div>
    </div>
</x-app-layout>
