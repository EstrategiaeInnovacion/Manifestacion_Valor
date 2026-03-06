<x-app-layout>
    <x-slot name="title">Ticket #{{ $ticket->id }}</x-slot>

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegación --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Ticket #{{ $ticket->id }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('tickets.index') }}"
                           class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-slate-500 hover:text-[#001a4d] transition-colors rounded-xl hover:bg-slate-50">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Tickets
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            {{-- Flash --}}
            @if(session('success'))
                <div class="mb-6 p-4 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Columna izquierda: info del ticket + hilo --}}
                <div class="lg:col-span-2 space-y-5">

                    {{-- Info del ticket --}}
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-xs font-bold text-slate-400">#{{ $ticket->id }}</span>
                                    <span class="text-xs font-bold px-3 py-1 rounded-full
                                        {{ $ticket->status === 'open' ? 'bg-amber-50 text-amber-600' : ($ticket->status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-slate-50 text-slate-400') }}">
                                        {{ $ticket->statusLabel() }}
                                    </span>
                                    <span class="text-xs text-slate-400 bg-slate-50 px-2.5 py-1 rounded-full font-medium">{{ $ticket->category }}</span>
                                </div>
                                <h1 class="text-xl font-black text-[#001a4d]">{{ $ticket->subject }}</h1>
                                <p class="text-sm text-slate-400 mt-1">
                                    Creado por <strong class="text-slate-600">{{ $ticket->user->full_name }}</strong>
                                    · {{ $ticket->created_at->format('d/m/Y H:i') }}
                                    ({{ $ticket->created_at->diffForHumans() }})
                                </p>
                            </div>
                        </div>

                        {{-- Descripción original --}}
                        <div class="bg-slate-50 rounded-2xl p-4">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Descripción</p>
                            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $ticket->description }}</p>
                        </div>
                    </div>

                    {{-- Hilo de mensajes --}}
                    @if($ticket->messages->count())
                        <div class="space-y-4">
                            @foreach($ticket->messages as $msg)
                                <div class="bg-white rounded-2xl border shadow-sm p-5
                                    {{ $msg->is_support_response ? 'border-blue-100 bg-blue-50/30' : 'border-slate-100' }}">

                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm
                                                {{ $msg->is_support_response ? 'bg-[#001a4d] text-white' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $msg->is_support_response ? 'S' : strtoupper(substr($msg->sender->full_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-[#001a4d]">{{ $msg->is_support_response ? 'Soporte Técnico' : $msg->sender->full_name }}</p>
                                                <p class="text-xs text-slate-400">
                                                    {{ $msg->is_support_response ? 'Equipo de Soporte' : 'Usuario' }}
                                                    · {{ $msg->created_at->format('d/m/Y H:i') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $msg->body }}</p>

                                    {{-- Adjuntos del mensaje --}}
                                    @if($msg->attachments->count())
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach($msg->attachments as $att)
                                                <a href="{{ route('tickets.attachment', $att) }}"
                                                   class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-600 hover:border-[#003399] hover:text-[#003399] transition-colors">
                                                    <i data-lucide="image" class="w-3.5 h-3.5"></i>
                                                    {{ $att->original_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Columna derecha: acciones SuperAdmin --}}
                <div class="space-y-5">

                    {{-- Cambiar estatus (SuperAdmin) --}}
                    @if(auth()->user()->role === 'SuperAdmin')
                        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                            <h3 class="text-sm font-black text-[#001a4d] uppercase tracking-wider mb-4">Estatus</h3>
                            <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                                @csrf @method('PATCH')
                                <div class="space-y-2 mb-4">
                                    @foreach(['open'=>['Abierto','amber'],'in_progress'=>['En Proceso','blue'],'closed'=>['Cerrado','slate']] as $val => [$label,$col])
                                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all
                                            {{ $ticket->status === $val ? 'border-[#003399] bg-blue-50/50' : 'border-slate-200 hover:border-slate-300' }}">
                                            <input type="radio" name="status" value="{{ $val }}"
                                                   {{ $ticket->status === $val ? 'checked' : '' }}
                                                   class="accent-[#003399]">
                                            <span class="text-sm font-bold
                                                {{ $col === 'amber' ? 'text-amber-600' : ($col === 'blue' ? 'text-blue-600' : 'text-slate-400') }}">
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <button type="submit"
                                        class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-all">
                                    Actualizar Estatus
                                </button>
                            </form>
                        </div>

                        {{-- Formulario de respuesta --}}
                        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                            <h3 class="text-sm font-black text-[#001a4d] uppercase tracking-wider mb-4">
                                💬 Enviar Respuesta
                            </h3>
                            <form method="POST" action="{{ route('tickets.respond', $ticket) }}" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-4">
                                    <textarea name="body" rows="5" required maxlength="5000"
                                        placeholder="Escribe tu respuesta al usuario..."
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-[#003399] focus:border-transparent transition-all outline-none placeholder:text-slate-300 resize-none">{{ old('body') }}</textarea>
                                    @error('body')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Cambiar estatus junto con la respuesta --}}
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Cambiar estatus al responder</label>
                                    <select name="status"
                                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-[#001a4d] font-medium focus:ring-2 focus:ring-[#003399] focus:border-transparent outline-none">
                                        <option value="">— Sin cambios —</option>
                                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Abierto</option>
                                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                                        <option value="closed">Cerrado</option>
                                    </select>
                                </div>

                                {{-- Adjuntos de la respuesta --}}
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                                        Capturas / Adjuntos <span class="normal-case font-normal text-slate-300">(opcional, máx. 5)</span>
                                    </label>
                                    <label for="responseAttachments"
                                        class="flex items-center justify-center gap-2 w-full p-3 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-[#003399] hover:bg-blue-50/50 transition-all group">
                                        <i data-lucide="image-plus" class="w-5 h-5 text-slate-300 group-hover:text-[#003399] transition-colors"></i>
                                        <span class="text-sm font-medium text-slate-400 group-hover:text-[#003399] transition-colors">Adjuntar imágenes</span>
                                    </label>
                                    <input type="file" id="responseAttachments" name="attachments[]" multiple
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        class="hidden" onchange="previewResponseFiles(this)">
                                    <div id="responseFilePreview" class="flex flex-wrap gap-2 mt-2"></div>
                                </div>

                                <button type="submit"
                                        class="w-full py-3 bg-[#001a4d] hover:bg-[#003399] text-white font-bold text-sm rounded-xl transition-all shadow-lg flex items-center justify-center gap-2">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                    Enviar Respuesta y Notificar
                                </button>
                            </form>
                        </div>
                    @else
                        {{-- Vista usuario: solo estatus --}}
                        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 text-center">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Estatus del Ticket</p>
                            <span class="inline-block px-5 py-2 rounded-full font-black text-sm
                                {{ $ticket->status === 'open' ? 'bg-amber-50 text-amber-600' : ($ticket->status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-slate-50 text-slate-400') }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                            @if($ticket->status === 'open')
                                <p class="text-xs text-slate-400 mt-3">Tu ticket está pendiente de revisión.</p>
                            @elseif($ticket->status === 'in_progress')
                                <p class="text-xs text-slate-400 mt-3">El equipo de soporte está atendiendo tu caso.</p>
                            @else
                                <p class="text-xs text-slate-400 mt-3">Este ticket ha sido cerrado.</p>
                            @endif
                        </div>

                        {{-- Botón cancelar (dueño del ticket) --}}
                        @if($ticket->canBeCancelledBy(auth()->user()))
                            <div class="bg-white rounded-3xl border border-red-100 shadow-sm p-6">
                                <h3 class="text-sm font-black text-red-500 uppercase tracking-wider mb-3">Cancelar Ticket</h3>
                                <p class="text-xs text-slate-400 mb-4">Si ya no necesitas asistencia, puedes cancelar este ticket.</p>
                                <form method="POST" action="{{ route('tickets.cancel', $ticket) }}"
                                      onsubmit="return confirm('¿Estás seguro de que deseas cancelar este ticket? Esta acción no se puede deshacer.')">
                                    @csrf
                                    <button type="submit"
                                            class="w-full py-2.5 border border-red-200 text-red-500 hover:bg-red-50 font-bold text-sm rounded-xl transition-all">
                                        Cancelar Ticket
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endif

                    {{-- Info del solicitante (SuperAdmin) --}}
                    @if(auth()->user()->role === 'SuperAdmin')
                        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                            <h3 class="text-sm font-black text-[#001a4d] uppercase tracking-wider mb-3">Usuario</h3>
                            <p class="font-bold text-[#001a4d]">{{ $ticket->user->full_name }}</p>
                            <p class="text-sm text-[#003399]">{{ $ticket->user->email }}</p>
                            <p class="text-xs text-slate-400 mt-1">Rol: {{ $ticket->user->role }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

        window.previewResponseFiles = function(input) {
            const preview = document.getElementById('responseFilePreview');
            preview.innerHTML = '';
            Array.from(input.files).slice(0, 5).forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = e => {
                    const wrap = document.createElement('div');
                    wrap.className = 'relative';
                    wrap.innerHTML = `
                        <img src="${e.target.result}" title="${file.name}"
                            class="h-14 w-14 object-cover rounded-xl border border-slate-200 shadow-sm">
                        <span class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-[#003399] rounded-full text-white text-[9px] flex items-center justify-center font-black">${i + 1}</span>
                    `;
                    preview.appendChild(wrap);
                };
                reader.readAsDataURL(file);
            });
        };
    </script>
</x-app-layout>
