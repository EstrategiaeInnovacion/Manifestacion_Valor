<x-app-layout>
    <x-slot name="title">Ajustes del Sistema</x-slot>
    @vite(['resources/css/users-list.css', 'resources/css/license-panel.css'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Ajustes del Sistema</span>
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

        <main class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-4">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>
                <h1 class="text-3xl font-black text-[#001a4d]">Ajustes del Sistema</h1>
                <p class="text-slate-500 mt-1">Gestiona el contenido legal y avisos del sistema.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-600"></i>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Tabs --}}
            <div x-data="{ tab: 'sellos' }" class="space-y-0">
                <div class="flex gap-1 bg-slate-100 p-1 rounded-xl mb-6">
                    <button @click="tab='sellos'"
                        :class="tab==='sellos' ? 'bg-white shadow text-[#003399] font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm transition-all">
                        <i data-lucide="shield" class="w-4 h-4"></i>
                        Aviso de Sellos VUCEM
                    </button>
                    <button @click="tab='privacidad'"
                        :class="tab==='privacidad' ? 'bg-white shadow text-[#003399] font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm transition-all">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        Aviso de Privacidad
                    </button>
                    <button @click="tab='condiciones'"
                        :class="tab==='condiciones' ? 'bg-white shadow text-[#003399] font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm transition-all">
                        <i data-lucide="scroll-text" class="w-4 h-4"></i>
                        Condiciones de Uso
                    </button>
                </div>

                {{-- Tab: Aviso Sellos --}}
                <div x-show="tab==='sellos'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-[#001a4d] to-[#003399] px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/10 rounded-lg">
                                    <i data-lucide="shield" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Aviso de Privacidad — Sección de Sellos VUCEM</h2>
                                    <p class="text-blue-200 text-xs mt-0.5">Este texto aparece cuando el usuario sube sus credenciales (sellos digitales) de un solicitante.</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                                <i data-lucide="info" class="w-4 h-4 text-amber-600 mt-0.5 shrink-0"></i>
                                <p class="text-xs text-amber-800">Puedes usar HTML básico: <code class="bg-amber-100 px-1 rounded">&lt;strong&gt;</code>, <code class="bg-amber-100 px-1 rounded">&lt;p&gt;</code>, <code class="bg-amber-100 px-1 rounded">&lt;ul&gt;&lt;li&gt;</code>, etc. El contenido se mostrará dentro de un cuadro con scroll.</p>
                            </div>

                            {{-- Preview --}}
                            <div class="mb-4">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Vista previa</p>
                                <div id="preview-sellos" class="bg-slate-50 border border-slate-200 rounded-xl p-4 max-h-48 overflow-y-auto text-xs text-slate-700 leading-relaxed prose prose-sm max-w-none">
                                    {!! $avisoSellos !!}
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.settings.update') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="key" value="aviso_privacidad_sellos">
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Contenido HTML</label>
                                    <textarea name="value" id="editor-sellos" rows="12"
                                        class="w-full border border-slate-200 rounded-xl p-3 text-xs font-mono text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#003399]/30 focus:border-[#003399] resize-y"
                                        oninput="updatePreview('sellos', this.value)">{{ $avisoSellos }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 bg-[#003399] hover:bg-[#002266] text-white font-bold px-6 py-2.5 rounded-xl transition-colors">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Guardar Aviso de Sellos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tab: Aviso de Privacidad Completo --}}
                <div x-show="tab==='privacidad'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-[#001a4d] to-[#003399] px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/10 rounded-lg">
                                    <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Aviso de Privacidad Completo</h2>
                                    <p class="text-blue-200 text-xs mt-0.5">Texto completo que aparece en la página pública de Aviso de Privacidad
                                        (<a href="{{ route('legal.privacidad') }}" target="_blank" class="underline text-blue-100">ver página</a>).</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Vista previa</p>
                                <div id="preview-privacidad" class="bg-slate-50 border border-slate-200 rounded-xl p-4 max-h-48 overflow-y-auto text-sm text-slate-700 leading-relaxed prose prose-sm max-w-none">
                                    {!! $avisoCompleto !!}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.update') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="key" value="aviso_privacidad_completo">
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Contenido HTML</label>
                                    <textarea name="value" rows="14"
                                        class="w-full border border-slate-200 rounded-xl p-3 text-xs font-mono text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#003399]/30 focus:border-[#003399] resize-y"
                                        oninput="updatePreview('privacidad', this.value)">{{ $avisoCompleto }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 bg-[#003399] hover:bg-[#002266] text-white font-bold px-6 py-2.5 rounded-xl transition-colors">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Guardar Aviso de Privacidad
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Tab: Condiciones de Uso --}}
                <div x-show="tab==='condiciones'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-[#001a4d] to-[#003399] px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/10 rounded-lg">
                                    <i data-lucide="scroll-text" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Condiciones de Uso</h2>
                                    <p class="text-blue-200 text-xs mt-0.5">Términos y condiciones de uso del sistema. Aparecen en la página pública
                                        (<a href="{{ route('legal.privacidad') }}#condiciones" target="_blank" class="underline text-blue-100">ver página</a>).</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Vista previa</p>
                                <div id="preview-condiciones" class="bg-slate-50 border border-slate-200 rounded-xl p-4 max-h-48 overflow-y-auto text-sm text-slate-700 leading-relaxed prose prose-sm max-w-none">
                                    {!! $condicionesUso !!}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.update') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="key" value="condiciones_uso">
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Contenido HTML</label>
                                    <textarea name="value" rows="14"
                                        class="w-full border border-slate-200 rounded-xl p-3 text-xs font-mono text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#003399]/30 focus:border-[#003399] resize-y"
                                        oninput="updatePreview('condiciones', this.value)">{{ $condicionesUso }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 bg-[#003399] hover:bg-[#002266] text-white font-bold px-6 py-2.5 rounded-xl transition-colors">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Guardar Condiciones de Uso
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="mt-16 border-t border-slate-200 bg-white py-6">
            <div class="max-w-5xl mx-auto px-4 text-center text-xs text-slate-400">
                © {{ date('Y') }} Estrategia e Innovación. Todos los derechos reservados.
            </div>
        </footer>
    </div>

    <script>
        // Dropdown avatar
        document.getElementById('avatarButton')?.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('dropdownMenu')?.classList.toggle('active');
        });
        document.addEventListener('click', function() {
            document.getElementById('dropdownMenu')?.classList.remove('active');
        });

        // Live preview
        function updatePreview(id, html) {
            const el = document.getElementById('preview-' + id);
            if (el) el.innerHTML = html;
        }

        lucide.createIcons();
    </script>
</x-app-layout>
