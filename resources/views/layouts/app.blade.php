<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Manifestación de Valor') }} | E&I</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <script src="https://unpkg.com/lucide@latest"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased{{ auth()->check() && \App\Models\AppSetting::get('banner_enabled', '0') === '1' && \App\Models\AppSetting::get('banner_message', '') !== '' ? ' has-banner' : '' }}">

        @auth
        {{-- ── BANNER AUTOMÁTICO DE ESTADO VUCEM ────────────────────────── --}}
        @php
            $vucemEstadoAuto = \App\Services\VucemDiagnosticService::getEstadoSistema();
        @endphp
        @if(in_array($vucemEstadoAuto['estado'], ['VUCEM_CAIDO', 'INTERMITENTE']))
        <div class="w-full {{ $vucemEstadoAuto['color'] === 'red' ? 'bg-red-600 border-red-700' : 'bg-amber-500 border-amber-600' }} border-b px-4 py-2 flex items-center justify-center gap-2.5 z-[56]" style="position: sticky; top: 0;">
            <i data-lucide="{{ $vucemEstadoAuto['color'] === 'red' ? 'wifi-off' : 'wifi' }}" style="width:15px;height:15px;flex-shrink:0;" class="{{ $vucemEstadoAuto['color'] === 'red' ? 'text-red-100' : 'text-amber-900' }}"></i>
            <p class="text-sm font-semibold {{ $vucemEstadoAuto['color'] === 'red' ? 'text-white' : 'text-amber-950' }} text-center">
                <span class="font-black">{{ $vucemEstadoAuto['titulo'] }}:</span> {{ $vucemEstadoAuto['mensaje'] }}
            </p>
        </div>
        @endif

        {{-- ── BANNER DE AVISO GLOBAL ─────────────────────────────────────── --}}
        @php
            $sysBannerEnabled = \App\Models\AppSetting::get('banner_enabled', '0') === '1';
            $sysBannerMessage = \App\Models\AppSetting::get('banner_message', '');
        @endphp
        @if($sysBannerEnabled && $sysBannerMessage !== '')
        <div id="system-banner" class="system-banner-bar w-full bg-amber-50 border-b border-amber-300 px-4 py-2 flex items-center justify-center gap-3 z-[55]" style="position: sticky; top: 0;">
            <i data-lucide="megaphone" style="width:16px;height:16px;flex-shrink:0;" class="text-amber-600"></i>
            <p class="text-sm font-medium text-amber-900 text-center">{{ $sysBannerMessage }}</p>
        </div>
        @endif

        {{-- ── MODAL DE AVISOS GENERALES ──────────────────────────────────── --}}
        @php
            $pendingAnnouncements = \App\Models\Announcement::whereDoesntHave('reads', fn($q) => $q->where('user_id', auth()->id()))
                ->oldest()
                ->get(['id', 'title', 'body', 'created_at']);
        @endphp
        @if($pendingAnnouncements->isNotEmpty())
        <div id="announcement-modal" class="announcement-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="announcement-modal-title">
            <div class="announcement-modal-box">
                {{-- Header --}}
                <div class="announcement-modal-header">
                    <div class="announcement-modal-icon">
                        <i data-lucide="bell" style="width:22px;height:22px;"></i>
                    </div>
                    <div>
                        <p class="announcement-modal-label">Aviso del Sistema</p>
                        <p id="announcement-modal-title" class="announcement-modal-title"></p>
                    </div>
                </div>
                {{-- Body --}}
                <div class="announcement-modal-body">
                    <p id="announcement-modal-body"></p>
                </div>
                {{-- Counter --}}
                <div id="announcement-counter" class="announcement-modal-counter"></div>
                {{-- Footer --}}
                <div class="announcement-modal-footer">
                    <button id="announcement-accept" type="button" class="announcement-modal-btn" data-id="">
                        <i data-lucide="check" style="width:16px;height:16px;"></i>
                        Aceptar y Continuar
                    </button>
                </div>
            </div>
        </div>

        <script>
        (function() {
            const announcements = @json($pendingAnnouncements);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            let currentIdx = 0;

            function showCurrent() {
                if (currentIdx >= announcements.length) {
                    document.getElementById('announcement-modal').remove();
                    return;
                }
                const a = announcements[currentIdx];
                document.getElementById('announcement-modal-title').textContent = a.title;
                document.getElementById('announcement-modal-body').textContent = a.body;
                document.getElementById('announcement-accept').dataset.id = a.id;

                const counter = document.getElementById('announcement-counter');
                if (announcements.length > 1) {
                    counter.textContent = (currentIdx + 1) + ' de ' + announcements.length + ' avisos pendientes';
                    counter.style.display = 'block';
                } else {
                    counter.style.display = 'none';
                }
            }

            document.getElementById('announcement-accept').addEventListener('click', async function() {
                const id = this.dataset.id;
                try {
                    await fetch('/announcements/' + id + '/read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                } catch (e) { /* no-op */ }
                currentIdx++;
                showCurrent();
            });

            showCurrent();
        })();
        </script>
        @endif
        @endauth

        {{ $slot }}
        <footer class="border-t border-slate-100 bg-white mt-0 py-4">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
                <span>© {{ date('Y') }} Estrategia e Innovación. Todos los derechos reservados.</span>
                <div class="flex gap-4">
                    <a href="{{ route('legal.privacidad') }}" class="hover:text-[#003399] transition-colors">Aviso de Privacidad</a>
                    <a href="{{ route('legal.privacidad') }}#condiciones" class="hover:text-[#003399] transition-colors">Condiciones de Uso</a>
                </div>
            </div>
        </footer>
    </body>
</html>
