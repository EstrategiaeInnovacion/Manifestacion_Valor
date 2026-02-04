// Applicant Form Page Interactivity
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar iconos de Lucide
    lucide.createIcons();
    
    // Validación del RFC (formato mexicano)
    const rfcInput = document.getElementById('applicant_rfc');
    if (rfcInput) {
        rfcInput.addEventListener('input', function(e) {
            // Convertir a mayúsculas
            this.value = this.value.toUpperCase();
            
            // Validar formato básico RFC
            const rfcPattern = /^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/;
            if (this.value.length === 12 || this.value.length === 13) {
                if (rfcPattern.test(this.value)) {
                    this.style.borderColor = '#10b981';
                } else {
                    this.style.borderColor = '#ef4444';
                }
            } else {
                this.style.borderColor = '#e2e8f0';
            }
        });
    }
    
    // Validación del código postal
    const postalCodeInput = document.getElementById('postal_code');
    if (postalCodeInput) {
        postalCodeInput.addEventListener('input', function(e) {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Validación de teléfono
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Validación de lada
    const areaCodeInput = document.getElementById('area_code');
    if (areaCodeInput) {
        areaCodeInput.addEventListener('input', function(e) {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Confirmación antes de enviar el formulario
    const form = document.querySelector('form');
    if (form && !form.id.includes('delete')) {
        form.addEventListener('submit', function(e) {
            const rfcValue = rfcInput ? rfcInput.value : '';
            const businessName = document.getElementById('business_name').value;
            
            if (rfcValue && businessName) {
                const confirmMessage = `¿Confirmar registro del solicitante?\n\nRFC: ${rfcValue}\nRazón Social: ${businessName}`;
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                }
            }
        });
    }
    
    // Auto-mayúsculas para TODOS los campos de texto excepto email y números
    const textInputs = document.querySelectorAll('input[type="text"], textarea');
    textInputs.forEach(function(input) {
        // Excluir campos que no deben estar en mayúsculas
        const excludeIds = ['area_code', 'phone', 'postal_code', 'ws_file_upload_key'];
        
        if (!excludeIds.includes(input.id)) {
            input.addEventListener('input', function(e) {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(start, end);
            });
        }
    });
});
