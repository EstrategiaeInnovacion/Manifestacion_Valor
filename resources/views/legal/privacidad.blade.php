<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aviso de Privacidad y Condiciones de Uso | E&I</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet"/>
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Instrument Sans', sans-serif; background: #F8FAFC; color: #1e293b; }
        .prose h2 { color: #001a4d; font-size: 1.4rem; font-weight: 800; margin-bottom: 0.75rem; margin-top: 1.5rem; }
        .prose h3 { color: #003399; font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; margin-top: 1.25rem; }
        .prose p  { font-size: 0.9rem; line-height: 1.7; margin-bottom: 1rem; color: #475569; }
        .prose ul { list-style: disc; padding-left: 1.5rem; margin-bottom: 1rem; }
        .prose li { font-size: 0.9rem; color: #475569; margin-bottom: 0.25rem; }
        .prose strong { color: #1e293b; }
    </style>
</head>
<body>
    {{-- Header --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ auth()->check() ? route('dashboard') : '/' }}" class="flex items-center gap-3">
                <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="E&I" class="h-9 w-auto">
                <span class="font-bold text-[#001a4d] hidden sm:block">Estrategia e Innovación</span>
            </a>
            <a href="{{ auth()->check() ? route('dashboard') : '/' }}"
               class="inline-flex items-center gap-2 text-sm font-semibold text-[#003399] hover:text-[#001a4d] transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                {{ auth()->check() ? 'Regresar al sistema' : 'Inicio' }}
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-[#001a4d] to-[#003399] py-14 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-2xl mb-4">
                <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-white mb-3">Aviso de Privacidad y Condiciones de Uso</h1>
            <p class="text-blue-200 text-sm max-w-xl mx-auto">Información legal sobre el tratamiento de datos y las condiciones de uso del sistema de Manifestación de Valor Electrónica.</p>
            <p class="text-blue-300 text-xs mt-3">Última actualización: {{ \App\Models\AppSetting::where('key', 'aviso_privacidad_completo')->value('updated_at') ? \Carbon\Carbon::parse(\App\Models\AppSetting::where('key', 'aviso_privacidad_completo')->value('updated_at'))->format('d/m/Y') : date('d/m/Y') }}</p>
        </div>
    </section>

    {{-- Navigation --}}
    <div class="max-w-4xl mx-auto px-4 mt-8 flex gap-3 flex-wrap">
        <a href="#privacidad" class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:border-[#003399] hover:text-[#003399] font-semibold px-4 py-2 rounded-xl text-sm shadow-sm transition-all">
            <i data-lucide="file-text" class="w-4 h-4"></i>
            Aviso de Privacidad
        </a>
        <a href="#condiciones" class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:border-[#003399] hover:text-[#003399] font-semibold px-4 py-2 rounded-xl text-sm shadow-sm transition-all">
            <i data-lucide="scroll-text" class="w-4 h-4"></i>
            Condiciones de Uso
        </a>
    </div>

    <main class="max-w-4xl mx-auto px-4 py-10 space-y-8">
        {{-- Privacy Notice --}}
        <section id="privacidad" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden scroll-mt-24">
            <div class="bg-gradient-to-r from-[#001a4d] to-[#003399] px-6 py-5 flex items-center gap-3">
                <div class="p-2 bg-white/10 rounded-lg">
                    <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
                </div>
                <h2 class="text-lg font-bold text-white m-0">Aviso de Privacidad</h2>
            </div>
            <div class="p-8 prose max-w-none">
                {!! $avisoCompleto !!}
            </div>
        </section>

        {{-- Terms of Use --}}
        <section id="condiciones" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden scroll-mt-24">
            <div class="bg-gradient-to-r from-slate-700 to-slate-900 px-6 py-5 flex items-center gap-3">
                <div class="p-2 bg-white/10 rounded-lg">
                    <i data-lucide="scroll-text" class="w-5 h-5 text-white"></i>
                </div>
                <h2 class="text-lg font-bold text-white m-0">Condiciones de Uso</h2>
            </div>
            <div class="p-8 prose max-w-none">
                {!! $condicionesUso !!}
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 bg-white mt-16 py-8">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="text-xs text-slate-400">© {{ date('Y') }} Estrategia e Innovación. Todos los derechos reservados.</p>
            <p class="text-xs text-slate-400 mt-1">
                Para ejercer sus derechos ARCO o consultas sobre privacidad, contacte al administrador del sistema.
            </p>
        </div>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
