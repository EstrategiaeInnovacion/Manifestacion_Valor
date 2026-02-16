{{-- Step 5: Vista Previa --}}
<div id="step-5" class="step-content hidden" data-step="5">
    <div class="mve-section-card">
        <div class="mve-card-header bg-gradient-to-r from-[#003399] to-[#0055cc]">
            <div class="mve-card-icon bg-white/20 text-white">
                <i data-lucide="eye" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="mve-card-title text-white">Vista Previa de la Manifestación</h3>
                <p class="text-blue-200 text-sm">Revise cuidadosamente todos los datos antes de confirmar</p>
            </div>
        </div>
        <div class="mve-card-body p-0">
            <div id="stepPreviewContent" class="p-6">
                {{-- Se llena dinámicamente por JS --}}
                <div class="flex items-center justify-center py-16 text-slate-400">
                    <span class="inline-block w-6 h-6 border-2 border-slate-300 border-t-transparent rounded-full animate-spin mr-3"></span>
                    Cargando vista previa...
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones finales de Step 5 --}}
    <div class="mt-6 flex flex-col gap-4">
        {{-- Mensaje informativo --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0"></i>
                <p class="text-sm text-blue-700">Revise cuidadosamente todos los datos. Al confirmar, la manifestación se guardará como <strong>completada</strong> y podrá proceder a firmarla y enviarla a VUCEM.</p>
            </div>
        </div>

        {{-- Botones --}}
        <div class="flex justify-between items-center">
            <div class="flex gap-3">
                <button type="button" onclick="prevStep()" class="btn-secondary-large">
                    <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                    Volver a Editar
                </button>
                <button type="button" onclick="confirmarBorrarBorrador()" class="btn-danger-large">
                    <i data-lucide="trash-2" class="w-5 h-5 mr-2"></i>
                    Borrar Borrador
                </button>
            </div>

            <button type="button" id="btnConfirmarManifestacion" onclick="confirmarGuardadoFinal()" class="btn-primary-large bg-green-600 hover:bg-green-700">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                Confirmar y Guardar Manifestación
            </button>
        </div>
    </div>
</div>{{-- /step-5 --}}
