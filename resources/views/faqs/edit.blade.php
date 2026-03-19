<x-app-layout>
    <x-slot name="title">Editar Pregunta Frecuente</x-slot>
    @vite(['resources/css/users-list.css'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Editar Pregunta Frecuente</span>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Usuario Conectado</p>
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

        <main class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div class="mb-8">
                <a href="{{ route('faqs.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-[#003399] transition-colors mb-5">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Volver a Preguntas Frecuentes
                </a>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-600 to-cyan-400 flex items-center justify-center shadow-lg">
                        <i data-lucide="pencil" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-[#001a4d] tracking-tight">Editar Pregunta</h1>
                        <p class="text-slate-500 text-sm mt-1">Modifica la pregunta frecuente seleccionada.</p>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-600"></i>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Formulario --}}
            <form method="POST" action="{{ route('faqs.update', $faq) }}" enctype="multipart/form-data"
                  class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8 space-y-7">
                @csrf
                @method('PUT')

                {{-- Pregunta --}}
                <div>
                    <label for="question" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Pregunta <span class="text-red-500">*</span>
                    </label>
                    <textarea id="question" name="question" required rows="2" maxlength="500"
                        placeholder="¿Cuál es la pregunta frecuente?"
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all outline-none placeholder:text-slate-300 resize-none @error('question') border-red-400 @enderror">{{ old('question', $faq->question) }}</textarea>
                    @error('question')
                        <p class="mt-1.5 text-xs text-red-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Respuesta --}}
                <div>
                    <label for="answer" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Respuesta <span class="text-red-500">*</span>
                    </label>
                    <textarea id="answer" name="answer" required rows="8" maxlength="20000"
                        placeholder="Escribe la respuesta detallada aquí."
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all outline-none placeholder:text-slate-300 resize-y @error('answer') border-red-400 @enderror">{{ old('answer', $faq->answer) }}</textarea>
                    @error('answer')
                        <p class="mt-1.5 text-xs text-red-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Orden y visibilidad --}}
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Orden de aparición
                        </label>
                        <input type="number" id="sort_order" name="sort_order" min="0"
                            value="{{ old('sort_order', $faq->sort_order) }}"
                            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all outline-none @error('sort_order') border-red-400 @enderror">
                        <p class="text-xs text-slate-400 mt-1">Número menor aparece primero.</p>
                    </div>
                    <div class="flex flex-col justify-center">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Visibilidad
                        </label>
                        <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" id="is_published"
                                   {{ old('is_published', $faq->is_published ? '1' : '0') == '1' ? 'checked' : '' }}
                                   class="w-5 h-5 text-cyan-600 border-slate-300 rounded focus:ring-cyan-400 cursor-pointer">
                            <span class="text-sm font-semibold text-[#001a4d]">Publicada</span>
                        </label>
                        <p class="text-xs text-slate-400 mt-1">Si no está publicada, solo el SuperAdmin la verá.</p>
                    </div>
                </div>

                {{-- Archivos existentes --}}
                @if($faq->attachments->isNotEmpty())
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                            Archivos actuales
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($faq->attachments as $attachment)
                                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                    @if($attachment->isImage())
                                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-slate-200 shrink-0">
                                            <img src="{{ route('faqs.attachment', $attachment) }}"
                                                 alt="{{ $attachment->original_name }}"
                                                 class="w-full h-full object-cover" />
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                            <i data-lucide="file-text" class="w-5 h-5 text-red-500"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-[#001a4d] truncate">{{ $attachment->original_name }}</p>
                                        <p class="text-xs text-slate-400">{{ $attachment->humanSize() }}</p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <a href="{{ route('faqs.attachment', $attachment) }}"
                                           target="_blank"
                                           class="p-1.5 text-slate-400 hover:text-[#003399] hover:bg-blue-50 rounded-lg transition-colors"
                                           title="Ver">
                                            <i data-lucide="external-link" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('faqs.attachment.destroy', $attachment) }}"
                                              onsubmit="return confirm('¿Eliminar este archivo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Eliminar archivo">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Agregar nuevos archivos --}}
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Agregar archivos
                        <span class="normal-case font-normal text-slate-300 ml-1">(opcional, máx. 10 archivos — imágenes o PDF)</span>
                    </label>
                    <label for="faqAttachments"
                           class="flex flex-col items-center justify-center w-full p-6 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-cyan-400 hover:bg-cyan-50/50 transition-all group">
                        <i data-lucide="paperclip" class="w-6 h-6 text-slate-300 group-hover:text-cyan-500 transition-colors mb-2"></i>
                        <span class="text-sm font-medium text-slate-400 group-hover:text-cyan-600 transition-colors">Haz clic para adjuntar más archivos</span>
                        <span class="text-xs text-slate-300 mt-0.5">PNG, JPG, GIF, WEBP, PDF — Máx. 10 MB por archivo</span>
                    </label>
                    <input type="file" id="faqAttachments" name="attachments[]" multiple
                           accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                           class="hidden"
                           onchange="previewAttachments(this)">
                    @error('attachments.*')
                        <p class="mt-1.5 text-xs text-red-600 font-semibold">{{ $message }}</p>
                    @enderror
                    <div id="attachmentPreview" class="flex flex-wrap gap-2 mt-3"></div>
                </div>

                {{-- Botones --}}
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <a href="{{ route('faqs.index') }}"
                       class="px-6 py-3 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors rounded-xl">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-8 py-3 bg-[#001a4d] hover:bg-[#003399] text-white font-bold text-sm rounded-xl transition-all shadow-lg flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        function previewAttachments(input) {
            const preview = document.getElementById('attachmentPreview');
            preview.innerHTML = '';
            const files = Array.from(input.files).slice(0, 10);
            files.forEach(file => {
                const isImage = file.type.startsWith('image/');
                const item = document.createElement('div');
                item.className = 'flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 font-medium';
                if (isImage) {
                    const img = document.createElement('img');
                    img.className = 'w-8 h-8 object-cover rounded-lg';
                    const reader = new FileReader();
                    reader.onload = e => img.src = e.target.result;
                    reader.readAsDataURL(file);
                    item.appendChild(img);
                } else {
                    item.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
                }
                const nameEl = document.createElement('span');
                nameEl.className = 'truncate max-w-xs';
                nameEl.textContent = file.name;
                item.appendChild(nameEl);
                preview.appendChild(item);
            });
        }
    </script>
</x-app-layout>
