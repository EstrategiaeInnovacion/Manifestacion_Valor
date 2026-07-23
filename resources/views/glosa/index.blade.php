<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 tracking-tight flex items-center gap-2.5">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    Glosa Aduanera & Data Stage (SAT / ANAM)
                </h2>
                <p class="text-xs text-slate-500 mt-1">Plataforma de ingesta, descompresión en memoria, análisis de compliance y exportación en Excel (26 Bóvedas)</p>
            </div>
            <a href="#upload-section" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-200 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                Cargar ZIP Data Stage
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Alertas Flash --}}
            @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-xs font-bold">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="text-xs font-bold">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            @endif

            {{-- SECCIÓN DE FILTROS GLOBALES --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filtros de Análisis del Dashboard
                    </h3>
                    <button onclick="loadMetrics()" class="text-xs text-indigo-600 font-bold hover:underline flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Actualizar Métricas
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha Inicial</label>
                        <input type="date" id="filter-start-date" class="w-full text-xs rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha Final</label>
                        <input type="date" id="filter-end-date" class="w-full text-xs rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">RFC Contribuyente</label>
                        <input type="text" id="filter-rfc" placeholder="Ej: ABC123456789" class="w-full text-xs rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tipo Operación</label>
                        <select id="filter-tipo-op" class="w-full text-xs rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Todas (Imp / Exp)</option>
                            <option value="1">1 - Importación</option>
                            <option value="2">2 - Exportación</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Aduana Despacho</label>
                        <input type="text" id="filter-aduana" placeholder="Ej: 470, 160" class="w-full text-xs rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>

            {{-- TARJETAS KPI DASHBOARD --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                {{-- KPI 1: Operaciones Totales --}}
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Operaciones</p>
                        <h4 id="kpi-total-ops" class="text-2xl font-black text-slate-800 mt-1">0</h4>
                        <p id="kpi-imp-exp" class="text-xs text-slate-500 mt-1 font-medium">0 Imp / 0 Exp</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                {{-- KPI 2: Valor Comercial USD --}}
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Valor Comercial USD</p>
                        <h4 id="kpi-valor-usd" class="text-2xl font-black text-emerald-600 mt-1">$0.00</h4>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Acumulado Bóvedas 505 / 551</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                {{-- KPI 3: Total Impuestos Pagados --}}
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Impuestos Pagados (MXN)</p>
                        <h4 id="kpi-total-impuestos" class="text-2xl font-black text-blue-600 mt-1">$0.00</h4>
                        <p class="text-xs text-slate-500 mt-1 font-medium">IGI, IVA, DTA (Bóveda 510/557)</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                {{-- KPI 4: Compliance / Rectificaciones --}}
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Compliance / Rectificaciones</p>
                        <h4 id="kpi-total-rect" class="text-2xl font-black text-amber-600 mt-1">0</h4>
                        <p id="kpi-tasa-rect" class="text-xs text-amber-600 font-bold mt-1">0% Tasa</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
            </div>

            {{-- GRÁFICOS DASHBOARD --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Tendencia Mensual --}}
                <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            Tendencia de Operaciones por Mes
                        </h3>
                    </div>
                    <div class="h-64">
                        <canvas id="chart-tendencia"></canvas>
                    </div>
                </div>

                {{-- Distribución por Aduana --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Operaciones por Aduana
                        </h3>
                    </div>
                    <div class="h-64">
                        <canvas id="chart-aduanas"></canvas>
                    </div>
                </div>

                {{-- Top 10 Fracciones Arancelarias --}}
                <div class="lg:col-span-3 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            Top 10 Fracciones Arancelarias Más Frecuentes (Bóveda 551)
                        </h3>
                    </div>
                    <div class="h-72">
                        <canvas id="chart-top-fracciones"></canvas>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN DE INGESTA ZIP (DRAG & DROP) --}}
            <div id="upload-section" class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h5l2 2h7a2 2 0 012 2v1M5 8v10a2 2 0 002 2h14a2 2 0 002-2V8m-9 4h4"></path></svg>
                        Cargar Archivo Data Stage ZIP Mensual
                    </h3>
                    <p class="text-xs text-slate-500">Selecciona o arrastra el paquete comprimido emitido por el SAT / ANAM (ej: <code class="bg-slate-100 px-2 py-0.5 rounded text-indigo-600 font-mono">1920833_solicitudes.zip</code>)</p>
                </div>

                <form action="{{ route('glosa.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div id="drop-zone" class="border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-3xl p-8 text-center bg-slate-50/50 hover:bg-indigo-50/30 transition-all cursor-pointer">
                        <input type="file" name="zip_file" id="zip_file" class="hidden" accept=".zip" onchange="handleFileSelect(this)">
                        <div class="space-y-3">
                            <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mx-auto shadow-inner">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            </div>
                            <div>
                                <p id="file-label-title" class="text-sm font-bold text-slate-700">Arrastra tu archivo ZIP aquí o haz clic para examinar</p>
                                <p id="file-label-sub" class="text-xs text-slate-400 mt-1">Formatos soportados: .ZIP (Máx: 100 MB)</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button type="submit" id="submit-btn" disabled class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg> Procesar e Ingestar Bóvedas
                        </button>
                    </div>
                </form>
            </div>

            {{-- HISTORIAL DE PAQUETES PROCESADOS & EXPORTACIÓN EXCEL 26 HOJAS --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Historial de Paquetes Procesados y Exportación Excel (26 Hojas)
                        </h3>
                        <p class="text-xs text-slate-500">Descarga el libro .xlsx estructurado por bóvedas con encabezados descriptivos y filtros activados</p>
                    </div>
                    @if($imports->count() > 0)
                    <form action="{{ route('glosa.purge-all') }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar TODOS los paquetes Data Stage y sus registros acumulados? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition-all">
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Limpiar Todo el Historial
                        </button>
                    </form>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px] border-y border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Folio / Archivo</th>
                                <th class="py-3 px-4">RFC</th>
                                <th class="py-3 px-4">Rango Fechas</th>
                                <th class="py-3 px-4 text-center">Archivos</th>
                                <th class="py-3 px-4 text-center">Pedimentos</th>
                                <th class="py-3 px-4 text-center">Partidas</th>
                                <th class="py-3 px-4 text-right">Valor USD</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($imports as $imp)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h5l2 2h7a2 2 0 012 2v1M5 8v10a2 2 0 002 2h14a2 2 0 002-2V8"></path></svg>
                                        <div>
                                            <p>{{ $imp->original_filename }}</p>
                                            <p class="text-[10px] font-normal text-slate-400">Folio: {{ $imp->folio ?? 'N/A' }} | {{ $imp->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-700">{{ $imp->rfc ?? 'N/A' }}</td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    @if($imp->fecha_inicial && $imp->fecha_final)
                                        {{ $imp->fecha_inicial->format('d/m/Y') }} - {{ $imp->fecha_final->format('d/m/Y') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-indigo-600">{{ $imp->total_files }}</td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-800">{{ $imp->total_pedimentos }}</td>
                                <td class="py-3.5 px-4 text-center text-slate-600">{{ $imp->total_partidas }}</td>
                                <td class="py-3.5 px-4 text-right font-bold text-emerald-600">${{ number_format($imp->total_valor_dolares, 2) }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($imp->status === 'completed')
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full font-bold text-[10px] border border-emerald-200">Completado</span>
                                    @elseif($imp->status === 'processing')
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full font-bold text-[10px] border border-amber-200">Procesando</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-rose-50 text-rose-700 rounded-full font-bold text-[10px] border border-rose-200">Error</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if($imp->status === 'completed')
                                        <a href="{{ route('glosa.export', $imp->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg shadow-sm transition-all" title="Descargar Excel 26 Hojas">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Excel 26 Hojas
                                        </a>
                                        @endif
                                        <form action="{{ route('glosa.destroy', $imp->id) }}" method="POST" onsubmit="return confirm('¿Eliminar el paquete \'{{ $imp->original_filename }}\' y todas sus bóvedas asociadas?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center p-1.5 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white rounded-lg border border-rose-200 transition-all" title="Eliminar Paquete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    No hay archivos de Data Stage cargados previamente.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $imports->links() }}
                </div>
            </div>

        </div>
    </div>

    {{-- SCRIPTS INTERACTIVOS Y CHARTS --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let chartTendencia = null;
        let chartAduanas = null;
        let chartTopFracciones = null;

        document.addEventListener('DOMContentLoaded', () => {
            setupDragAndDrop();
            loadMetrics();
        });

        function setupDragAndDrop() {
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('zip_file');

            dropZone.addEventListener('click', () => fileInput.click());

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropZone.classList.add('border-indigo-600', 'bg-indigo-100/50');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('border-indigo-600', 'bg-indigo-100/50');
                });
            });

            dropZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect(fileInput);
                }
            });
        }

        function handleFileSelect(input) {
            const submitBtn = document.getElementById('submit-btn');
            const title = document.getElementById('file-label-title');
            const sub = document.getElementById('file-label-sub');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                title.innerHTML = `Archivo Seleccionado: <span class="text-indigo-600 font-black">${file.name}</span>`;
                sub.textContent = `Tamaño: ${(file.size / (1024 * 1024)).toFixed(2)} MB`;
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        async function loadMetrics() {
            const startDate     = document.getElementById('filter-start-date').value;
            const endDate       = document.getElementById('filter-end-date').value;
            const rfc           = document.getElementById('filter-rfc').value;
            const tipoOperacion = document.getElementById('filter-tipo-op').value;
            const aduana        = document.getElementById('filter-aduana').value;

            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (rfc) params.append('rfc', rfc);
            if (tipoOperacion) params.append('tipo_operacion', tipoOperacion);
            if (aduana) params.append('aduana', aduana);

            try {
                const res = await fetch(`/glosa/metrics?${params.toString()}`);
                const data = await res.json();

                // Actualizar KPIs
                document.getElementById('kpi-total-ops').textContent = data.kpis.total_operaciones.toLocaleString();
                document.getElementById('kpi-imp-exp').textContent = `${data.kpis.importaciones} Imp / ${data.kpis.exportaciones} Exp`;
                document.getElementById('kpi-valor-usd').textContent = `$${data.kpis.valor_comercial_usd.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                document.getElementById('kpi-total-impuestos').textContent = `$${data.kpis.total_impuestos.toLocaleString('en-US', {minimumFractionDigits: 2})}`;

                document.getElementById('kpi-total-rect').textContent = data.compliance.total_rectificaciones;
                document.getElementById('kpi-tasa-rect').textContent = `${data.compliance.tasa_rectificacion}% Tasa`;

                // Renderizar Gráficos
                renderTendenciaChart(data.charts.tendencia_mensual);
                renderAduanasChart(data.charts.por_aduana);
                renderTopFraccionesChart(data.charts.top_fracciones);
            } catch (err) {
                console.error('Error al cargar métricas del Dashboard:', err);
            }
        }

        function renderTendenciaChart(data) {
            const ctx = document.getElementById('chart-tendencia').getContext('2d');
            if (chartTendencia) chartTendencia.destroy();

            const labels = data.map(d => d.mes);
            const totals = data.map(d => d.total);

            chartTendencia = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels.length ? labels : ['Sin Datos'],
                    datasets: [{
                        label: 'Operaciones Procesadas',
                        data: totals.length ? totals : [0],
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#4F46E5',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#F1F5F9' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        function renderAduanasChart(data) {
            const ctx = document.getElementById('chart-aduanas').getContext('2d');
            if (chartAduanas) chartAduanas.destroy();

            const labels = data.map(d => `Aduana ${d.seccion_aduanera}`);
            const totals = data.map(d => d.total);

            chartAduanas = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels.length ? labels : ['Sin datos'],
                    datasets: [{
                        data: totals.length ? totals : [1],
                        backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#3B82F6', '#EC4899', '#8B5CF6', '#64748B', '#06B6D4'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10, weight: 'bold' } } }
                    }
                }
            });
        }

        function renderTopFraccionesChart(data) {
            const ctx = document.getElementById('chart-top-fracciones').getContext('2d');
            if (chartTopFracciones) chartTopFracciones.destroy();

            const labels = data.map(d => d.fraccion_arancelaria);
            const totals = data.map(d => d.total_operaciones);

            chartTopFracciones = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels.length ? labels : ['Sin Fracciones'],
                    datasets: [{
                        label: 'Frecuencia de Operaciones',
                        data: totals.length ? totals : [0],
                        backgroundColor: '#6366F1',
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#F1F5F9' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }
    </script>
</x-app-layout>
