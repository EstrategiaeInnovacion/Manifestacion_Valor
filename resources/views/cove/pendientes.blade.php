<x-app-layout>
    <x-slot name="title">COVE Pendientes</x-slot>

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- NAVEGACIÓN PRINCIPAL (Para igualar al Dashboard) --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Módulo COVE</span>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Usuario Conectado</p>
                            <p class="text-sm font-black text-[#003399]">{{ auth()->user()->full_name ?? auth()->user()->name }}</p>
                        </div>
                        <div class="user-dropdown">
                            <div id="avatarButton" class="avatar-button h-10 w-10 bg-[#001a4d] rounded-full flex items-center justify-center text-white font-bold shadow-lg cursor-pointer">
                                {{ substr(auth()->user()->full_name ?? auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                        COVE <span class="text-[#003399]">Pendientes</span>
                    </h2>
                    <p class="text-slate-500 mt-2">Documentos en proceso, borradores o con error de validación.</p>
                </div>
                
                <div class="flex gap-3">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-[#003399] hover:bg-[#001a4d] text-white font-bold rounded-lg transition-all">
                        <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                        Volver al Dashboard
                    </a>
                    
                    <a href="{{ route('cove.select-applicant') }}" class="inline-flex items-center px-6 py-3 bg-white border border-[#003399] text-[#003399] hover:bg-slate-50 font-bold rounded-lg transition-all">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                        Nuevo COVE
                    </a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm border border-slate-200 sm:rounded-2xl">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    @if($coves->count() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID / Fecha</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo Op / Patente</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estatus</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($coves as $cove)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">#{{ $cove->id }}</div>
                                        <div class="text-sm text-gray-500">{{ $cove->updated_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $cove->applicant->applicant_name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $cove->applicant->applicant_rfc ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $cove->tipo_operacion ?? '--' }} <br>
                                        {{ $cove->patente_aduanal ?? '--' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($cove->status == 'borrador' || $cove->status == 'guardado')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Borrador</span>
                                        @elseif($cove->status == 'procesando_vucem')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Procesando VUCEM</span>
                                        @elseif($cove->status == 'error' || $cove->status == 'rechazado')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Error / Rechazado</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if(in_array($cove->status, ['borrador', 'guardado', 'error', 'rechazado']))
                                            <a href="{{ route('cove.create-manual', $cove->applicant_id) }}" class="text-indigo-600 hover:text-indigo-900">Continuar / Editar</a>
                                        @elseif($cove->status == 'procesando_vucem')
                                            <span class="text-gray-400">Por favor espere...</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $coves->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No hay COVEs pendientes.</p>
                    @endif
                </div>
            </div>
        </main>
    </div>
    
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</x-app-layout>
