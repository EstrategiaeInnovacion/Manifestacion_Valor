<x-app-layout>
    <x-slot name="title">Glosa Aduanera & Data Stage</x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- HEADER DEL MÓDULO --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <div>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 mr-1"></i>
                        Regresar al Dashboard
                    </a>
                    <h2 class="font-black text-2xl text-slate-800 tracking-tight flex items-center gap-2.5">
                        <i data-lucide="database" class="w-7 h-7 text-indigo-600"></i>
                        Glosa Aduanera & Data Stage (SAT / ANAM)
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Plataforma de ingesta, descompresión en memoria, análisis de compliance y exportación en Excel (26 Bóvedas)</p>
                </div>
                <a href="#upload-section" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-200 transition-all">
                    <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                    Cargar ZIP Data Stage
                </a>
            </div>

            {{-- Alertas Flash --}}
            @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                    <span class="text-xs font-semibold">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            @endif

            @if(session('error') || $errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                    <span class="text-xs font-semibold">{{ session('error') ?? $errors->first() }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            @endif

            {{-- 1. ZONA DE CARGA DRAG & DROP --}}
            <div id="upload-section" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="file-archive" class="w-5 h-5 text-indigo-600"></i>
                            Ingesta Mensual de Paquete Data Stage (.zip)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Sube el archivo comprimido mensual emitido por el SAT/ANAM (contiene los 26 archivos planos .asc M3)</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Procesamiento Volátil en Memoria
                    </span>
                </div>

                <form action="{{ route('glosa.upload') }}" method="POST" enctype="multipart/form-data" id="glosa-upload-form" class="space-y-4">
                    @csrf
                    <div id="drop-zone" class="border-2 border-dashed border-indigo-200 hover:border-indigo-500 bg-indigo-50/40 hover:bg-indigo-50/80 transition-all rounded-2xl p-8 text-center cursor-pointer relative group">
                        <input type="file" name="zip_file" id="zip_file" accept=".zip" class="hidden" onchange="handleFileSelect(this)">
                        
                        <div class="flex flex-col items-center justify-center space-y-3 pointer-events-none">
                            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-200 group-hover:scale-110 transition-transform">
                                <i data-lucide="upload-cloud" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800" id="file-label-title">Arrastra tu archivo .ZIP aquí o <span class="text-indigo-600 underline">haz clic para examinar</span></p>
                                <p class="text-xs text-slate-400 mt-1" id="file-label-sub">Soporta archivos hasta 100MB (Ejemplo: 1920833_solicitudes.zip)</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" id="submit-btn" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-200 transition-all flex items-center gap-2 disabled:opacity-50" disabled>
                            <i data-lucide="zap" class="w-4 h-4"></i>
                            Procesar & Estructurar Data Stage
                        </button>
                    </div>
                </form>
            </div>

            {{-- 2. FILTROS GLOBALES DEL DASHBOARD --}}
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="filter" class="w-5 h-5 text-indigo-600"></i>
                        <h4 class="text-sm font-bold text-slate-800">Filtros Globales del Dashboard</h4>
                    </div>
                    <button type="button" onclick="loadMetrics()" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Actualizar Métricas
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Fecha Inicio</label>
                        <input type="date" id="filter-start-date" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" onchange="loadMetrics()">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Fecha Fin</label>
                        <input type="date" id="filter-end-date" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" onchange="loadMetrics()">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">RFC Contribuyente</label>
                        <select id="filter-rfc" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" onchange="loadMetrics()">
                            <option value="">-- Todos los RFCs --</option>
                            @foreach($rfcs as $r)
                                <option value="{{ $r }}">{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Tipo Operación</label>
                        <select id="filter-tipo-op" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" onchange="loadMetrics()">
                            <option value="">-- Importación & Exportación --</option>
                            <option value="1">1 - Importaciones</option>
                            <option value="2">2 - Exportaciones</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Aduana Despacho</label>
                        <select id="filter-aduana" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" onchange="loadMetrics()">
                            <option value="">-- Todas las Aduanas --</option>
                            @foreach($aduanas as $a)
                                <option value="{{ $a }}">Aduana {{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 3. TARJETAS KPI PRINCIPALES --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- KPI 1: Operaciones Totales --}}
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <i data-lucide="layers" class="w-6 h-6"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full" id="kpi-imp-exp">0 Imp / 0 Exp</span>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Operaciones Totales</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-1" id="kpi-total-ops">0</h3>
                </div>

                {{-- KPI 2: Valor Comercial USD --}}
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full">USD</span>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Valor Comercial Total</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-1" id="kpi-valor-usd">$0.00</h3>
                </div>

                {{-- KPI 3: Impuestos Pagados --}}
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <i data-lucide="receipt" class="w-6 h-6"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full">Bóveda 510 & 557</span>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Impuestos Pagados (IVA/IGI/DTA)</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-1" id="kpi-total-impuestos">$0.00</h3>
                </div>

                {{-- KPI 4: Compliance & Rectificaciones Risk --}}
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <i data-lucide="shield-alert" class="w-6 h-6"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 bg-amber-100 text-amber-700 rounded-full" id="kpi-tasa-rect">0% Tasa</span>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rectificaciones (Bóveda 701)</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-1" id="kpi-total-rect">0</h3>
                </div>
            </div>

            {{-- 4. GRÁFICOS ANALÍTICOS --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Gráfico 1: Tendencias Mensuales --}}
                <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="trending-up" class="w-4 h-4 text-indigo-600"></i>
                            Tendencia de Operaciones por Mes
                        </h4>
                    </div>
                    <div class="h-64">
                        <canvas id="chart-tendencia"></canvas>
                    </div>
                </div>

                {{-- Gráfico 2: Distribución por Aduana --}}
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-indigo-600"></i>
                            Operaciones por Aduana
                        </h4>
                    </div>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="chart-aduanas"></canvas>
                    </div>
                </div>
            </div>

            {{-- Top 10 Fracciones Arancelarias --}}
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="bar-chart-2" class="w-4 h-4 text-indigo-600"></i>
                        Top 10 Fracciones Arancelarias Más Frecuentes (Bóveda 551)
                    </h4>
                </div>
                <div class="h-72">
                    <canvas id="chart-top-fracciones"></canvas>
                </div>
            </div>

            {{-- 5. HISTORIAL DE ARCHIVOS CARGADOS Y EXPORTACIÓN A EXCEL DE 26 HOJAS --}}
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600"></i>
                            Historial de Paquetes Procesados y Exportación Excel (26 Hojas)
                        </h3>
                        <p class="text-xs text-slate-500">Descarga el libro .xlsx estructurado por bóvedas con encabezados descriptivos y filtros activados</p>
                    </div>
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
                                        <i data-lucide="archive" class="w-4 h-4 text-indigo-500"></i>
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
                                    @if($imp->status === 'completed')
                                    <a href="{{ route('glosa.export', $imp->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg shadow-sm transition-all">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i> Excel 26 Hojas
                                    </a>
                                    @else
                                    <span class="text-slate-400 text-[10px]">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                    No hay archivos de Data Stage cargados previamente.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginación --}}
                <div class="mt-4">
                    {{ $imports->links() }}
                </div>
            </div>

        </div>
    </div>

    {{-- SCRIPTS INTERACTIVOS Y CHARTS --}}
    <script>
        let chartTendencia = null;
        let chartAduanas = null;
        let chartTopFracciones = null;

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
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
