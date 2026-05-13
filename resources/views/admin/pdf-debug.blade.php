<x-app-layout>
    <x-slot name="title">Debug Digitalizador VUCEM</x-slot>

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navbar --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Debug — Digitalizador VUCEM</span>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Super Administrador</p>
                            <p class="text-sm font-black text-[#003399]">{{ auth()->user()->full_name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-4">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>
                <h1 class="text-3xl font-black text-[#001a4d]">Debug — Digitalizador VUCEM</h1>
                <p class="text-slate-500 mt-1">Verifica el estado de las herramientas y prueba la conversión de PDF al formato VUCEM.</p>
            </div>

            {{-- ── ESTADO DE HERRAMIENTAS ─────────────────────────────── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-[#001a4d] mb-4 flex items-center gap-2">
                    <i data-lucide="settings-2" class="w-5 h-5 text-slate-400"></i>
                    Estado de Herramientas
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- OS --}}
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Sistema Operativo</p>
                        <p class="text-sm font-semibold text-slate-700">{{ $toolsInfo['os']['type'] }}</p>
                        <p class="text-xs text-slate-400">{{ $toolsInfo['os']['php_os'] }}</p>
                    </div>

                    {{-- Ghostscript --}}
                    @php $gsOk = $toolsInfo['ghostscript']['available']; @endphp
                    <div class="rounded-xl border p-4 {{ $gsOk ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full {{ $gsOk ? 'bg-green-500' : 'bg-red-500' }}"></span>
                            <p class="text-xs font-bold {{ $gsOk ? 'text-green-700' : 'text-red-700' }} uppercase tracking-wider">Ghostscript</p>
                        </div>
                        <p class="text-xs text-slate-600 break-all">{{ $toolsInfo['ghostscript']['path'] ?? '— no encontrado —' }}</p>
                        @if(!$gsOk)
                            <p class="text-xs text-red-600 mt-1 font-semibold">⚠ Sin Ghostscript la conversión no funciona</p>
                        @endif
                    </div>

                    {{-- pdfimages --}}
                    @php $piOk = $toolsInfo['pdfimages']['available']; @endphp
                    <div class="rounded-xl border p-4 {{ $piOk ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full {{ $piOk ? 'bg-green-500' : 'bg-amber-400' }}"></span>
                            <p class="text-xs font-bold {{ $piOk ? 'text-green-700' : 'text-amber-700' }} uppercase tracking-wider">pdfimages</p>
                        </div>
                        <p class="text-xs text-slate-600 break-all">{{ $toolsInfo['pdfimages']['path'] ?? '— no encontrado —' }}</p>
                        @if(!$piOk)
                            <p class="text-xs text-amber-700 mt-1">Opcional — sin él no se validan DPI</p>
                        @endif
                    </div>
                </div>

                {{-- Debug info adicional (Linux) --}}
                @if(isset($debugInfo['commands_tested']) && !empty($debugInfo['commands_tested']))
                    <details class="mt-4">
                        <summary class="text-xs font-semibold text-slate-500 cursor-pointer hover:text-slate-700">Ver diagnóstico de PATH del servidor</summary>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-xs border border-slate-200 rounded-lg overflow-hidden">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th class="text-left px-3 py-2 text-slate-600">Comando</th>
                                        <th class="text-left px-3 py-2 text-slate-600">Estado</th>
                                        <th class="text-left px-3 py-2 text-slate-600">Salida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($debugInfo['commands_tested'] as $key => $info)
                                        <tr class="border-t border-slate-100">
                                            <td class="px-3 py-2 font-mono text-slate-700">{{ $info['command'] }}</td>
                                            <td class="px-3 py-2">
                                                <span class="inline-block px-2 py-0.5 rounded-full font-bold {{ $info['success'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                    {{ $info['success'] ? 'OK' : 'FALLO' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 font-mono text-slate-500 break-all">{{ $info['output'] ?: $info['error'] ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endif
            </div>

            {{-- ── FORMULARIO DE PRUEBA ───────────────────────────────── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-[#001a4d] mb-4 flex items-center gap-2">
                    <i data-lucide="flask-conical" class="w-5 h-5 text-slate-400"></i>
                    Prueba de Conversión
                </h2>
                <p class="text-sm text-slate-500 mb-5">
                    Sube un PDF problemático para ver cómo estaba <strong>antes</strong> y cómo queda <strong>después</strong> de la conversión al formato VUCEM (PDF 1.4, 300 DPI, escala de grises).
                </p>

                <form method="POST" action="{{ route('admin.pdf-debug.test') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            @foreach($errors->all() as $e)
                                <p class="text-sm text-red-700">{{ $e }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Archivo PDF (máx. 20 MB)</label>
                        <input type="file" name="archivo" accept=".pdf" required
                            class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-semibold file:bg-[#003399] file:text-white hover:file:bg-[#001a4d] cursor-pointer">
                    </div>

                    <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-[#003399] text-white text-sm font-bold rounded-xl hover:bg-[#001a4d] transition-colors shadow-sm">
                        <i data-lucide="play-circle" class="w-4 h-4"></i>
                        Ejecutar Conversión de Prueba
                    </button>
                </form>
            </div>

            {{-- ── RESULTADOS ─────────────────────────────────────────── --}}
            @if(isset($before))
                {{-- Error de conversión --}}
                @if(isset($error))
                    <div class="bg-red-50 border border-red-200 rounded-2xl p-6">
                        <h3 class="text-base font-bold text-red-700 mb-2 flex items-center gap-2">
                            <i data-lucide="x-circle" class="w-5 h-5"></i>
                            Error durante la Conversión
                        </h3>
                        <p class="text-sm font-mono text-red-800 break-all">{{ $error }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- ANTES --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-base font-bold text-slate-700 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center">
                                <i data-lucide="file" class="w-3.5 h-3.5 text-amber-600"></i>
                            </span>
                            Antes — PDF Original
                        </h3>
                        @include('admin.partials.pdf-stats', ['stats' => $before])
                    </div>

                    {{-- DESPUÉS --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-base font-bold text-slate-700 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full {{ isset($after) ? 'bg-green-100' : 'bg-slate-100' }} flex items-center justify-center">
                                <i data-lucide="file-check-2" class="w-3.5 h-3.5 {{ isset($after) ? 'text-green-600' : 'text-slate-400' }}"></i>
                            </span>
                            Después — Formato VUCEM
                        </h3>
                        @if(isset($after))
                            @include('admin.partials.pdf-stats', ['stats' => $after])
                        @else
                            <p class="text-sm text-slate-400 italic">No se generó archivo de salida.</p>
                        @endif
                    </div>
                </div>

                {{-- Resultado de la conversión --}}
                @if(!empty($conversionResult))
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-base font-bold text-[#001a4d] mb-4 flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-slate-400"></i>
                            Detalle del Proceso de Conversión
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                            <div class="rounded-xl bg-slate-50 p-3 border border-slate-200 text-center">
                                <p class="text-xs text-slate-500 mb-1">Tamaño original</p>
                                <p class="text-lg font-black text-slate-700">{{ $conversionResult['original_size_mb'] ?? '—' }} MB</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 border border-slate-200 text-center">
                                <p class="text-xs text-slate-500 mb-1">Tamaño convertido</p>
                                <p class="text-lg font-black {{ isset($conversionResult['converted_size_mb']) && $conversionResult['converted_size_mb'] <= 3 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $conversionResult['converted_size_mb'] ?? '—' }} MB
                                </p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 border border-slate-200 text-center">
                                <p class="text-xs text-slate-500 mb-1">Intentos compresión</p>
                                <p class="text-lg font-black text-slate-700">{{ $conversionResult['compression_attempts'] ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 border border-slate-200 text-center">
                                <p class="text-xs text-slate-500 mb-1">Calidad final</p>
                                <p class="text-lg font-black text-slate-700">
                                    {{ isset($conversionResult['final_quality']) ? ($conversionResult['final_quality'] === 'direct' ? 'Direct (sin rasterizar)' : $conversionResult['final_quality'] . '%') : '—' }}
                                </p>
                            </div>
                        </div>

                        @if(!empty($conversionResult['messages']))
                            <div class="space-y-1 mb-3">
                                @foreach($conversionResult['messages'] as $msg)
                                    <p class="text-sm text-slate-700">{{ $msg }}</p>
                                @endforeach
                            </div>
                        @endif

                        @if(!empty($conversionResult['warnings']))
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 space-y-1">
                                @foreach($conversionResult['warnings'] as $w)
                                    <p class="text-sm text-amber-800">{{ $w }}</p>
                                @endforeach
                            </div>
                        @endif

                        @if(isset($conversionResult['auto_divided']) && $conversionResult['auto_divided'])
                            <div class="mt-3 bg-blue-50 border border-blue-200 rounded-xl p-3">
                                <p class="text-sm font-semibold text-blue-800">
                                    El PDF fue dividido automáticamente en {{ count($conversionResult['parts'] ?? []) }} partes.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Veredicto VUCEM --}}
                @php
                    $vucemOk = isset($after)
                        && $after['version'] === '1.4'
                        && !$after['encrypted']
                        && $after['size_mb'] <= 3
                        && ($after['dpi']['valid'] ?? false);
                @endphp
                <div class="rounded-2xl border p-6 {{ $vucemOk ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }}">
                    <div class="flex items-center gap-3">
                        <i data-lucide="{{ $vucemOk ? 'check-circle-2' : 'alert-triangle' }}" class="w-7 h-7 {{ $vucemOk ? 'text-green-600' : 'text-amber-600' }}"></i>
                        <div>
                            <p class="text-base font-black {{ $vucemOk ? 'text-green-800' : 'text-amber-800' }}">
                                @if($vucemOk)
                                    PDF convertido correctamente — cumple requisitos VUCEM
                                @elseif(!isset($after))
                                    No se generó archivo de salida — revisar Ghostscript
                                @else
                                    Conversión completada con advertencias — verificar los datos
                                @endif
                            </p>
                            <p class="text-xs {{ $vucemOk ? 'text-green-700' : 'text-amber-700' }} mt-0.5">
                                Versión 1.4 · 300 DPI · Escala de grises · Máx. 3 MB · Sin encriptación
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</x-app-layout>
