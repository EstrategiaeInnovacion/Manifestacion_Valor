<x-app-layout>
    <x-slot name="title">Ajustes del Sistema</x-slot>
    @vite(['resources/css/users-list.css', 'resources/css/license-panel.css', 'resources/js/users-list.js'])

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
                <div class="flex gap-1 bg-slate-100 p-1 rounded-xl mb-6 flex-wrap">
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
                    <button @click="tab='manuales'"
                        :class="tab==='manuales' ? 'bg-white shadow text-violet-600 font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm transition-all">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        Manuales de Uso
                    </button>
                    <button @click="tab='avisos'"
                        :class="tab==='avisos' ? 'bg-white shadow text-amber-600 font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm transition-all">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        Avisos Generales
                    </button>
                    <button @click="tab='banner'"
                        :class="tab==='banner' ? 'bg-white shadow text-yellow-600 font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm transition-all">
                        <i data-lucide="megaphone" class="w-4 h-4"></i>
                        Banner de Aviso
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
                {{-- Tab: Manuales de Uso (content unchanged) --}}
                <div x-show="tab==='manuales'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-violet-800 to-violet-600 px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/10 rounded-lg">
                                    <i data-lucide="book-open" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Manuales de Uso</h2>
                                    <p class="text-violet-200 text-xs mt-0.5">Sube y gestiona los manuales del sistema por versión. Los usuarios los verán como tarjetas.</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">

                            {{-- Formulario de subida --}}
                            <form method="POST" action="{{ route('manuals.store') }}" enctype="multipart/form-data" class="mb-8">
                                @csrf
                                <div class="flex flex-col sm:flex-row gap-4 items-end">
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                            Versión del Manual
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="version" placeholder="Ej: V.2.2"
                                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500"
                                            value="{{ old('version') }}" required>
                                        @error('version')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex-[2]">
                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                            Archivo PDF
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input type="file" name="manual" accept=".pdf"
                                            class="w-full border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500" required>
                                        @error('manual')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-bold px-6 py-2.5 rounded-xl transition-colors whitespace-nowrap">
                                        <i data-lucide="upload" class="w-4 h-4"></i>
                                        Subir Manual
                                    </button>
                                </div>
                            </form>

                            {{-- Lista de manuales existentes --}}
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Versiones Publicadas ({{ $manuals->count() }})</p>
                                @if($manuals->isEmpty())
                                    <div class="text-center py-10 text-slate-400 text-sm">
                                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                        <p>No hay manuales subidos todavía.</p>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($manuals as $manual)
                                            <div class="flex items-center gap-3 border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                                                <div class="w-10 h-10 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                                    <i data-lucide="file-text" class="w-5 h-5 text-violet-600"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-bold text-[#001a4d] truncate">FILE {{ $manual->version }}</p>
                                                    <p class="text-xs text-slate-400">{{ $manual->created_at->format('d/m/Y H:i') }}</p>
                                                </div>
                                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                                    <a href="{{ route('manuals.show', $manual) }}" target="_blank"
                                                        class="w-8 h-8 rounded-lg bg-violet-600 hover:bg-violet-700 text-white flex items-center justify-center transition-colors"
                                                        title="Ver manual">
                                                        <i data-lucide="external-link" class="w-4 h-4"></i>
                                                    </a>
                                                    <form method="POST" action="{{ route('manuals.destroy', $manual) }}"
                                                        onsubmit="return confirm('¿Eliminar el manual FILE {{ $manual->version }}?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-colors"
                                                            title="Eliminar">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab: Avisos Generales --}}
                <div x-show="tab==='avisos'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/10 rounded-lg">
                                    <i data-lucide="bell" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Avisos Generales</h2>
                                    <p class="text-amber-100 text-xs mt-0.5">Publica un aviso que aparecerá como modal emergente a todos los usuarios y se enviará por correo.</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">

                            {{-- Formulario nuevo aviso --}}
                            <form method="POST" action="{{ route('announcements.store') }}" class="mb-8">
                                @csrf
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Publicar Nuevo Aviso</p>
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Título del Aviso <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="title" placeholder="Ej: Mantenimiento programado el viernes 14..."
                                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"
                                        value="{{ old('title') }}" required>
                                    @error('title')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Contenido del Aviso <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="body" rows="5" placeholder="Escribe aquí el mensaje detallado del aviso..."
                                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 resize-y"
                                        required>{{ old('body') }}</textarea>
                                    @error('body')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-2.5 rounded-xl transition-colors">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                        Publicar y Enviar por Correo
                                    </button>
                                </div>
                            </form>

                            {{-- Lista de avisos existentes --}}
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Avisos Publicados ({{ $announcements->count() }})</p>
                                @if($announcements->isEmpty())
                                    <div class="text-center py-10 text-slate-400 text-sm">
                                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                        <p>No hay avisos publicados todavía.</p>
                                    </div>
                                @else
                                    <div class="space-y-3">
                                        @foreach($announcements as $announcement)
                                            <div class="flex items-start gap-4 border border-amber-100 rounded-xl p-4 bg-amber-50">
                                                <div class="w-9 h-9 rounded-lg bg-amber-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                    <i data-lucide="bell" class="w-4 h-4 text-amber-700"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-bold text-[#001a4d]">{{ $announcement->title }}</p>
                                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $announcement->body }}</p>
                                                    <p class="text-xs text-slate-400 mt-1.5">
                                                        {{ $announcement->created_at->format('d/m/Y H:i') }} — por {{ $announcement->creator?->full_name ?? 'Sistema' }}
                                                    </p>
                                                </div>
                                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}"
                                                    onsubmit="return confirm('¿Eliminar este aviso?')" class="flex-shrink-0">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="w-8 h-8 rounded-lg bg-white hover:bg-red-500 text-red-400 hover:text-white border border-red-200 flex items-center justify-center transition-colors"
                                                        title="Eliminar aviso">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab: Banner de Aviso --}}
                <div x-show="tab==='banner'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-yellow-500 to-yellow-400 px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/10 rounded-lg">
                                    <i data-lucide="megaphone" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Banner de Aviso</h2>
                                    <p class="text-yellow-100 text-xs mt-0.5">Muestra una barra amarilla debajo de la barra de navegación en todas las páginas.</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">

                            {{-- Toggle enable/disable --}}
                            <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 mb-6">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Estado del Banner</p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Actualmente: <span class="font-semibold {{ $bannerEnabled ? 'text-emerald-600' : 'text-slate-400' }}">{{ $bannerEnabled ? 'Visible para todos los usuarios' : 'Oculto' }}</span>
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('admin.settings.update') }}" id="bannerToggleForm">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="key" value="banner_enabled">
                                    <input type="hidden" name="value" id="bannerToggleValue" value="{{ $bannerEnabled ? '0' : '1' }}">
                                    <button type="submit" id="bannerToggleBtn"
                                        class="relative inline-flex h-7 w-13 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none {{ $bannerEnabled ? 'bg-emerald-500' : 'bg-slate-300' }}"
                                        style="width: 52px;"
                                        title="{{ $bannerEnabled ? 'Desactivar banner' : 'Activar banner' }}">
                                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow-md transform transition-transform duration-200 ease-in-out mt-1 {{ $bannerEnabled ? 'translate-x-7' : 'translate-x-1' }}"></span>
                                    </button>
                                </form>
                            </div>

                            {{-- Mensaje del banner --}}
                            <form method="POST" action="{{ route('admin.settings.update') }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="key" value="banner_message">
                                <div class="mb-5">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Mensaje del Banner
                                    </label>
                                    <input type="text" name="value"
                                        placeholder="Ej: El sistema estará en mantenimiento el viernes 14 de marzo de 18:00 a 20:00 hrs."
                                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500/30 focus:border-yellow-500"
                                        value="{{ $bannerMessage }}">
                                </div>

                                {{-- Vista previa del banner --}}
                                <div class="mb-5">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Vista previa</p>
                                    <div class="w-full bg-amber-50 border-y border-amber-300 px-4 py-2.5 flex items-center gap-3 rounded-xl">
                                        <i data-lucide="megaphone" class="w-4 h-4 text-amber-600 flex-shrink-0"></i>
                                        <p class="text-sm font-medium text-amber-900 flex-1" id="bannerPreviewText">{{ $bannerMessage ?: 'El mensaje del banner aparecerá aquí...' }}</p>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-6 py-2.5 rounded-xl transition-colors">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Guardar Mensaje
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>{{-- cierra x-data alpine --}}
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

        // Live preview para editores de texto legal
        function updatePreview(id, html) {
            const el = document.getElementById('preview-' + id);
            if (el) el.innerHTML = html;
        }

        // Live preview del banner
        const bannerInput = document.querySelector('input[name="value"][placeholder*="mantenimiento"]');
        if (bannerInput) {
            bannerInput.addEventListener('input', function() {
                const preview = document.getElementById('bannerPreviewText');
                if (preview) preview.textContent = this.value || 'El mensaje del banner aparecerá aquí...';
            });
        }

        lucide.createIcons();
    </script>
</x-app-layout>
