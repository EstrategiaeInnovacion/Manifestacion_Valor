<x-app-layout>
    <x-slot name="title">{{ $faq->question }}</x-slot>
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

        <main class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            {{-- Navegación --}}
            <a href="{{ route('faqs.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-[#003399] transition-colors mb-8">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                Volver a Preguntas Frecuentes
            </a>

            {{-- Tarjeta de pregunta --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-8 pt-8 pb-6 border-b border-slate-100">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-cyan-50 flex items-center justify-center shrink-0 mt-0.5">
                                <i data-lucide="circle-help" class="w-5 h-5 text-cyan-600"></i>
                            </div>
                            <h1 class="text-2xl font-black text-[#001a4d] leading-snug">{{ $faq->question }}</h1>
                        </div>
                        @if($isSuperAdmin)
                            <a href="{{ route('faqs.edit', $faq) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-all shrink-0">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                Editar
                            </a>
                        @endif
                    </div>
                </div>

                <div class="px-8 py-7">
                    <div class="text-slate-600 text-sm leading-relaxed whitespace-pre-line">{{ $faq->answer }}</div>

                    {{-- Adjuntos --}}
                    @if($faq->attachments->isNotEmpty())
                        <div class="mt-8 pt-6 border-t border-slate-100">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                Archivos adjuntos ({{ $faq->attachments->count() }})
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($faq->attachments as $attachment)
                                    <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                        @if($attachment->isImage())
                                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-slate-200 shrink-0">
                                                <img src="{{ route('faqs.attachment', $attachment) }}"
                                                     alt="{{ $attachment->original_name }}"
                                                     class="w-full h-full object-cover cursor-pointer"
                                                     onclick="openLightbox(this.src, '{{ e($attachment->original_name) }}')" />
                                            </div>
                                        @else
                                            <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                                <i data-lucide="file-text" class="w-6 h-6 text-red-500"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-[#001a4d] truncate">{{ $attachment->original_name }}</p>
                                            <p class="text-xs text-slate-400">{{ $attachment->humanSize() }}</p>
                                        </div>
                                        <a href="{{ route('faqs.attachment', $attachment) }}"
                                           target="_blank"
                                           class="p-2 text-slate-400 hover:text-[#003399] hover:bg-blue-50 rounded-lg transition-colors shrink-0"
                                           title="Ver / Descargar">
                                            <i data-lucide="external-link" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    {{-- Lightbox --}}
    <div id="lightbox" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 hidden p-4" onclick="closeLightbox()">
        <div class="relative max-w-5xl w-full" onclick="event.stopPropagation()">
            <button onclick="closeLightbox()" class="absolute -top-10 right-0 text-white/80 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <img id="lightboxImg" src="" alt="" class="max-h-[85vh] w-full object-contain rounded-xl shadow-2xl">
            <p id="lightboxCaption" class="text-white/70 text-sm text-center mt-3"></p>
        </div>
    </div>
    <script>
        function openLightbox(src, caption) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightboxCaption').textContent = caption;
            document.getElementById('lightbox').classList.remove('hidden');
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
    </script>
</x-app-layout>
