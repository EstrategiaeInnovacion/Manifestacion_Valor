<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acceso al Portal | E&I</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <script src="https://unpkg.com/lucide@latest"></script>

        @vite(['resources/css/app.css', 'resources/css/login.css', 'resources/js/app.js', 'resources/js/login.js'])
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
                            Acceso al <br><span class="text-[#003399]">Portal.</span>
                        </h2>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    @if(session('license_expired'))
                        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                            </svg>
                            <span>{{ session('license_expired') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="login" class="block text-sm font-bold text-[#001a4d] mb-2 px-1">Usuario o Correo</label>
                            <input id="login" 
                                   type="text" 
                                   name="login" 
                                   value="{{ old('login') }}" 
                                   required 
                                   autofocus 
                                   class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#003399] focus:border-transparent transition-all outline-none placeholder:text-slate-300"
                                   placeholder="Nombre de usuario o email">
                            <x-input-error :messages="$errors->get('login')" class="mt-2" />
                        </div>

                        <div>
                            <div class="flex justify-between mb-2 px-1">
                                <label for="password" class="text-sm font-bold text-[#001a4d]">Contraseña</label>
                                @if (Route::has('password.request'))
                                    <a class="text-xs font-bold text-[#003399] hover:underline" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                                @endif
                            </div>
                            <input id="password" 
                                   type="password" 
                                   name="password" 
                                   required 
                                   class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#003399] focus:border-transparent transition-all outline-none placeholder:text-slate-300"
                                   placeholder="••••••••">
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="flex items-center px-1">
                            <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-[#003399] focus:ring-[#003399]">
                            <span class="ms-3 text-sm font-medium text-slate-500 italic">Mantener sesión iniciada</span>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-[#001a4d] hover:bg-[#003399] text-white font-black py-5 px-8 rounded-2xl transition shadow-xl flex items-center justify-center gap-3 group text-lg">
                                Ingresar
                                <i data-lucide="arrow-right" class="w-6 h-6 group-hover:translate-x-1 transition-transform"></i>
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

    </body>
</html>
