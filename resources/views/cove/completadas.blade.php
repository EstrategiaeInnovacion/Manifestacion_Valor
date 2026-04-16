<x-app-layout>
    <x-slot name="title">COVEs Completados</x-slot>

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
                        COVE <span class="text-[#003399]">Completados</span>
                    </h2>
                    <p class="text-slate-500 mt-2">Historial de documentos firmados y transmitidos exitosamente.</p>
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
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha Transmisión
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Solicitante
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Número Operación
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            e-Document
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($coves as $cove)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $cove->updated_at->format('d/m/Y H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $cove->applicant->applicant_name ?? 'Desconocido' }}</div>
                                                <div class="text-sm text-gray-500">{{ $cove->applicant->applicant_rfc ?? '' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-gray-100 text-gray-800">
                                                    {{ $cove->numero_operacion }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $cove->e_document ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <!-- Action to view XML/Response -->
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $coves->links() }}
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-500">
                            <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium">Aún no hay COVEs completados</p>
                            <p class="text-sm mt-1">Los COVEs firmados y aceptados por VUCEM aparecerán aquí.</p>
                        </div>
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
