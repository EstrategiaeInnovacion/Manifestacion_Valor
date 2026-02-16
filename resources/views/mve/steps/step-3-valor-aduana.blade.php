{{-- Step 3: Valor en Aduana --}}
<div id="step-3" class="step-content hidden" data-step="3">
{{-- 3. Valor en Aduana --}}
<div class="mve-section-card">
        <div class="mve-card-header">
            <div class="mve-card-icon bg-emerald-50 text-emerald-600">
                <i data-lucide="dollar-sign" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="mve-card-title">VALOR EN ADUANA</h3>
                <p class="mve-card-description">INFORMACIÓN DE VALORES Y MONTOS ADUANALES</p>
            </div>
        </div>
        <div class="mve-card-body">
            <form class="mve-form">
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label class="form-label">
                            Importe total del precio pagado (Sumatoria de los conceptos y deberán ser declarados en M N)
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="totalPrecioPagado" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">
                            IMPORTE TOTAL DEL PRECIO POR PAGAR (SUMATORIA DE LOS CONCEPTOS Y DEBERÁN SER DECLARADOS EN M N)
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="totalPrecioPorPagar" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group flex-1">
                        <label class="form-label">
                            Importe total de incrementables (Sumatoria de los conceptos y deberán ser declarados en M N)
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="totalIncrementables" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">
                            Importe total de decrementables (Sumatoria de los conceptos y deberán ser declarados en M N)
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="totalDecrementables" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        TOTAL DEL VALOR EN ADUANA (SUMATORIA DE LOS CONCEPTOS Y DEBERÁN SER DECLARADOS EN M N)
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="totalValorAduana" class="form-input" placeholder="0" value="0" step="0.01" min="0" max="999999999999999.999" oninput="validateMonetaryInput(this)">
                </div>

                <div class="form-note">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span>* Campos obligatorios</span>
                </div>

                {{-- Botón Guardar Sección --}}
                <div class="form-actions-save">
                    <button type="button" onclick="saveValorAduana()" class="btn-save-draft">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        GUARDAR VALOR EN ADUANA
                    </button>
                </div>

                {{-- Navegación Stepper --}}
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep()" class="btn-secondary-large">
                        <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                        Anterior
                    </button>
                    <button type="button" onclick="nextStep()" class="btn-primary-large">
                        Siguiente
                        <i data-lucide="arrow-right" class="w-5 h-5 ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>{{-- /step-3 --}}
