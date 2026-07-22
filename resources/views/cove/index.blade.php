<x-app-layout>
    <x-slot name="title">Listado de COVEs</x-slot>

    <div class="min-h-screen bg-[#F8FAFC] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-10">
                <div>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-4">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                        Regresar al Dashboard
                    </a>
                    <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                        Transmisión de <span class="text-[#003399]">COVE</span>
                    </h2>
                    <p class="text-slate-500 mt-2">Administra y envía Comprobantes de Valor Electrónico a VUCEM</p>
                </div>
                
                <a href="{{ route('coves.select-applicant') }}" class="px-5 py-3 bg-[#003399] text-sm font-bold text-white rounded-md hover:bg-[#002266] shadow-sm flex items-center gap-2">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    Nuevo COVE (Archivo M)
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-md text-emerald-700 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                @if($coves->isEmpty())
                    <div class="p-12 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                            <i data-lucide="file-digit" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-xl font-bold text-[#001a4d]">No hay COVEs registrados</h3>
                        <p class="text-slate-500 mt-1 mb-6">Carga un Archivo M de pedimento para empezar.</p>
                        <a href="{{ route('coves.select-applicant') }}" class="px-5 py-2 bg-[#003399] text-sm font-bold text-white rounded-md hover:bg-[#002266]">
                            Crear Primer COVE
                        </a>
                    </div>
                @else
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="p-5">Solicitante</th>
                                <th class="p-5">Factura</th>
                                <th class="p-5">Estatus</th>
                                <th class="p-5">e-Document</th>
                                <th class="p-5">Fecha Carga</th>
                                <th class="p-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($coves as $cove)
                                <tr>
                                    <td class="p-5">
                                        <div class="font-bold text-[#001a4d]">{{ $cove->applicant->business_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $cove->applicant->applicant_rfc }}</div>
                                    </td>
                                    <td class="p-5">
                                        <div class="font-semibold text-slate-700">#{{ $cove->factura_numero }}</div>
                                        <div class="text-xs text-slate-400">{{ $cove->factura_fecha }}</div>
                                    </td>
                                    <td class="p-5">
                                        @if($cove->status === 'borrador')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                                Borrador
                                            </span>
                                        @elseif($cove->status === 'pendiente')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 animate-pulse">
                                                Pendiente
                                            </span>
                                        @elseif($cove->status === 'enviado')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 animate-pulse">
                                                Transmitiendo
                                            </span>
                                        @elseif($cove->status === 'procesado')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                Completado
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="{{ $cove->error_mensaje }}">
                                                Error ⚠️
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-5 font-mono text-xs text-slate-600">
                                        {{ $cove->edocument ?: 'N/A' }}
                                    </td>
                                    <td class="p-5 text-slate-500 text-xs">
                                        {{ $cove->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="p-5 text-right">
                                        <div class="flex justify-end gap-3">
                                            @if(in_array($cove->status, ['borrador', 'error']))
                                                <a href="{{ route('coves.preview', $cove) }}" target="_blank" class="px-3 py-1.5 border border-[#003399] text-xs font-bold text-[#003399] rounded hover:bg-blue-50">
                                                    Previsualizar
                                                </a>
                                                <a href="{{ route('coves.edit', $cove) }}" class="px-3 py-1.5 border border-slate-300 text-xs font-bold text-slate-700 rounded hover:bg-slate-50">
                                                    Editar
                                                </a>
                                                <form action="{{ route('coves.transmit', $cove) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-[#003399] text-xs font-bold text-white rounded hover:bg-[#002266]">
                                                        Transmitir
                                                    </button>
                                                </form>
                                            @elseif(in_array($cove->status, ['pendiente', 'enviado']))
                                                <a href="{{ route('coves.preview', $cove) }}" target="_blank" class="px-3 py-1.5 border border-[#003399] text-xs font-bold text-[#003399] rounded hover:bg-blue-50">
                                                    Previsualizar
                                                </a>
                                                <button class="px-3 py-1.5 border border-amber-200 text-xs font-bold text-amber-500 rounded cursor-not-allowed animate-pulse" disabled>
                                                    Transmitiendo...
                                                </button>
                                                {{-- Botón para registrar e-Document manualmente una vez que VUCEM lo procese --}}
                                                <button
                                                    onclick="document.getElementById('modal-edoc-{{ $cove->id }}').classList.remove('hidden')"
                                                    class="px-3 py-1.5 border border-slate-300 text-xs font-bold text-slate-600 rounded hover:bg-slate-50"
                                                    title="¿Ya tienes el e-Document de VUCEM? Regístralo aquí">
                                                    📋 e-Doc
                                                </button>
                                                {{-- Modal ingreso e-Document --}}
                                                <div id="modal-edoc-{{ $cove->id }}" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                                                    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-sm">
                                                        <h3 class="text-sm font-bold text-slate-800 mb-1">Registrar e-Document</h3>
                                                        <p class="text-xs text-slate-500 mb-4">Ingresa el número de e-Document que encontraste en el portal de VUCEM para el COVE <strong>{{ $cove->factura_numero }}</strong>.</p>
                                                        <form action="{{ route('coves.register-edocument', $cove) }}" method="POST" class="space-y-3">
                                                            @csrf
                                                            <input type="text" name="edocument" placeholder="Ej. 1234567890123" maxlength="30"
                                                                class="w-full border border-slate-300 rounded text-sm px-3 py-2 focus:border-[#003399] focus:ring-[#003399]" required>
                                                            <div class="flex gap-2 justify-end">
                                                                <button type="button" onclick="document.getElementById('modal-edoc-{{ $cove->id }}').classList.add('hidden')"
                                                                    class="px-4 py-2 text-xs font-bold text-slate-600 border border-slate-300 rounded hover:bg-slate-50">
                                                                    Cancelar
                                                                </button>
                                                                <button type="submit"
                                                                    class="px-4 py-2 text-xs font-bold text-white bg-[#003399] rounded hover:bg-[#002266]">
                                                                    Guardar
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="inline-flex items-center text-xs font-bold text-emerald-600 gap-1 mr-2">
                                                    <i data-lucide="check" class="w-4 h-4"></i> Transmitido
                                                </span>
                                                <a href="{{ route('coves.preview', $cove) }}" target="_blank" class="px-3 py-1.5 border border-[#003399] text-xs font-bold text-[#003399] rounded hover:bg-blue-50">
                                                    Previsualizar
                                                </a>
                                            @endif
                                            
                                            {{-- Botón general para eliminar COVEs (disponible siempre excepto si ya está procesado) --}}
                                            @if($cove->status !== 'procesado')
                                                <form action="{{ route('coves.destroy', $cove) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este registro de COVE?');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-3 py-1.5 border border-red-300 text-xs font-bold text-red-600 rounded hover:bg-red-50" title="Eliminar registro">
                                                        🗑️
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="p-5 border-t border-slate-200">
                        {{ $coves->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
