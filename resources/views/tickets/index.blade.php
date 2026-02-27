<x-app-layout>
    <x-slot name="title">Tickets de Soporte</x-slot>

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegación --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Tickets de Soporte</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-slate-500 hover:text-[#001a4d] transition-colors rounded-xl hover:bg-slate-50">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            {{-- Título + filtros (solo SuperAdmin) --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-black text-[#001a4d]">
                        {{ auth()->user()->role === 'SuperAdmin' ? 'Todos los Tickets' : 'Mis Tickets' }}
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">
                        {{ $tickets->total() }} {{ $tickets->total() === 1 ? 'ticket' : 'tickets' }} encontrados
                    </p>
                </div>

                @if(auth()->user()->role === 'SuperAdmin')
                    <div class="flex items-center gap-2">
                        @foreach([''=>'Todos', 'open'=>'Abiertos', 'in_progress'=>'En Proceso', 'closed'=>'Cerrados'] as $val => $label)
                            <a href="{{ route('tickets.index', $val ? ['status'=>$val] : []) }}"
                               class="px-4 py-2 rounded-xl text-sm font-bold transition-all
                                   {{ $status === ($val ?: null) || ($val === '' && !$status)
                                       ? 'bg-[#001a4d] text-white shadow'
                                       : 'bg-white text-slate-500 border border-slate-200 hover:border-[#003399] hover:text-[#003399]' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Flash --}}
            @if(session('success'))
                <div class="mb-6 p-4 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Lista --}}
            @if($tickets->isEmpty())
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-16 text-center">
                    <i data-lucide="inbox" class="w-12 h-12 text-slate-200 mx-auto mb-4"></i>
                    <p class="text-slate-400 font-semibold text-lg">No hay tickets</p>
                    <p class="text-slate-300 text-sm mt-1">
                        {{ auth()->user()->role === 'SuperAdmin' ? 'Aún no se han creado tickets de soporte.' : 'Aún no has creado ningún ticket. Usa el botón "Contactar Soporte" en el dashboard.' }}
                    </p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($tickets as $ticket)
                        @php
                            $colors = ['open'=>'amber','in_progress'=>'blue','closed'=>'slate'];
                            $color  = $colors[$ticket->status] ?? 'slate';
                            $labels = ['open'=>'Abierto','in_progress'=>'En Proceso','closed'=>'Cerrado'];
                        @endphp
                        <a href="{{ route('tickets.show', $ticket) }}"
                           class="flex items-center gap-5 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-[#003399]/30 transition-all p-5 group">

                            {{-- Status dot --}}
                            <div class="w-3 h-3 rounded-full flex-shrink-0
                                {{ $ticket->status === 'open' ? 'bg-amber-400' : ($ticket->status === 'in_progress' ? 'bg-blue-500' : 'bg-slate-300') }}">
                            </div>

                            {{-- Main info --}}
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="text-xs font-bold text-slate-400">#{{ $ticket->id }}</span>
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full
                                        {{ $ticket->status === 'open' ? 'bg-amber-50 text-amber-600' : ($ticket->status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-slate-50 text-slate-400') }}">
                                        {{ $labels[$ticket->status] ?? $ticket->status }}
                                    </span>
                                    <span class="text-xs text-slate-300 bg-slate-50 px-2.5 py-0.5 rounded-full font-medium">{{ $ticket->category }}</span>
                                </div>
                                <p class="font-bold text-[#001a4d] truncate group-hover:text-[#003399] transition-colors">{{ $ticket->subject }}</p>
                                @if(auth()->user()->role === 'SuperAdmin')
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $ticket->user->full_name }} · {{ $ticket->user->email }}</p>
                                @endif
                            </div>

                            {{-- Date + arrow --}}
                            <div class="text-right flex-shrink-0 hidden sm:block">
                                <p class="text-xs text-slate-400">{{ $ticket->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-slate-300">{{ $ticket->created_at->diffForHumans() }}</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-5 h-5 text-slate-300 group-hover:text-[#003399] flex-shrink-0 transition-colors"></i>
                        </a>
                    @endforeach
                </div>

                {{-- Paginación --}}
                @if($tickets->hasPages())
                    <div class="mt-8">{{ $tickets->links() }}</div>
                @endif
            @endif
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
    </script>
</x-app-layout>
