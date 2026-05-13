{{-- Muestra estadísticas de un PDF (antes/después de conversión VUCEM) --}}
@php
    $dpi    = $stats['dpi'] ?? [];
    $hasGoodDpi = $dpi['valid'] ?? false;
    $dpiCount   = $dpi['total_images'] ?? null;
@endphp

<dl class="space-y-3">
    {{-- Tamaño --}}
    <div class="flex items-center justify-between">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tamaño</dt>
        <dd class="text-sm font-bold {{ $stats['size_mb'] <= 3 ? 'text-green-700' : 'text-red-700' }}">
            {{ $stats['size_mb'] }} MB
            <span class="text-xs font-normal text-slate-400">({{ number_format($stats['size_bytes']) }} bytes)</span>
            @if($stats['size_mb'] <= 3)
                <span class="ml-1 text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">✓ OK</span>
            @else
                <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full">✗ Excede 3 MB</span>
            @endif
        </dd>
    </div>

    {{-- Versión PDF --}}
    <div class="flex items-center justify-between">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Versión PDF</dt>
        <dd class="text-sm font-bold {{ $stats['version'] === '1.4' ? 'text-green-700' : 'text-red-700' }}">
            {{ $stats['version'] ?? '—' }}
            @if($stats['version'] === '1.4')
                <span class="ml-1 text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">✓ OK</span>
            @else
                <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full">✗ Debe ser 1.4</span>
            @endif
        </dd>
    </div>

    {{-- Encriptación --}}
    <div class="flex items-center justify-between">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Encriptación</dt>
        <dd class="text-sm font-bold {{ !$stats['encrypted'] ? 'text-green-700' : 'text-red-700' }}">
            {{ $stats['encrypted'] ? 'Sí (protegido)' : 'No' }}
            @if(!$stats['encrypted'])
                <span class="ml-1 text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">✓ OK</span>
            @else
                <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full">✗ Protegido</span>
            @endif
        </dd>
    </div>

    {{-- DPI --}}
    <div class="flex items-start justify-between">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider pt-0.5">DPI imágenes</dt>
        <dd class="text-sm font-bold text-right">
            @if(isset($dpi['error']))
                <span class="text-amber-600">{{ $dpi['error'] }}</span>
            @elseif($dpiCount === 0)
                <span class="text-blue-600">Sin imágenes rasterizadas
                    <span class="ml-1 text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full">Solo vectores</span>
                </span>
            @elseif($hasGoodDpi)
                <span class="text-green-700">300 DPI ({{ $dpiCount }} img)
                    <span class="ml-1 text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">✓ OK</span>
                </span>
            @elseif(is_null($dpiCount))
                <span class="text-slate-400 text-xs">pdfimages no disponible</span>
            @else
                <span class="text-red-700">
                    {{ $dpi['invalid_count'] ?? 0 }} de {{ $dpiCount }} imágenes fuera de 300 DPI
                    <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full">✗</span>
                </span>
                @if(!empty($dpi['invalid_images']))
                    <div class="mt-1 text-xs text-slate-500 font-normal">
                        @foreach(array_slice($dpi['invalid_images'], 0, 5) as $img)
                            Pág {{ $img['page'] }}: {{ $img['x_dpi'] }}×{{ $img['y_dpi'] }} DPI &nbsp;
                        @endforeach
                        @if(count($dpi['invalid_images']) > 5)
                            <span>… y {{ count($dpi['invalid_images']) - 5 }} más</span>
                        @endif
                    </div>
                @endif
            @endif
        </dd>
    </div>
</dl>
