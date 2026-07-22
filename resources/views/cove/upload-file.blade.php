<x-app-layout>
    <x-slot name="title">Cargar Archivo M para COVE</x-slot>

    <div class="min-h-screen bg-[#F8FAFC] py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('coves.select-applicant') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Cambiar Solicitante
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Cargar <span class="text-[#003399]">Archivo M</span> para COVE
                </h2>
                <p class="text-slate-500 mt-2">Sube el archivo de texto del pedimento para pre-llenar los datos del COVE</p>
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md text-red-700 flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <p class="text-sm font-semibold">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Info Solicitante --}}
            <div class="bg-white border border-slate-200 rounded-lg p-6 mb-8 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="building-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[#001a4d]">{{ $applicant->business_name }}</h3>
                        <p class="text-sm text-slate-500">RFC: <span class="font-bold text-[#003399]">{{ $applicant->applicant_rfc }}</span></p>
                    </div>
                </div>
            </div>

            {{-- Formulario --}}
            <div class="bg-white border border-slate-200 rounded-lg p-8 shadow-sm">
                <form action="{{ route('coves.store', $applicant) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="border-2 border-dashed border-slate-300 rounded-lg p-10 text-center hover:border-[#003399] transition-colors relative" id="dropArea">
                        <input type="file" name="archivo_m" id="archivo_m" accept="*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="fileSelected(this)">
                        
                        <div id="prompt">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-500 mb-4">
                                <i data-lucide="file-text" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#001a4d]">Arrastra tu archivo aquí</h4>
                            <p class="text-sm text-slate-500 mt-1">o haz clic para seleccionar</p>
                            <p class="text-xs text-slate-400 mt-4">Soporta formatos planos de pedimento (.txt, .287, etc.) · máx. 2MB</p>
                        </div>

                        <div id="file-details" class="hidden">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mb-4">
                                <i data-lucide="file-check" class="w-8 h-8"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#001a4d]" id="selected-file-name">archivo.txt</h4>
                            <p class="text-sm text-slate-500 mt-1" id="selected-file-size">120 KB</p>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between items-center">
                        <a href="{{ route('dashboard') }}" class="px-5 py-2 border border-slate-300 text-sm font-bold text-slate-700 rounded-md hover:bg-slate-50">
                            Cancelar
                        </a>
                        <button type="submit" id="submitBtn" class="px-6 py-2 bg-[#003399] text-sm font-bold text-white rounded-md hover:bg-[#002266] disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Importar y Crear Borrador
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function fileSelected(input) {
            const file = input.files[0];
            if (file) {
                document.getElementById('prompt').classList.add('hidden');
                document.getElementById('file-details').classList.remove('hidden');
                document.getElementById('selected-file-name').textContent = file.name;
                document.getElementById('selected-file-size').textContent = (file.size / 1024).toFixed(2) + ' KB';
                document.getElementById('submitBtn').disabled = false;
            }
        }
    </script>
</x-app-layout>
