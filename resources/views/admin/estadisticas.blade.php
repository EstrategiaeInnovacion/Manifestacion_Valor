<x-app-layout>
    <x-slot name="title">Estadísticas del Sistema</x-slot>
    @vite(['resources/css/users-list.css', 'resources/js/users-list.js'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Panel de Estadísticas</span>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Super Administrador</p>
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

        <main class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-[#003399] transition-colors mb-3">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                        Regresar al Dashboard
                    </a>
                    <h1 class="text-3xl font-black text-[#001a4d]">Panel de Estadísticas</h1>
                    <p class="text-slate-500 mt-1 text-sm">Monitoreo de conectividad VUCEM y tickets de soporte. Datos en tiempo real.</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    Última actualización: {{ now()->format('d/m/Y H:i') }}
                    <button onclick="window.location.reload()" class="ml-2 p-1.5 text-slate-400 hover:text-[#003399] hover:bg-slate-100 rounded-lg transition-colors">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            {{-- Tab navigation --}}
            <div x-data="{ tab: 'vucem' }">

                <div class="flex gap-2 bg-white rounded-2xl p-1.5 border border-slate-200 shadow-sm mb-8 w-fit">
                    <button @click="tab='vucem'"
                        :class="tab === 'vucem' ? 'bg-[#003399] text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-200">
                        <i data-lucide="wifi" class="w-4 h-4"></i>
                        Conectividad VUCEM
                    </button>
                    <button @click="tab='tickets'"
                        :class="tab === 'tickets' ? 'bg-[#003399] text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-200">
                        <i data-lucide="ticket" class="w-4 h-4"></i>
                        Tickets de Soporte
                    </button>
                </div>

                {{-- ════════════════════════════════════════════════════════════════ --}}
                {{-- TAB: VUCEM                                                       --}}
                {{-- ════════════════════════════════════════════════════════════════ --}}
                <div x-show="tab === 'vucem'" x-transition>

                    {{-- Estado actual del sistema --}}
                    @php
                        $estadoColorMap = [
                            'VUCEM_CAIDO'  => ['bg' => 'bg-red-600',    'text' => 'text-white',     'icon' => 'wifi-off',      'label' => 'CAÍDO'],
                            'INTERMITENTE' => ['bg' => 'bg-amber-400',  'text' => 'text-amber-950', 'icon' => 'wifi',          'label' => 'INTERMITENTE'],
                            'POSIBLE_LOCAL'=> ['bg' => 'bg-orange-400', 'text' => 'text-orange-950','icon' => 'alert-triangle', 'label' => 'POSIBLE PROBLEMA LOCAL'],
                            'OPERANDO'     => ['bg' => 'bg-green-500',  'text' => 'text-white',     'icon' => 'check-circle',  'label' => 'OPERANDO'],
                        ];
                        $ec = $estadoColorMap[$estadoActual['estado']] ?? $estadoColorMap['OPERANDO'];
                    @endphp

                    {{-- Flash de confirmación --}}
                    @if(session('success_vucem'))
                    <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm font-medium">
                        <i data-lucide="check-circle" class="w-4 h-4 shrink-0 text-green-600"></i>
                        {{ session('success_vucem') }}
                    </div>
                    @endif

                    <div class="{{ $ec['bg'] }} rounded-2xl p-5 mb-4 flex flex-wrap items-center gap-4 shadow-md">
                        <div class="flex-shrink-0 bg-white bg-opacity-20 rounded-full p-3">
                            <i data-lucide="{{ $ec['icon'] }}" class="w-7 h-7 {{ $ec['text'] }}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="text-xs font-black {{ $ec['text'] }} opacity-70 uppercase tracking-widest">Estado actual (últimos 30 min)</span>
                                <span class="text-xs font-black {{ $ec['text'] }} bg-white bg-opacity-20 px-2.5 py-0.5 rounded-full">{{ $ec['label'] }}</span>
                                @if($tieneOverride)
                                <span class="text-xs font-bold bg-white bg-opacity-30 {{ $ec['text'] }} px-2 py-0.5 rounded-full">Override activo (2h)</span>
                                @endif
                            </div>
                            <p class="font-black text-lg {{ $ec['text'] }} mt-0.5">
                                {{ $estadoActual['titulo'] ?? 'VUCEM operando normalmente' }}
                            </p>
                            @if(!empty($estadoActual['mensaje']))
                            <p class="text-sm {{ $ec['text'] }} opacity-80 mt-0.5">{{ $estadoActual['mensaje'] }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-3xl font-black {{ $ec['text'] }}">{{ number_format($vucemTasaError, 1) }}%</p>
                            <p class="text-xs {{ $ec['text'] }} opacity-70">tasa de error (sem. actual)</p>
                        </div>
                    </div>

                    {{-- Controles de override (solo admin) --}}
                    <div class="flex flex-wrap items-center gap-3 mb-8">
                        @if(!$tieneOverride && in_array($estadoActual['estado'], ['VUCEM_CAIDO', 'INTERMITENTE']))
                        <form method="POST" action="{{ route('admin.estadisticas.forzar-operando') }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('¿Confirmas que el sistema VUCEM está funcionando correctamente y deseas ocultar la alerta por 2 horas?')"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold shadow transition-colors">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                                Marcar como Operando (forzar 2h)
                            </button>
                        </form>
                        @endif
                        @if($tieneOverride)
                        <form method="POST" action="{{ route('admin.estadisticas.limpiar-override') }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-600 hover:bg-slate-700 text-white text-xs font-bold shadow transition-colors">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                Restaurar diagnóstico automático
                            </button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('admin.estadisticas.forzar-operando') }}" class="hidden" id="form-limpiar-cache">
                            @csrf
                        </form>
                        <p class="text-xs text-slate-400">
                            <i data-lucide="info" class="w-3 h-3 inline-block"></i>
                            La alerta automática evalúa los últimos 30 minutos. Los errores de la semana pasada no afectan este diagnóstico.
                        </p>
                    </div>

                    {{-- KPI cards --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                                    <i data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full">hoy</span>
                            </div>
                            <p class="text-3xl font-black text-slate-800">{{ $vucemErroresHoy }}</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Errores hoy</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center">
                                    <i data-lucide="alert-triangle" class="w-5 h-5 text-orange-500"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full">sem. actual</span>
                            </div>
                            <p class="text-3xl font-black text-slate-800">{{ $vucemErroresTotal }}</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Errores totales</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full">sem. actual</span>
                            </div>
                            <p class="text-3xl font-black text-slate-800">{{ $vucemExitososTotal }}</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1">MVE enviadas</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                                    <i data-lucide="activity" class="w-5 h-5 text-blue-500"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full">sem. actual</span>
                            </div>
                            <p class="text-3xl font-black text-slate-800">{{ $vucemTotalOps }}</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Operaciones totales</p>
                        </div>
                    </div>

                    {{-- Gráfica semana actual --}}
                    @php
                        $maxDiaVal = max(array_map(fn($d) => $d['errores'] + $d['exitosos'], $diasChart) + [1]);
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-6">
                        <h2 class="text-base font-black text-slate-800 mb-1">Actividad semana actual</h2>
                        <p class="text-xs text-slate-400 mb-5">Errores vs. envíos exitosos por día (lun–hoy)</p>
                        <div class="flex items-end gap-2 h-36">
                            @foreach($diasChart as $dia)
                            @php
                                $total = $dia['errores'] + $dia['exitosos'];
                                $hErr = $total > 0 ? round(($dia['errores'] / $maxDiaVal) * 100) : 0;
                                $hOk  = $total > 0 ? round(($dia['exitosos'] / $maxDiaVal) * 100) : 0;
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-0.5 group relative" title="{{ $dia['label'] }}: {{ $dia['errores'] }} errores / {{ $dia['exitosos'] }} exitosos">
                                {{-- Tooltip --}}
                                <div class="absolute -top-10 left-1/2 -translate-x-1/2 hidden group-hover:flex bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10 pointer-events-none">
                                    ✗ {{ $dia['errores'] }} &bull; ✓ {{ $dia['exitosos'] }}
                                </div>
                                <div class="w-full flex flex-col-reverse gap-0.5 items-center">
                                    @if($dia['errores'] > 0)
                                    <div class="w-full bg-red-400 rounded-t" style="height: {{ max(4, $hErr) }}%; min-height: 4px; max-height: 144px;"></div>
                                    @endif
                                    @if($dia['exitosos'] > 0)
                                    <div class="w-full bg-green-400 rounded-t" style="height: {{ max(4, $hOk) }}%; min-height: 4px; max-height: 144px;"></div>
                                    @endif
                                    @if($total === 0)
                                    <div class="w-full bg-slate-100 rounded" style="height: 4px;"></div>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1 font-medium">{{ $dia['label'] }}</span>
                            </div>
                            @endforeach
                        </div>
                        <div class="flex items-center gap-4 mt-3">
                            <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span> Errores</span>
                            <span class="flex items-center gap-1.5 text-xs text-slate-500"><span class="w-3 h-3 rounded-sm bg-green-400 inline-block"></span> Exitosos</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        {{-- Errores por servicio --}}
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h2 class="text-base font-black text-slate-800 mb-1">Por servicio</h2>
                            <p class="text-xs text-slate-400 mb-5">Errores de la semana actual por servicio VUCEM</p>
                            @php $maxServ = max($vucemPorServicio->max('total') ?? 1, 1); @endphp
                            @forelse($vucemPorServicio as $row)
                            @php
                                $pct = round(($row->total / $maxServ) * 100);
                                $servLabel = ['MV_ENVIO' => 'Envío de MVE', 'MV_CONSULTA' => 'Consulta MVE', 'DIGITALIZACION' => 'Digitalización', 'DIGITALIZACION_CONSULTA' => 'Consulta Digit.', 'OTRO' => 'Otro'][$row->servicio] ?? $row->servicio;
                            @endphp
                            <div class="mb-3">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-semibold text-slate-700">{{ $servLabel }}</span>
                                    <span class="font-black text-red-600">{{ $row->total }}</span>
                                </div>
                                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-400 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-slate-400 text-center py-6">Sin errores esta semana ✓</p>
                            @endforelse
                        </div>

                        {{-- Errores por tipo --}}
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h2 class="text-base font-black text-slate-800 mb-1">Por tipo de error</h2>
                            <p class="text-xs text-slate-400 mb-5">Clasificación de errores de red (semana actual)</p>
                            @php
                                $maxTipo = max($vucemPorTipo->max('total') ?? 1, 1);
                                $tipoColors = ['TIMEOUT' => 'bg-orange-400', 'CONNECTION_REFUSED' => 'bg-red-500', 'SSL_ERROR' => 'bg-purple-400', 'DNS_ERROR' => 'bg-yellow-400', 'NETWORK_ERROR' => 'bg-pink-400', 'CURL_ERROR' => 'bg-slate-400'];
                            @endphp
                            @forelse($vucemPorTipo as $row)
                            @php $pct = round(($row->total / $maxTipo) * 100); @endphp
                            <div class="mb-3">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-semibold text-slate-700">{{ str_replace('_', ' ', $row->tipo_error) }}</span>
                                    <span class="font-black text-slate-700">{{ $row->total }}</span>
                                </div>
                                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full {{ $tipoColors[$row->tipo_error] ?? 'bg-slate-400' }} rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-slate-400 text-center py-6">Sin errores clasificados ✓</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Top usuarios --}}
                    @if($vucemTopUsuarios->isNotEmpty())
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h2 class="text-base font-black text-slate-800 mb-1">Top usuarios con errores</h2>
                        <p class="text-xs text-slate-400 mb-5">Usuarios que más errores de conectividad VUCEM han tenido (semana actual)</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                                        <th class="pb-3 font-bold">#</th>
                                        <th class="pb-3 font-bold">Usuario</th>
                                        <th class="pb-3 font-bold">Correo</th>
                                        <th class="pb-3 font-bold text-right">Errores</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vucemTopUsuarios as $i => $item)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td class="py-3 pr-4 text-slate-400 font-bold">{{ $i + 1 }}</td>
                                        <td class="py-3 pr-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-black flex-shrink-0">
                                                    {{ strtoupper(substr($item['user']?->full_name ?? '?', 0, 1)) }}
                                                </div>
                                                <span class="font-semibold text-slate-800">{{ $item['user']?->full_name ?? 'Usuario eliminado' }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-4 text-slate-500 text-xs">{{ $item['user']?->email ?? '—' }}</td>
                                        <td class="py-3 text-right">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-red-50 text-red-700">{{ $item['total'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                </div>{{-- end VUCEM tab --}}

                {{-- ════════════════════════════════════════════════════════════════ --}}
                {{-- TAB: TICKETS                                                     --}}
                {{-- ════════════════════════════════════════════════════════════════ --}}
                <div x-show="tab === 'tickets'" x-transition>

                    {{-- KPI por status --}}
                    @php
                        $stCfg = [
                            'open'        => ['label' => 'Abiertos',    'bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'icon' => 'circle',          'border' => 'border-amber-200'],
                            'in_progress' => ['label' => 'En Proceso',  'bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'icon' => 'loader-2',        'border' => 'border-blue-200'],
                            'closed'      => ['label' => 'Cerrados',    'bg' => 'bg-green-50',  'text' => 'text-green-700',  'icon' => 'check-circle-2',  'border' => 'border-green-200'],
                            'cancelled'   => ['label' => 'Cancelados',  'bg' => 'bg-slate-50',  'text' => 'text-slate-500',  'icon' => 'x-circle',        'border' => 'border-slate-200'],
                        ];
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                        @foreach($stCfg as $status => $cfg)
                        @php $count = $ticketsPorStatus[$status]?->total ?? 0; @endphp
                        <div class="bg-white rounded-2xl border {{ $cfg['border'] }} p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 {{ $cfg['bg'] }} rounded-xl flex items-center justify-center">
                                    <i data-lucide="{{ $cfg['icon'] }}" class="w-5 h-5 {{ $cfg['text'] }}"></i>
                                </div>
                            </div>
                            <p class="text-3xl font-black text-slate-800">{{ $count }}</p>
                            <p class="text-xs font-semibold text-slate-500 mt-1">{{ $cfg['label'] }}</p>
                        </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        {{-- Por categoría --}}
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h2 class="text-base font-black text-slate-800 mb-1">Por categoría</h2>
                            <p class="text-xs text-slate-400 mb-5">Tickets de los últimos 30 días por categoría</p>
                            @php $maxCat = max($ticketsPorCategoria->max('total') ?? 1, 1); @endphp
                            @forelse($ticketsPorCategoria as $row)
                            @php $pct = round(($row->total / $maxCat) * 100); @endphp
                            <div class="mb-3">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-semibold text-slate-700">{{ $row->category ?: 'Sin categoría' }}</span>
                                    <span class="font-black text-[#003399]">{{ $row->total }}</span>
                                </div>
                                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-[#003399] rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-slate-400 text-center py-6">Sin tickets en los últimos 30 días</p>
                            @endforelse
                        </div>

                        {{-- Tendencia 7 días --}}
                        @php $maxTickDia = max(array_map(fn($d) => $d['total'], $ticketsDiasChart) + [1]); @endphp
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h2 class="text-base font-black text-slate-800 mb-1">Tendencia últimos 7 días</h2>
                            <p class="text-xs text-slate-400 mb-5">{{ $ticketsTotales7d }} ticket(s) en la última semana</p>
                            <div class="flex items-end gap-2 h-32">
                                @foreach($ticketsDiasChart as $dia)
                                @php $h = $dia['total'] > 0 ? round(($dia['total'] / $maxTickDia) * 100) : 0; @endphp
                                <div class="flex-1 flex flex-col items-center gap-0.5 group relative" title="{{ $dia['label'] }}: {{ $dia['total'] }} ticket(s)">
                                    <div class="absolute -top-8 left-1/2 -translate-x-1/2 hidden group-hover:flex bg-slate-800 text-white text-[10px] rounded px-2 py-1 whitespace-nowrap z-10 pointer-events-none">
                                        {{ $dia['total'] }} ticket(s)
                                    </div>
                                    @if($dia['total'] > 0)
                                    <div class="w-full bg-[#003399] rounded-t" style="height: {{ max(6, $h) }}%; min-height: 6px; max-height: 128px;"></div>
                                    @else
                                    <div class="w-full bg-slate-100 rounded" style="height: 4px;"></div>
                                    @endif
                                    <span class="text-[10px] text-slate-400 mt-1 font-medium">{{ $dia['label'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Tickets recientes abiertos --}}
                    @if($ticketsRecientes->isNotEmpty())
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-base font-black text-slate-800">Tickets pendientes</h2>
                                <p class="text-xs text-slate-400">Tickets abiertos o en proceso que requieren atención</p>
                            </div>
                            <a href="{{ route('tickets.index') }}" class="text-xs font-bold text-[#003399] hover:underline flex items-center gap-1">
                                Ver todos <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                                        <th class="pb-3 font-bold">Usuario</th>
                                        <th class="pb-3 font-bold">Asunto</th>
                                        <th class="pb-3 font-bold">Categoría</th>
                                        <th class="pb-3 font-bold">Estado</th>
                                        <th class="pb-3 font-bold text-right">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ticketsRecientes as $ticket)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td class="py-3 pr-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-black flex-shrink-0">
                                                    {{ strtoupper(substr($ticket->user?->full_name ?? '?', 0, 1)) }}
                                                </div>
                                                <span class="font-semibold text-slate-800 text-xs">{{ $ticket->user?->full_name ?? 'Desconocido' }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-4 max-w-xs">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-[#003399] hover:underline font-medium text-xs line-clamp-1">{{ $ticket->subject }}</a>
                                        </td>
                                        <td class="py-3 pr-4">
                                            <span class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{{ $ticket->category }}</span>
                                        </td>
                                        <td class="py-3 pr-4">
                                            @php
                                                $stColor = ['open' => 'bg-amber-100 text-amber-800', 'in_progress' => 'bg-blue-100 text-blue-800'];
                                            @endphp
                                            <span class="text-xs font-bold {{ $stColor[$ticket->status] ?? 'bg-slate-100 text-slate-600' }} px-2 py-0.5 rounded-full">
                                                {{ $ticket->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right text-xs text-slate-400">{{ $ticket->created_at->diffForHumans() }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center shadow-sm">
                        <i data-lucide="inbox" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
                        <p class="font-bold text-slate-600">No hay tickets pendientes</p>
                        <p class="text-sm text-slate-400 mt-1">Todos los tickets han sido atendidos.</p>
                    </div>
                    @endif

                </div>{{-- end tickets tab --}}

            </div>{{-- end x-data --}}
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const avatarButton = document.getElementById('avatarButton');
            const dropdownMenu = document.getElementById('dropdownMenu');
            if (avatarButton && dropdownMenu) {
                avatarButton.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
                document.addEventListener('click', (e) => {
                    if (!avatarButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</x-app-layout>
