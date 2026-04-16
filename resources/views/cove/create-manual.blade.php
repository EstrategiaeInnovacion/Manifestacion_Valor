<x-app-layout>
    <x-slot name="title">Nuevo COVE</x-slot>

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- NAVEGACIÓN PRINCIPAL (Para igualar al Dashboard) --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Módulo COVE</span>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Usuario Conectado</p>
                            <p class="text-sm font-black text-[#003399]">{{ auth()->user()->full_name ?? auth()->user()->name }}</p>
                        </div>
                        <div class="user-dropdown">
                            <div id="avatarButton" class="avatar-button h-10 w-10 bg-[#001a4d] rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                {{ substr(auth()->user()->full_name ?? auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

    <div class="py-12 bg-[#F8FAFC]" x-data="coveForm()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <div class="flex items-center gap-4 mb-6">
                    <a href="{{ route('cove.pendientes') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                        Volver al Dashboard
                    </a>
                    <span class="text-slate-300">|</span>
                    <a href="{{ route('cove.select-applicant') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors">
                        <i data-lucide="repeat" class="w-4 h-4 mr-2"></i>
                        Cambiar Solicitante
                    </a>
                </div>

                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Crear <span class="text-[#003399]">COVE</span>
                </h2>
                <p class="text-slate-500 mt-2">Complete el formulario para el Solicitante: <strong class="text-[#003399]">{{ $applicant->applicant_rfc }}</strong></p>
            </div>
            
            <!-- Navigation Steps -->
            <div class="mb-8">
                <nav aria-label="Progress">
                    <ol role="list" class="flex items-center justify-center">
                        <!-- Paso 1 -->
                        <li class="relative pr-8 sm:pr-20">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="h-0.5 w-full" :class="tab === 'datos' ? 'bg-slate-200' : 'bg-emerald-500'"></div>
                            </div>
                            <button @click="tab = 'datos'" class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all"
                                :class="tab === 'datos' ? 'bg-white border-[#001a4d] text-[#001a4d] ring-4 ring-blue-50' : (tab === 'facturas' || tab === 'firma' ? 'bg-emerald-500 border-emerald-500 text-white hover:bg-emerald-600' : 'bg-white border-slate-300 text-slate-500')">
                                <i data-lucide="info" class="w-5 h-5"></i>
                            </button>
                            <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 w-max text-xs font-bold uppercase tracking-wider" :class="tab === 'datos' ? 'text-[#001a4d]' : 'text-slate-400'">Generales</span>
                        </li>

                        <!-- Paso 2 -->
                        <li class="relative pr-8 sm:pr-20">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="h-0.5 w-full" :class="tab === 'firma' ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                            </div>
                            <button @click="tab = 'facturas'" class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all"
                                :class="tab === 'facturas' ? 'bg-white border-[#001a4d] text-[#001a4d] ring-4 ring-blue-50' : (tab === 'firma' ? 'bg-emerald-500 border-emerald-500 text-white hover:bg-emerald-600' : 'bg-white border-slate-300 text-slate-500')">
                                <i data-lucide="package" class="w-5 h-5"></i>
                            </button>
                            <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 w-max text-xs font-bold uppercase tracking-wider" :class="tab === 'facturas' ? 'text-[#001a4d]' : 'text-slate-400'">Facturas</span>
                        </li>

                        <!-- Paso 3 -->
                        <li class="relative">
                            <button @click="tab = 'firma'" class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all"
                                :class="tab === 'firma' ? 'bg-white border-[#001a4d] text-[#001a4d] ring-4 ring-blue-50' : 'bg-white border-slate-300 text-slate-500'">
                                <i data-lucide="shield-check" class="w-5 h-5"></i>
                            </button>
                            <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 w-max text-xs font-bold uppercase tracking-wider" :class="tab === 'firma' ? 'text-[#001a4d]' : 'text-slate-400'">Firma</span>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Main Content Area -->
            <div class="mt-12 bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
                <div class="p-8">
                    
                    <!-- ALERTAS -->
                    <div x-show="message" class="mb-6 p-4 rounded-xl flex items-start gap-3 border" :class="isError ? 'bg-red-50 border-red-100 text-red-800' : 'bg-emerald-50 border-emerald-100 text-emerald-800'">
                        <i :data-lucide="isError ? 'alert-circle' : 'check-circle'" class="w-5 h-5 mt-0.5"></i>
                        <p x-text="message" class="text-sm font-semibold"></p>
                    </div>

                    <!-- TAB 1: DATOS GENERALES -->
                    <div x-show="tab === 'datos'" x-cloak>
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-[#003399]">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-[#001a4d]">Información de Operación</h3>
                                <p class="text-sm text-slate-500 font-medium">Capture los detalles básicos del documento.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tipo de Operación</label>
                                <select x-model="payload.tipoOperacion" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-amber-400 outline-none cursor-pointer">
                                    <option value="TOL">TOL</option>
                                    <option value="TCI">TCI</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Patente Aduanal</label>
                                <input type="text" x-model="payload.patenteAduanal" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-amber-400 outline-none" placeholder="Ej: 1234">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tipo de Figura</label>
                                <select x-model="payload.tipoFigura" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-amber-400 outline-none cursor-pointer">
                                    <option value="1">1 - Agente Aduanal</option>
                                    <option value="2">2 - Apoderado Aduanal</option>
                                    <option value="3">3 - Mandatario</option>
                                    <option value="4">4 - Importador / Exportador</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo de Contacto</label>
                                <input type="email" x-model="payload.correoElectronico" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-amber-400 outline-none">
                            </div>
                        </div>

                        <div class="mt-10 flex justify-end">
                            <button @click="saveDraft(false); tab = 'facturas'; refreshIcons();" type="button" class="px-8 py-3 bg-[#001a4d] hover:bg-[#003399] text-white font-bold text-sm rounded-xl transition-all shadow-md flex items-center gap-2">
                                Continuar <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: FACTURAS Y MERCANCÍAS -->
                    <div x-show="tab === 'facturas'" x-cloak>
                        <div class="flex justify-between items-center mb-8 pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                                    <i data-lucide="receipt" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-[#001a4d]">Listado de Facturas</h3>
                                    <p class="text-sm text-slate-500 font-medium">Añada las facturas y sus materias correspondientes.</p>
                                </div>
                            </div>
                            <button @click="addFactura(); refreshIcons();" type="button" class="px-5 py-2.5 bg-slate-100 text-[#001a4d] hover:bg-slate-200 font-bold text-sm rounded-xl transition-colors flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i> Factura
                            </button>
                        </div>

                        <template x-for="(factura, idx) in payload.facturas" :key="idx">
                            <div class="border-2 border-slate-100 rounded-2xl p-6 mb-8 bg-slate-50/50 relative group">
                                <button @click="payload.facturas.splice(idx, 1)" class="absolute -top-3 -right-3 w-8 h-8 bg-red-100 text-red-600 rounded-full flex items-center justify-center hover:bg-red-600 hover:text-white transition-colors shadow-sm opacity-0 group-hover:opacity-100" title="Eliminar Factura">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="bg-[#001a4d] text-white w-6 h-6 rounded flex items-center justify-center text-xs font-bold" x-text="idx + 1"></div>
                                    <h4 class="font-bold text-slate-700 uppercase tracking-widest text-sm">Detalle de Factura</h4>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Número de Factura</label>
                                        <input type="text" x-model="factura.numeroFactura" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-amber-400 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Cert. Origen (0/1)</label>
                                        <input type="text" x-model="factura.certificadoOrigen" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-amber-400 outline-none text-center">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Subdivisión (0/1)</label>
                                        <input type="text" x-model="factura.subdivision" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-amber-400 outline-none text-center">
                                    </div>
                                </div>

                                <!-- Emisor -->
                                <div class="bg-white p-5 rounded-xl border border-slate-200 mb-6 shadow-sm">
                                   <label class="block text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                       <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                       Datos del Emisor
                                   </label>
                                   <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <input type="text" x-model="factura.emisor.identificacion" placeholder="RFC / Tax ID del Emisor" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-emerald-400 outline-none">
                                        </div>
                                        <div>
                                            <input type="text" x-model="factura.emisor.nombre" placeholder="Razón Social del Emisor" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-[#001a4d] font-semibold focus:ring-2 focus:ring-emerald-400 outline-none">
                                        </div>
                                   </div>
                                </div>

                                <!-- Mercancías -->
                                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                                    <div class="bg-slate-100/50 p-4 border-b border-slate-200 flex justify-between items-center">
                                        <label class="block text-xs font-bold text-indigo-600 uppercase tracking-widest flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                            Partidas / Mercancías
                                        </label>
                                        <button @click="addMercancia(factura)" type="button" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-white border border-indigo-200 px-3 py-1.5 rounded-lg shadow-sm transition-colors">
                                            + Añadir Mercancía
                                        </button>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-white border-b border-slate-100">
                                                    <th class="p-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Descripción Gral.</th>
                                                    <th class="p-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-24">UM</th>
                                                    <th class="p-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-24">Cant.</th>
                                                    <th class="p-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-32">V.Unitario (USD)</th>
                                                    <th class="p-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-32">V.Total</th>
                                                    <th class="p-3 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-slate-50/30">
                                                <template x-for="(mercancia, midx) in factura.mercancias" :key="midx">
                                                    <tr class="hover:bg-slate-50 transition-colors">
                                                        <td class="p-2">
                                                            <input type="text" x-model="mercancia.descripcionGenerica" class="w-full px-3 py-2 bg-white border border-slate-200 rounded text-xs focus:ring-1 focus:ring-indigo-400 outline-none" placeholder="Descripción...">
                                                        </td>
                                                        <td class="p-2">
                                                            <input type="text" x-model="mercancia.claveUnidadMedida" class="w-full px-3 py-2 bg-white border border-slate-200 rounded text-xs focus:ring-1 focus:ring-indigo-400 outline-none text-center" placeholder="01">
                                                        </td>
                                                        <td class="p-2">
                                                            <input type="number" step="0.001" x-model="mercancia.cantidad" class="w-full px-3 py-2 bg-white border border-slate-200 rounded text-xs focus:ring-1 focus:ring-indigo-400 outline-none text-right">
                                                        </td>
                                                        <td class="p-2">
                                                            <input type="number" step="0.001" x-model="mercancia.valorUnitario" class="w-full px-3 py-2 bg-white border border-slate-200 rounded text-xs focus:ring-1 focus:ring-indigo-400 outline-none text-right">
                                                        </td>
                                                        <td class="p-2">
                                                            <div class="px-3 py-2 bg-slate-100 border border-slate-200 rounded text-xs font-semibold text-slate-600 text-right">
                                                                <span x-text="(mercancia.cantidad * mercancia.valorUnitario).toFixed(3)"></span>
                                                            </div>
                                                        </td>
                                                        <td class="p-2 text-center">
                                                            <button @click="factura.mercancias.splice(midx, 1)" type="button" class="text-slate-400 hover:text-red-500 transition-colors p-1" title="Eliminar partida">
                                                              <svg class="w-5 h-5 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </template>

                        <div class="mt-10 flex justify-between">
                            <button @click="tab = 'datos'; refreshIcons();" type="button" class="px-8 py-3 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition-colors flex items-center gap-2">
                                <i data-lucide="arrow-left" class="w-4 h-4"></i> Atrás
                            </button>
                            <button @click="saveDraft(false); tab = 'firma'; refreshIcons();" type="button" class="px-8 py-3 bg-[#001a4d] hover:bg-[#003399] text-white font-bold text-sm rounded-xl transition-all shadow-md flex items-center gap-2">
                                Revisión y Firma <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: FIRMA Y ENVÍO -->
                    <div x-show="tab === 'firma'" x-cloak class="max-w-xl mx-auto py-6">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-sm">
                                <i data-lucide="shield-check" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-2xl font-black text-[#001a4d] mb-2">Transmisión a VUCEM</h3>
                            <p class="text-slate-500 font-medium">Firma electrónica usando el certificado de <strong class="text-[#003399]">{{ $applicant->applicant_rfc }}</strong></p>
                        </div>
                        
                        <div class="p-5 bg-indigo-50 border border-indigo-100 rounded-2xl mb-8 flex gap-4 items-start">
                            <i data-lucide="info" class="w-6 h-6 text-indigo-500 shrink-0 mt-0.5"></i>
                            <div class="text-sm text-indigo-900 font-medium leading-relaxed">
                                Al hacer clic en "Firmar y Transmitir", la operación será encolada y procesada asíncronamente con VUCEM. Deberás verificar el estatus final e imprimir tu Acuse en la pestaña de <strong>Pendientes</strong> o <strong>Completadas</strong>.
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 text-center">Contraseña de Clave Privada (.KEY)</label>
                            <input type="password" x-model="privateKeyPassword" class="w-full px-4 py-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-center text-lg text-[#001a4d] font-bold focus:ring-0 focus:border-amber-400 outline-none transition-colors tracking-[0.2em]" placeholder="••••••••">
                        </div>

                         <div class="flex flex-col-reverse sm:flex-row justify-between items-center gap-4 pt-6 border-t border-slate-100">
                            <button @click="tab = 'facturas'; refreshIcons();" type="button" class="w-full sm:w-auto px-8 py-3.5 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition-colors">
                                Volver a Facturas
                            </button>
                            <button @click="firmarYEnviar()" type="button" :disabled="loading" :class="{'opacity-75 cursor-not-allowed': loading}" class="w-full sm:w-auto px-8 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm rounded-xl transition-all shadow-lg flex items-center justify-center gap-2">
                                <svg x-show="loading" class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <i x-show="!loading" data-lucide="send-to-back" class="w-5 h-5"></i>
                                <span x-text="loading ? 'Procesando...' : 'Firmar y Transmitir'"></span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('coveForm', () => ({
                tab: 'datos',
                loading: false,
                message: '',
                isError: false,
                coveId: {{ $coveDocument ? $coveDocument->id : 'null' }},
                applicantId: {{ $applicant->id }},
                privateKeyPassword: '',
                
                // Estructura VUCEM simplificada a lo configurado
                payload: {
                    tipoOperacion: '{{ $coveDocument->tipo_operacion ?? "TOL" }}',
                    patenteAduanal: '{{ $coveDocument->patente_aduanal ?? "" }}',
                    tipoFigura: '1',
                    correoElectronico: '{{ $applicant->applicant_email }}',
                    facturas: @json($coveDocument ? ($coveDocument->payload[0]['facturas'] ?? []) : [])
                },

                init() {
                    // Si no hay facturas, inicializar una por defecto
                    if (this.payload.facturas.length === 0) {
                        this.addFactura();
                    }
                    this.$nextTick(() => { this.refreshIcons() });
                },

                refreshIcons() {
                    if (window.lucide) {
                        setTimeout(() => lucide.createIcons(), 50);
                    }
                },

                addFactura() {
                    this.payload.facturas.push({
                        numeroFactura: '',
                        certificadoOrigen: '0',
                        subdivision: '0',
                        emisor: { tipoIdentificador: '1', identificacion: '', nombre: '' },
                        destinatario: { tipoIdentificador: '1', identificacion: '', nombre: '' },
                        mercancias: []
                    });
                    this.refreshIcons();
                },

                addMercancia(factura) {
                    factura.mercancias.push({
                        descripcionGenerica: '',
                        claveUnidadMedida: '',
                        cantidad: 1,
                        valorUnitario: 0,
                        valorTotal: 0,
                        tipoMoneda: 'USD'
                    });
                    this.refreshIcons();
                },

                async saveDraft(showMsg = true) {
                    try {
                        const response = await fetch('/cove/save-draft/' + this.applicantId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                cove_id: this.coveId,
                                tipo_operacion: this.payload.tipoOperacion,
                                patente_aduanal: this.payload.patenteAduanal,
                                // Envolvemos payload en un array para simular la vista "comprobantes" del WS
                                payload: [this.payload] 
                            })
                        });

                        const result = await response.json();
                        
                        if (result.success) {
                            this.coveId = result.cove_id;
                            if (showMsg) this.showMessage('Borrador guardado exitosamente.', false);
                        } else {
                            this.showMessage('Error al guardar borrador automáticamente.', true);
                        }
                    } catch (error) {
                        this.showMessage('Hubo un problema de conexión al guardar el borrador.', true);
                    }
                },

                async firmarYEnviar() {
                    if (!this.privateKeyPassword) {
                        this.showMessage('Por favor ingresa la contraseña de la e.firma.', true);
                        return;
                    }

                    this.loading = true;
                    this.message = '';
                    
                    // Guardar borrador antes de enviar
                    await this.saveDraft(false);

                    try {
                        const response = await fetch('/cove/firmar-enviar/' + this.applicantId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                cove_id: this.coveId,
                                rfc: '{{ $applicant->applicant_rfc }}',
                                password: this.privateKeyPassword
                            })
                        });

                        const result = await response.json();
                        
                        this.loading = false;
                        if (result.success) {
                            this.showMessage(result.message, false);
                            setTimeout(() => {
                                window.location.href = '{{ route("cove.pendientes") }}';
                            }, 2000);
                        } else {
                            this.showMessage(result.message || 'Error en VUCEM. Revisa que tu contraseña sea correcta.', true);
                        }
                    } catch (error) {
                        this.loading = false;
                        this.showMessage('Error fatal de conectividad. Intenta de nuevo.', true);
                    }
                    this.refreshIcons();
                },

                showMessage(msg, isErr) {
                    this.message = msg;
                    this.isError = isErr;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    this.refreshIcons();
                }
            }));
        });
    </script>
    </div>
</x-app-layout>
