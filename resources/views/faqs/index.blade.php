<x-app-layout>
    <x-slot name="title">Preguntas Frecuentes</x-slot>
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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Preguntas Frecuentes</span>
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

        <main class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-[#003399] transition-colors mb-5">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-600 to-cyan-400 flex items-center justify-center shadow-lg shrink-0">
                            <i data-lucide="circle-help" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-[#001a4d] tracking-tight">Preguntas Frecuentes</h1>
                            <p class="text-slate-500 text-sm mt-1">Consulta las respuestas a las dudas más comunes sobre el sistema y la MVE.</p>
                        </div>
                    </div>
                    @if($isSuperAdmin)
                        <a href="{{ route('faqs.create') }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#001a4d] hover:bg-[#003399] text-white font-bold text-sm rounded-xl transition-all shadow-lg shrink-0">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Nueva Pregunta
                        </a>
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-600"></i>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl">
                    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-red-600"></i>
                    <span class="font-semibold text-sm">{{ session('error') }}</span>
                </div>
            @endif

            @if($faqs->isEmpty())
                <div class="text-center py-24">
                    <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="circle-help" class="w-10 h-10 text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-400">No hay preguntas publicadas aún</h3>
                    <p class="text-slate-400 text-sm mt-2">El administrador aún no ha registrado preguntas frecuentes.</p>
                </div>
            @else
                <div class="space-y-3" id="faqAccordion">
                    @foreach($faqs as $index => $faq)
                        <div class="faq-item bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                             id="faq-{{ $faq->id }}">

                            {{-- Cabecera --}}
                            <div class="faq-header flex items-center gap-2 px-6 py-5">
                                {{-- Zona clickeable para expandir --}}
                                <button type="button"
                                        class="flex items-center gap-4 min-w-0 flex-1 text-left focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:ring-inset rounded-lg"
                                        onclick="toggleFaq({{ $faq->id }})">
                                    <span class="text-xs font-black text-slate-400 tabular-nums shrink-0">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="font-bold text-[#001a4d] text-base leading-snug">{{ $faq->question }}</span>
                                    @if($isSuperAdmin && !$faq->is_published)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full shrink-0">
                                            <i data-lucide="eye-off" class="w-3 h-3"></i> No publicada
                                        </span>
                                    @endif
                                </button>

                                {{-- Acciones (fuera del button para evitar HTML inválido) --}}
                                <div class="flex items-center gap-1 shrink-0">
                                    @if($isSuperAdmin)
                                        <a href="{{ route('faqs.edit', $faq) }}"
                                           class="p-1.5 text-slate-400 hover:text-[#003399] hover:bg-blue-50 rounded-lg transition-colors"
                                           title="Editar">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('faqs.destroy', $faq) }}"
                                              onsubmit="return confirm('¿Eliminar esta pregunta? Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Eliminar">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button type="button"
                                            onclick="toggleFaq({{ $faq->id }})"
                                            class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                        <i data-lucide="chevron-down" class="w-5 h-5 faq-chevron transition-transform duration-300" id="chevron-{{ $faq->id }}"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Contenido --}}
                            <div class="faq-body hidden px-6 pb-6" id="body-{{ $faq->id }}">
                                <div class="border-t border-slate-100 pt-5">
                                    <div class="prose prose-slate prose-sm max-w-none text-slate-600 leading-relaxed">
                                        {!! nl2br(e($faq->answer)) !!}
                                    </div>

                                    {{-- Adjuntos --}}
                                    @if($faq->attachments->isNotEmpty())
                                        <div class="mt-6">
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                                Archivos adjuntos
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                @foreach($faq->attachments as $attachment)
                                                    <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl group/att">
                                                        @if($attachment->isImage())
                                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-slate-200 shrink-0">
                                                                <img src="{{ route('faqs.attachment', $attachment) }}"
                                                                     alt="{{ $attachment->original_name }}"
                                                                     class="w-full h-full object-cover cursor-pointer"
                                                                     onclick="openImageLightbox(this.src, '{{ e($attachment->original_name) }}')" />
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
                                                               title="Ver / Descargar">
                                                                <i data-lucide="external-link" class="w-4 h-4"></i>
                                                            </a>
                                                            @if($isSuperAdmin)
                                                                <form method="POST" action="{{ route('faqs.attachment.destroy', $attachment) }}"
                                                                      onsubmit="return confirm('¿Eliminar este archivo?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                                            title="Eliminar archivo">
                                                                        <i data-lucide="x" class="w-4 h-4"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </main>
    </div>

    {{-- Lightbox para imágenes --}}
    <div id="imageLightbox" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 hidden p-4" onclick="closeLightbox()">
        <div class="relative max-w-5xl w-full" onclick="event.stopPropagation()">
            <button onclick="closeLightbox()" class="absolute -top-10 right-0 text-white/80 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <img id="lightboxImg" src="" alt="" class="max-h-[85vh] w-full object-contain rounded-xl shadow-2xl">
            <p id="lightboxCaption" class="text-white/70 text-sm text-center mt-3"></p>
        </div>
    </div>

    <script>
        function toggleFaq(id) {
            const body    = document.getElementById('body-' + id);
            const chevron = document.getElementById('chevron-' + id);
            const isOpen  = !body.classList.contains('hidden');

            // Cerrar todos
            document.querySelectorAll('.faq-body').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.faq-chevron').forEach(el => el.style.transform = '');

            if (!isOpen) {
                body.classList.remove('hidden');
                chevron.style.transform = 'rotate(180deg)';
            }
        }

        function openImageLightbox(src, caption) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightboxCaption').textContent = caption;
            document.getElementById('imageLightbox').classList.remove('hidden');
        }

        function closeLightbox() {
            document.getElementById('imageLightbox').classList.add('hidden');
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeLightbox();
        });

        // Abrir la pregunta si viene con hash en URL (#faq-N)
        document.addEventListener('DOMContentLoaded', () => {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#faq-')) {
                const id = hash.replace('#faq-', '');
                const el = document.getElementById('faq-' + id);
                if (el) {
                    toggleFaq(id);
                    setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
                }
            }
        });
    </script>
</x-app-layout>
