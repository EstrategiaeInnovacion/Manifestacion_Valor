<x-app-layout>
    <x-slot name="title">Seleccionar Solicitante para COVE</x-slot>

    <div class="min-h-screen bg-[#F8FAFC] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-10">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar al Dashboard
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Seleccione el <span class="text-[#003399]">Solicitante</span> para COVE
                </h2>
                <p class="text-slate-500 mt-2">
                    Escoja el solicitante / importador para cargar el archivo M del pedimento
                </p>
            </div>

            @if($applicants->isEmpty())
                <div class="bg-white rounded-lg p-12 text-center shadow-sm border border-slate-200">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-500 mb-4">
                        <i data-lucide="building-2" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[#001a4d] mt-2">No hay solicitantes registrados</h3>
                    <p class="text-slate-500 mt-2 mb-6">Debe registrar al menos un solicitante para poder gestionar COVEs.</p>
                    <a href="{{ route('applicants.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#003399] hover:bg-[#002266]">
                        Gestionar Solicitantes
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($applicants as $applicant)
                        <div class="bg-white rounded-lg p-6 shadow-sm border border-slate-200 hover:shadow-md transition-all group flex flex-col justify-between">
                            <div>
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                        <i data-lucide="building-2" class="w-6 h-6"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Solicitante</span>
                                        <h4 class="font-bold text-[#001a4d] truncate">{{ $applicant->business_name }}</h4>
                                        <p class="text-sm text-slate-500">RFC: {{ $applicant->applicant_rfc }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col gap-2">
                                <a href="{{ route('coves.upload', $applicant->id) }}" class="flex items-center justify-between px-4 py-2 bg-slate-50 text-xs font-bold text-slate-700 rounded-md border border-slate-200 hover:bg-[#003399] hover:text-white hover:border-[#003399] transition-all">
                                    <span>Importar Archivo M</span>
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('coves.manual-create', $applicant->id) }}" class="flex items-center justify-between px-4 py-2 bg-slate-50 text-xs font-bold text-slate-700 rounded-md border border-slate-200 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all">
                                    <span>Registro Manual</span>
                                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
