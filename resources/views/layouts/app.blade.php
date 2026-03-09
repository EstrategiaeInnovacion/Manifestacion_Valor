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
    <body class="font-sans antialiased">
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
