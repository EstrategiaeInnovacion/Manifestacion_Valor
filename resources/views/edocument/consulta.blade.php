<x-app-layout>
    @vite(['resources/css/mve-create.css', 'resources/js/edocument-consulta.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">
                            Consulta eDocument (VUCEM)
                        </span>
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

        <main class="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>

                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Consulta <span class="text-[#003399]">eDocument</span> en VUCEM
                </h2>
                <p class="text-slate-500 mt-2">
                    Ingresa el folio del eDocument para consultar en VUCEM. Las credenciales se obtienen automáticamente de su perfil.
                </p>
                
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2 flex items-center">
                        <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                        Información sobre Credenciales VUCEM
                    </h3>
                    <div class="text-xs text-blue-700 space-y-1">
                        <p>• <strong>RFC:</strong> Se obtiene automáticamente de su perfil o de los RFC asociados</p>
                        <p>• <strong>Clave WebService:</strong> Se obtiene automáticamente de su configuración</p>
                        <p>• <strong>eFirma:</strong> Se usa la configuración global o los archivos subidos si se requieren</p>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-red-800 mb-2">Error de Validación</h3>
                            <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            @if ($errors->has('certificado') && str_contains($errors->first('certificado'), 'X.509'))
                                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <p class="text-xs text-yellow-700"><strong>Tip:</strong> Verifique que su certificado sea .cer (PEM) y no binario.</p>
                                </div>
                            @endif
                            @if ($errors->has('llave_privada') && str_contains($errors->first('llave_privada'), 'binario'))
                                <div class="mt-3 p-3 bg-orange-50 border border-orange-200 rounded-md">
                                    <p class="text-xs text-orange-700"><strong>Tip:</strong> Su llave privada parece estar en formato DER (binario). Debe convertirla a PEM.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
                <form method="POST" action="{{ route('edocument.consulta') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 gap-6">
                        @if(isset($solicitantes) && $solicitantes->count() > 0)
                            <div>
                                <label for="solicitante_id" class="block text-sm font-semibold text-slate-700 mb-2">Seleccionar Solicitante</label>
                                <select name="solicitante_id" id="solicitante_id" class="form-input w-full" required>
                                    <option value="">Seleccione un solicitante...</option>
                                    @foreach($solicitantes as $solicitante)
                                        <option value="{{ $solicitante->id }}" {{ old('solicitante_id', $solicitante_seleccionado ?? '') == $solicitante->id ? 'selected' : '' }}>
                                            {{ $solicitante->applicant_rfc }} - {{ $solicitante->business_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="folio_edocument" class="block text-sm font-semibold text-slate-700 mb-2">Folio eDocument</label>
                                <input type="text" name="folio_edocument" id="folio_edocument" value="{{ old('folio_edocument', $folio ?? '') }}" class="form-input w-full" placeholder="Ej. COVE12345..." required />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="certificado" class="block text-sm font-semibold text-slate-700 mb-2">Certificado (.cer) <span class="text-xs font-normal text-gray-400">(Opcional si ya está configurado)</span></label>
                                    <input type="file" name="certificado" id="certificado" class="form-input w-full" accept=".cer,.crt,.pem" />
                                </div>
                                <div>
                                    <label for="llave_privada" class="block text-sm font-semibold text-slate-700 mb-2">Llave Privada (.key) <span class="text-xs font-normal text-gray-400">(Opcional)</span></label>
                                    <input type="file" name="llave_privada" id="llave_privada" class="form-input w-full" accept=".key,.pem" />
                                </div>
                            </div>

                            <div>
                                <label for="contrasena_llave" class="block text-sm font-semibold text-slate-700 mb-2">Contraseña de la Llave Privada <span class="text-xs font-normal text-gray-400">(Opcional)</span></label>
                                <input type="password" name="contrasena_llave" id="contrasena_llave" class="form-input w-full" placeholder="Contraseña..." />
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary">
                                    <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                    Consultar eDocument
                                </button>
                            </div>
                        @else
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <p class="text-amber-800 text-sm">No hay solicitantes registrados. Registre uno primero.</p>
                            </div>
                        @endif
                    </div>
                </form>
            </div>

            @if(isset($result))
                <div class="mt-8 space-y-6">
                    
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                        <div class="bg-slate-50 px-8 py-4 border-b border-slate-200 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-[#001a4d]">Estado de la Consulta</h3>
                            @if($result['success'])
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                    <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> ÉXITO
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                    <i data-lucide="x-circle" class="w-3 h-3 mr-1"></i> ERROR
                                </span>
                            @endif
                        </div>
                        <div class="p-8">
                            <p class="text-slate-600 mb-2"><strong>Mensaje VUCEM:</strong> {{ $result['message'] }}</p>
                            @if(isset($folio))
                                <p class="text-slate-600"><strong>Folio Consultado:</strong> <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $folio }}</span></p>
                            @endif
                        </div>
                    </div>

                    @if(isset($result['cove_data']) && !empty($result['cove_data']))
                        @php $cove = $result['cove_data']; @endphp

                        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                            <div class="bg-[#003399] px-8 py-4 border-b border-[#002266]">
                                <h3 class="text-lg font-bold text-white flex items-center">
                                    <i data-lucide="file-text" class="w-5 h-5 mr-2"></i>
                                    Información General del COVE
                                </h3>
                            </div>
                            <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">eDocument</p>
                                    <p class="text-lg font-mono font-bold text-slate-800">{{ $cove['eDocument'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">Tipo Operación</p>
                                    <p class="text-base font-semibold text-slate-700">{{ $cove['tipoOperacion'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">Fecha Expedición</p>
                                    <p class="text-base font-semibold text-slate-700">{{ $cove['fechaExpedicion'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">Factura / Relación</p>
                                    <p class="text-base text-slate-700">{{ $cove['numeroFacturaRelacionFacturas'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">Patente Aduanal</p>
                                    <p class="text-base text-slate-700">{{ $cove['patentesAduanales']['patenteAduanal'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">RFC Consulta</p>
                                    <p class="text-base text-slate-700">{{ $cove['rfcsConsulta']['rfcConsulta'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                            @if(!empty($cove['observaciones']))
                                <div class="px-8 pb-8">
                                    <p class="text-xs uppercase font-bold text-slate-400 mb-1">Observaciones</p>
                                    <div class="bg-yellow-50 p-3 rounded-md border border-yellow-100 text-sm text-yellow-800 italic">
                                        {{ $cove['observaciones'] }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                                <div class="bg-slate-100 px-6 py-3 border-b border-slate-200">
                                    <h4 class="font-bold text-slate-700 uppercase text-sm">Emisor (Proveedor)</h4>
                                </div>
                                <div class="p-6">
                                    @if(isset($cove['emisor']))
                                        <p class="text-lg font-bold text-[#003399]">{{ $cove['emisor']['nombre'] ?? 'N/A' }}</p>
                                        <p class="text-sm text-slate-500 mb-4">ID/Tax ID: {{ $cove['emisor']['identificacion'] ?? 'N/A' }}</p>
                                        
                                        @if(isset($cove['emisor']['domicilio']))
                                            <div class="text-sm text-slate-600 bg-slate-50 p-3 rounded border border-slate-100">
                                                <p>{{ $cove['emisor']['domicilio']['calle'] ?? '' }} {{ $cove['emisor']['domicilio']['numeroExterior'] ?? '' }}</p>
                                                <p>{{ $cove['emisor']['domicilio']['municipio'] ?? '' }}, {{ $cove['emisor']['domicilio']['pais'] ?? '' }}</p>
                                                <p>CP: {{ $cove['emisor']['domicilio']['codigoPostal'] ?? '' }}</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                                <div class="bg-slate-100 px-6 py-3 border-b border-slate-200">
                                    <h4 class="font-bold text-slate-700 uppercase text-sm">Destinatario (Importador)</h4>
                                </div>
                                <div class="p-6">
                                    @if(isset($cove['destinatario']))
                                        <p class="text-lg font-bold text-emerald-700">{{ $cove['destinatario']['nombre'] ?? 'N/A' }}</p>
                                        <p class="text-sm text-slate-500 mb-4">RFC: {{ $cove['destinatario']['identificacion'] ?? 'N/A' }}</p>
                                        
                                        @if(isset($cove['destinatario']['domicilio']))
                                            <div class="text-sm text-slate-600 bg-slate-50 p-3 rounded border border-slate-100">
                                                <p>{{ $cove['destinatario']['domicilio']['calle'] ?? '' }} {{ $cove['destinatario']['domicilio']['numeroExterior'] ?? '' }}</p>
                                                <p>{{ $cove['destinatario']['domicilio']['municipio'] ?? '' }}, {{ $cove['destinatario']['domicilio']['pais'] ?? '' }}</p>
                                                <p>CP: {{ $cove['destinatario']['domicilio']['codigoPostal'] ?? '' }}</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                            <div class="bg-slate-800 px-8 py-4 border-b border-slate-700">
                                <h3 class="text-lg font-bold text-white flex items-center">
                                    <i data-lucide="package" class="w-5 h-5 mr-2"></i>
                                    Detalle de Mercancías
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Descripción</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Cantidad / UM</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Valor Unitario</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Valor Total</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Moneda</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-200">
                                        @php
                                            // Lógica para normalizar mercancias (a veces es array de arrays, a veces objeto único)
                                            $mercancias = [];
                                            if (isset($cove['facturas']['factura']['mercancias']['mercancia'])) {
                                                $data = $cove['facturas']['factura']['mercancias']['mercancia'];
                                                // Si la clave 0 existe, es un array de items, si no, es un solo item
                                                $mercancias = isset($data[0]) ? $data : [$data];
                                            }
                                        @endphp

                                        @foreach($mercancias as $item)
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-slate-900">{{ $item['descripcionGenerica'] ?? 'S/D' }}</div>
                                                    @if(isset($item['descripcionesEspecificas']['descripcionEspecifica']))
                                                        @php
                                                            $desc = $item['descripcionesEspecificas']['descripcionEspecifica'];
                                                            $desc = isset($desc[0]) ? $desc[0] : $desc;
                                                        @endphp
                                                        <div class="text-xs text-slate-500 mt-1 flex gap-2">
                                                            @if(isset($desc['marca'])) <span class="bg-slate-100 px-2 py-0.5 rounded border">Marca: {{ $desc['marca'] }}</span> @endif
                                                            @if(isset($desc['subModelo'])) <span class="bg-slate-100 px-2 py-0.5 rounded border">Mod: {{ $desc['subModelo'] }}</span> @endif
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-bold text-slate-700">{{ $item['cantidad'] ?? 0 }}</div>
                                                    <div class="text-xs text-slate-500">{{ $item['claveUnidadMedida'] ?? '' }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-600 font-mono">
                                                    {{ number_format((float)($item['valorUnitario'] ?? 0), 4) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-900 font-mono">
                                                    {{ number_format((float)($item['valorTotal'] ?? 0), 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-blue-50 text-blue-800">
                                                        {{ $item['tipoMoneda'] ?? '---' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-slate-50">
                                        <tr>
                                            <td colspan="3" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Total Items: {{ count($mercancias) }}</td>
                                            <td class="px-6 py-3 text-right text-sm font-black text-slate-900 font-mono">
                                                {{ number_format(collect($mercancias)->sum('valorTotal'), 2) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    @endif

                    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
                        <h4 class="text-lg font-bold text-[#001a4d] mb-4 flex items-center">
                            <i data-lucide="paperclip" class="w-5 h-5 mr-2"></i>
                            Archivos XML / PDF
                        </h4>
                        @if(isset($files) && count($files) > 0)
                            <ul class="divide-y divide-slate-200 border border-slate-200 rounded-lg overflow-hidden">
                                @foreach($files as $file)
                                    <li class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                        <div class="flex items-center">
                                            <div class="bg-red-100 p-2 rounded-lg text-red-600 mr-3">
                                                <i data-lucide="file-text" class="w-5 h-5"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-700">{{ $file['name'] }}</p>
                                                <p class="text-xs text-slate-400">{{ $file['mime'] }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('edocument.descargar', $file['token']) }}" class="btn-secondary text-xs px-3 py-2">
                                            <i data-lucide="download" class="w-3 h-3 mr-1.5"></i>
                                            Descargar
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-6 bg-slate-50 rounded-lg border border-dashed border-slate-300">
                                <p class="text-slate-500 text-sm">No se encontraron archivos XML/PDF adjuntos en la respuesta de VUCEM.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </main>
    </div>
</x-app-layout>