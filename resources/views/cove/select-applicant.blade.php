<x-app-layout>
    <x-slot name="title">COVE - Seleccionar Solicitante</x-slot>

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

        <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <div class="flex items-center gap-4 mb-6">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                        Volver al Dashboard
                    </a>
                </div>

                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Nuevo <span class="text-[#003399]">COVE</span>
                </h2>
                <p class="text-slate-500 mt-2">Seleccione el Importador / Exportador para el cual desea generar el comprobante.</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm border border-slate-200 sm:rounded-2xl">
                <div class="p-8">
                    @if($applicants->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($applicants as $applicant)
                                <a href="{{ route('cove.create-manual', $applicant->id) }}" class="group bg-slate-50 border border-slate-200 rounded-xl p-6 hover:shadow-lg hover:border-[#003399] transition-all cursor-pointer flex flex-col justify-between">
                                    <div>
                                        <div class="w-12 h-12 bg-blue-100 text-[#003399] rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                            <i data-lucide="building" class="w-6 h-6"></i>
                                        </div>
                                        <h3 class="text-xl font-bold text-[#001a4d]">{{ $applicant->applicant_rfc }}</h3>
                                        <p class="text-sm text-slate-500 mt-2 font-medium line-clamp-2 h-10">{{ $applicant->applicant_name ?? $applicant->business_name }}</p>
                                    </div>
                                    <div class="mt-6 pt-4 border-t border-slate-200 flex justify-end">
                                        <span class="inline-flex items-center text-sm font-bold text-[#003399] group-hover:translate-x-1 transition-transform">
                                            Seleccionar <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500">No tiene solicitantes registrados o asignados a su cuenta.</p>
                            <a href="{{ route('applicants.create') }}" class="mt-4 inline-block text-blue-600 hover:underline">Registrar nuevo solicitante</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</x-app-layout>
