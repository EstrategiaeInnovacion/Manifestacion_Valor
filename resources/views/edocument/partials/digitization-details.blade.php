<div class="space-y-6 animate-fade-in-up">
    
    {{-- 1. ENCABEZADO: ESTADO DEL DOCUMENTO --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
        <div class="absolute top-0 right-0 p-4 opacity-5">
            <i data-lucide="file-check" class="w-40 h-40 text-emerald-600"></i>
        </div>

        <div class="bg-emerald-600 px-6 py-4 border-b border-emerald-700 flex justify-between items-center relative z-10">
            <h3 class="text-lg font-bold text-white flex items-center">
                <i data-lucide="scan-line" class="w-5 h-5 mr-2"></i>
                Documento Digitalizado
            </h3>
            <span class="bg-emerald-800 text-emerald-100 text-xs font-mono py-1 px-3 rounded-full border border-emerald-500 shadow-sm">
                VUCEM: ACTIVO
            </span>
        </div>

        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
            
            {{-- Columna Izquierda: Datos del Folio --}}
            <div class="space-y-6">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Folio eDocument</p>
                    <div class="flex items-center">
                        <span class="text-3xl font-black text-slate-800 tracking-tight mr-3">{{ $folio }}</span>
                        <i data-lucide="copy" class="w-4 h-4 text-slate-400 cursor-pointer hover:text-emerald-600 transition-colors" onclick="navigator.clipboard.writeText('{{ $folio }}')"></i>
                    </div>
                </div>

                <div class="flex gap-6">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Fecha Consulta</p>
                        <p class="text-sm font-semibold text-slate-700">{{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tipo Archivo</p>
                        <p class="text-sm font-semibold text-slate-700">PDF / XML</p>
                    </div>
                </div>

                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-start">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 mt-0.5 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-bold text-emerald-800">Digitalización Confirmada</h4>
                            <p class="text-xs text-emerald-600 mt-1">
                                El documento se encuentra registrado correctamente en los servidores de VUCEM y está vinculado a este eDocument.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Derecha: Vista Previa / Acción --}}
            <div class="flex flex-col justify-center items-center bg-slate-50 rounded-xl border-2 border-dashed border-slate-300 p-6">
                @if(isset($files[0]))
                    <div class="bg-white p-4 rounded-lg shadow-md mb-4">
                        <i data-lucide="file-text" class="w-16 h-16 text-red-500"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-700 mb-1">{{ $files[0]['name'] }}</p>
                    <p class="text-xs text-slate-400 mb-6">{{ $files[0]['mime'] }}</p>
                    
                    <a href="{{ route('edocument.descargar', $files[0]['token']) }}" class="btn-primary w-full justify-center shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                        <i data-lucide="download" class="w-5 h-5 mr-2"></i>
                        Descargar Documento Original
                    </a>
                @else
                    <div class="text-center opacity-50">
                        <i data-lucide="file-question" class="w-12 h-12 text-slate-400 mx-auto mb-2"></i>
                        <p class="text-sm">No se pudo recuperar el binario.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 2. LISTA DE ARCHIVOS ADICIONALES (Si hubiera más de uno) --}}
    @if(count($files) > 1)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-[#001a4d] flex items-center text-sm">
                    <i data-lucide="paperclip" class="w-4 h-4 mr-2 text-slate-500"></i>
                    Otros Archivos Adjuntos ({{ count($files) - 1 }})
                </h3>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach($files as $index => $file)
                    @if($index > 0) {{-- Saltamos el primero porque ya está arriba --}}
                        <li class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                            <div class="flex items-center">
                                <div class="bg-slate-100 p-2 rounded-lg text-slate-500 mr-3">
                                    <i data-lucide="file" class="w-4 h-4"></i>
                                </div>
                                <span class="text-sm text-slate-700 font-medium">{{ $file['name'] }}</span>
                            </div>
                            <a href="{{ route('edocument.descargar', $file['token']) }}" class="text-emerald-600 hover:text-emerald-800 text-xs font-bold flex items-center">
                                Descargar <i data-lucide="arrow-right" class="w-3 h-3 ml-1"></i>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif
</div>