<x-app-layout>
    <x-slot name="title">Cargar Archivo MVE</x-slot>
    @vite(['resources/css/mve-upload.css', 'resources/js/mve-upload.js'])

    <div class="min-h-screen bg-[#F8FAFC]">
        {{-- Navegación --}}
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}">
                            <img src="{{ asset('logo-ei.png') }}" alt="Logo E&I" class="h-10 w-auto">
                        </a>
                        <div class="hidden md:block h-8 w-px bg-slate-200"></div>
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Cargar Archivo M</span>
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

        <main class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('mve.select-applicant', ['mode' => 'archivo_m']) }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Cambiar Solicitante
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">
                    Cargar <span class="text-[#003399]">Archivo M</span>
                </h2>
                <p class="text-slate-500 mt-2">Sube el archivo de texto para completar automáticamente la MVE</p>
            </div>

            @if(session('success'))
                <div class="alert-success">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert-error">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Información del Solicitante --}}
            <div class="applicant-info-card">
                <div class="flex items-center gap-4">
                    <div class="applicant-info-icon">
                        <i data-lucide="building-2" class="w-6 h-6"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-[#001a4d]">{{ $applicant->business_name }}</h3>
                        <p class="text-sm text-slate-500 mt-1">RFC: <span class="font-semibold text-[#003399]">{{ $applicant->applicant_rfc }}</span></p>
                    </div>
                </div>
            </div>

            {{-- Formulario de carga --}}
            <div class="upload-card">
                <form action="{{ route('mve.process-file', $applicant) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="upload-area" id="uploadArea">
                        <input type="file" name="archivo_m" id="archivoM" accept=".txt" class="hidden" onchange="handleFileSelect(event)">
                        
                        <div id="uploadPrompt" class="upload-prompt">
                            <div class="upload-icon">
                                <i data-lucide="file-text" class="w-12 h-12"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#001a4d] mt-4">Arrastra tu archivo aquí</h4>
                            <p class="text-sm text-slate-500 mt-2">o haz clic para seleccionar</p>
                            <button type="button" onclick="document.getElementById('archivoM').click()" class="btn-secondary mt-6">
                                <i data-lucide="upload" class="w-5 h-5 mr-2"></i>
                                Seleccionar Archivo
                            </button>
                            <p class="text-xs text-slate-400 mt-4">Solo archivos .txt (máx. 2MB)</p>
                        </div>

                        <div id="fileInfo" class="file-info hidden">
                            <div class="file-icon">
                                <i data-lucide="file-check" class="w-8 h-8"></i>
                            </div>
                            <div class="flex-1">
                                <p class="file-name" id="fileName"></p>
                                <p class="file-size" id="fileSize"></p>
                            </div>
                            <button type="button" onclick="clearFile()" class="btn-icon-danger">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('dashboard') }}" class="btn-secondary">
                            <i data-lucide="x" class="w-5 h-5 mr-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn-primary" id="submitBtn" disabled>
                            <i data-lucide="arrow-right" class="w-5 h-5 mr-2"></i>
                            Procesar Archivo
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-app-layout>
