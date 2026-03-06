<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Nueva Contraseña | E&I</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <script src="https://unpkg.com/lucide@latest"></script>
        @vite(['resources/css/app.css', 'resources/css/login.css', 'resources/js/app.js'])
    </head>
    <body class="login-page bg-[#F8FAFC] min-h-screen flex flex-col">
        <main class="flex-grow flex items-center justify-center p-6">
            <div class="max-w-md w-full">
                <div class="bg-white rounded-[3.5rem] shadow-2xl overflow-hidden border border-slate-100 p-10 lg:p-14">
                    <div class="text-center mb-10">
                        <a href="/" class="inline-block mb-8 transition hover:opacity-80">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-14 w-auto mx-auto object-contain">
                        </a>
                        <div class="w-12 h-1.5 bg-[#003399] mx-auto mb-6 rounded-full"></div>
                        <h2 class="text-4xl font-black text-[#001a4d] tracking-tight leading-tight">
                            Nueva <br><span class="text-[#003399]">Contraseña.</span>
                        </h2>
                        <p class="text-sm text-slate-400 mt-3 leading-relaxed">
                            Elige una contraseña segura de al menos 8 caracteres.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-5 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-start gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500"></i>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="password" class="block text-sm font-bold text-[#001a4d] mb-2 px-1">Nueva Contraseña</label>
                            <div class="relative">
                                <input id="password"
                                       type="password"
                                       name="password"
                                       required
                                       autocomplete="new-password"
                                       class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#003399] focus:border-transparent transition-all outline-none placeholder:text-slate-300 pr-14"
                                       placeholder="Mínimo 8 caracteres">
                                <button type="button" onclick="togglePass('password','icon1')"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i id="icon1" data-lucide="eye" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-bold text-[#001a4d] mb-2 px-1">Confirmar Contraseña</label>
                            <div class="relative">
                                <input id="password_confirmation"
                                       type="password"
                                       name="password_confirmation"
                                       required
                                       autocomplete="new-password"
                                       class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#003399] focus:border-transparent transition-all outline-none placeholder:text-slate-300 pr-14"
                                       placeholder="Repite tu contraseña">
                                <button type="button" onclick="togglePass('password_confirmation','icon2')"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i id="icon2" data-lucide="eye" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full bg-[#001a4d] hover:bg-[#003399] text-white font-black py-5 px-8 rounded-2xl transition shadow-xl flex items-center justify-center gap-3 group text-lg">
                                Guardar Contraseña
                                <i data-lucide="check-circle" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-12">
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.4em] opacity-60">
                        Comercio Exterior, Logística y Tecnología
                    </p>
                </div>
            </div>
        </main>
        <script>
            lucide.createIcons();
            function togglePass(fieldId, iconId) {
                const input = document.getElementById(fieldId);
                const icon  = document.getElementById(iconId);
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.setAttribute('data-lucide', input.type === 'password' ? 'eye' : 'eye-off');
                lucide.createIcons();
            }
        </script>
    </body>
</html>
