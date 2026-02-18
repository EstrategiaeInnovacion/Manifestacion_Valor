<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Manifestación de Valor | E&I</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <script src="https://unpkg.com/lucide@latest"></script>

        @vite(['resources/css/app.css', 'resources/css/welcome.css', 'resources/js/app.js', 'resources/js/welcome.js'])
    </head>
    {{-- ELIMINADO: dark:bg-[#0a0a0a] --}}
    <body class="bg-[#F8FAFC] text-[#1e293b] min-h-screen flex flex-col">
        
        {{-- ELIMINADO: dark:bg-[#161615] dark:border-slate-800 --}}
        <header class="w-full bg-white border-b border-slate-200 px-8 py-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center">
                    <a href="/">
                        <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-12 w-auto object-contain">
                    </a>
                </div>

                <nav class="flex items-center gap-6">
                </nav>
            </div>
        </header>

        <main class="flex-grow flex items-center justify-center p-6 lg:p-12">
            {{-- ELIMINADO: dark:bg-[#161615] dark:border-slate-800 --}}
            <div class="max-w-6xl w-full grid lg:grid-cols-2 bg-white rounded-[3.5rem] shadow-2xl overflow-hidden border border-slate-100">
                
                <div class="p-12 lg:p-24 flex flex-col justify-center">
                    <div class="w-12 h-1.5 bg-[#003399] mb-8 rounded-full"></div>

                    {{-- ELIMINADO: dark:text-white --}}
                    <h2 class="text-6xl font-black text-[#001a4d] mb-6 leading-[1.1] tracking-tight">
                        Manifestación <br><span class="text-[#003399]">de Valor.</span>
                    </h2>
                    
                    {{-- ELIMINADO: dark:text-slate-400 --}}
                    <p class="text-slate-500 mb-12 text-xl leading-relaxed max-w-md">
                        Plataforma inteligente para la gestión de documentos de valor aduanal y control de operaciones internacionales.
                    </p>

                    <div class="flex items-center">
                        <a href="{{ route('login') }}" class="bg-[#001a4d] hover:bg-[#003399] text-white font-bold py-5 px-12 rounded-2xl transition shadow-2xl flex items-center gap-3 group text-lg">
                            Ingresar al Portal
                            <i data-lucide="arrow-right" class="w-6 h-6 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <div class="bg-ei-gradient relative flex items-center justify-center p-12 lg:p-20">
                    <div class="absolute inset-0 opacity-10 welcome-pattern"></div>
                    
                    <div class="relative w-full max-w-[400px]">
                        <div class="glass-card p-4 rounded-[3rem] shadow-2xl">
                            <div class="aspect-[4/5] rounded-[2.5rem] overflow-hidden image-container border border-white/20">
                                <img src="https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&q=80&w=600" 
                                     alt="Comercio Exterior E&I" 
                                     class="w-full h-full object-cover">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="py-10 text-center">
            <div class="flex items-center justify-center gap-4 mb-4 opacity-30">
                <div class="h-px w-12 bg-slate-400"></div>
                <i data-lucide="ship" class="w-4 h-4 text-slate-500"></i>
                <div class="h-px w-12 bg-slate-400"></div>
            </div>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.4em]">
                Comercio Exterior, Logística y Tecnología
            </p>
        </footer>
    </body>
</html>