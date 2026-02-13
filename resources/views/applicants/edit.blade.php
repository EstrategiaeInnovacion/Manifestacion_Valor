<x-app-layout>
    <x-slot name="title">Editar Solicitante</x-slot>
    @vite(['resources/css/applicant-form.css', 'resources/js/applicant-form.js'])

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
                        <span class="hidden md:block text-sm font-bold text-[#001a4d] uppercase tracking-wider">Editar Solicitante</span>
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
                <a href="{{ route('applicants.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-[#003399] transition-colors mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                    Regresar a Lista de Solicitantes
                </a>
                
                <h2 class="text-4xl font-black text-[#001a4d] tracking-tight">Editar <span class="text-[#003399]">Solicitante</span></h2>
                <p class="text-slate-500 mt-2">Actualice los datos fiscales del solicitante</p>
            </div>

            @if ($errors->any())
                <div class="alert-error">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <div>
                        <p class="font-semibold">Se encontraron los siguientes errores:</p>
                        <ul class="list-disc list-inside mt-2 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="form-card">
                <form method="POST" action="{{ route('applicants.update', $applicant) }}">
                    @csrf
                    @method('PUT')

                    {{-- Datos del Solicitante --}}
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                            Datos del Solicitante
                        </h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="user_email" class="form-label">Correo Electrónico del Usuario</label>
                                <input type="text" class="form-input form-input-readonly" value="{{ $applicant->user_email }}" readonly>
                                <p class="text-xs text-slate-500 mt-1">El usuario asociado no puede ser modificado</p>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="applicant_rfc" class="form-label">RFC del Solicitante</label>
                                <input type="text" id="applicant_rfc" name="applicant_rfc" value="{{ old('applicant_rfc', $applicant->applicant_rfc) }}" 
                                       class="form-input" maxlength="13" required>
                            </div>

                            <div class="form-group">
                                <label for="business_name" class="form-label">Razón Social</label>
                                <input type="text" id="business_name" name="business_name" value="{{ old('business_name', $applicant->business_name) }}" 
                                       class="form-input" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="main_economic_activity" class="form-label">Actividad Económica Preponderante</label>
                                <textarea id="main_economic_activity" name="main_economic_activity" class="form-input" rows="3" required>{{ old('main_economic_activity', $applicant->main_economic_activity) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Domicilio Fiscal --}}
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                            Domicilio Fiscal
                        </h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="country" class="form-label">País</label>
                                <input type="text" id="country" name="country" value="{{ old('country', $applicant->country) }}" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="postal_code" class="form-label">Código Postal</label>
                                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $applicant->postal_code) }}" 
                                       class="form-input" maxlength="10" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="state" class="form-label">Estado</label>
                                <input type="text" id="state" name="state" value="{{ old('state', $applicant->state) }}" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="municipality" class="form-label">Municipio</label>
                                <input type="text" id="municipality" name="municipality" value="{{ old('municipality', $applicant->municipality) }}" 
                                       class="form-input" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="locality" class="form-label">Localidad (Opcional)</label>
                                <input type="text" id="locality" name="locality" value="{{ old('locality', $applicant->locality) }}" class="form-input">
                            </div>

                            <div class="form-group">
                                <label for="neighborhood" class="form-label">Colonia</label>
                                <input type="text" id="neighborhood" name="neighborhood" value="{{ old('neighborhood', $applicant->neighborhood) }}" 
                                       class="form-input" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="street" class="form-label">Calle</label>
                                <input type="text" id="street" name="street" value="{{ old('street', $applicant->street) }}" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="exterior_number" class="form-label">No. Exterior</label>
                                <input type="text" id="exterior_number" name="exterior_number" value="{{ old('exterior_number', $applicant->exterior_number) }}" 
                                       class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="interior_number" class="form-label">No. Interior (Opcional)</label>
                                <input type="text" id="interior_number" name="interior_number" value="{{ old('interior_number', $applicant->interior_number) }}" 
                                       class="form-input">
                            </div>
                        </div>
                    </div>

                    {{-- Datos de Contacto --}}
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i data-lucide="phone" class="w-5 h-5"></i>
                            Datos de Contacto
                        </h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="area_code" class="form-label">Lada</label>
                                <input type="text" id="area_code" name="area_code" value="{{ old('area_code', $applicant->area_code) }}" 
                                       class="form-input" maxlength="5" required>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone', $applicant->phone) }}" 
                                       class="form-input" maxlength="20" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('applicants.index') }}" class="btn-secondary">
                            <i data-lucide="x" class="w-5 h-5 mr-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn-primary">
                            <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                            Actualizar Solicitante
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-app-layout>
