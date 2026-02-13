document.addEventListener('DOMContentLoaded', function() {
    // Dropdown funcionality
    const avatarButton = document.getElementById('avatarButton');
    const dropdownMenu = document.getElementById('dropdownMenu');
    
    if (avatarButton && dropdownMenu) {
        let isOpen = false;
        
        avatarButton.addEventListener('click', function(e) {
            e.stopPropagation();
            isOpen = !isOpen;
            
            if (isOpen) {
                dropdownMenu.classList.add('active');
            } else {
                dropdownMenu.classList.remove('active');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!avatarButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('active');
                isOpen = false;
            }
        });
        
        // Close dropdown when pressing Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdownMenu.classList.remove('active');
                isOpen = false;
            }
        });
    }
    
    // File upload handling
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        const container = input.closest('.file-upload-area');
        if (!container) return;
        
        // Drag and drop
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            container.classList.add('dragover');
        });
        
        container.addEventListener('dragleave', function(e) {
            e.preventDefault();
            container.classList.remove('dragover');
        });
        
        container.addEventListener('drop', function(e) {
            e.preventDefault();
            container.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                updateFileLabel(input, files[0].name);
            }
        });
        
        // File selection
        input.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                updateFileLabel(input, e.target.files[0].name);
            }
        });
    });
    
    function updateFileLabel(input, filename) {
        const label = input.nextElementSibling;
        if (label && label.tagName === 'LABEL') {
            const originalText = label.dataset.original || label.textContent;
            label.dataset.original = originalText;
            label.textContent = `Archivo seleccionado: ${filename}`;
            label.classList.add('file-selected');
        }
    }
    
    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('[COVE DEBUG] Form submit event fired');
            
            const folioInput = document.getElementById('folio_edocument');
            const certificadoInput = document.querySelector('input[name="certificado"]');
            const llaveInput = document.querySelector('input[name="llave_privada"]');
            const passwordInput = document.querySelector('input[name="contrasena_llave"]');
            
            let hasErrors = false;
            
            // Validate folio
            if (!folioInput || !folioInput.value.trim()) {
                showFieldError(folioInput, 'El folio eDocument es requerido');
                hasErrors = true;
            }
            
            // Validate certificate file (only if the eFirma section is visible)
            const efirmaSection = document.getElementById('efirma-manual-section');
            const efirmaVisible = efirmaSection && !efirmaSection.classList.contains('hidden');
            console.log('[COVE DEBUG] efirma visible:', efirmaVisible);

            if (efirmaVisible) {
                if (!certificadoInput || certificadoInput.files.length === 0) {
                    showFieldError(certificadoInput, 'El archivo de certificado es requerido');
                    hasErrors = true;
                }
                
                // Validate private key file
                if (!llaveInput || llaveInput.files.length === 0) {
                    showFieldError(llaveInput, 'El archivo de llave privada es requerido');
                    hasErrors = true;
                }
                
                // Validate password  
                if (!passwordInput || !passwordInput.value.trim()) {
                    showFieldError(passwordInput, 'La contraseña de la llave privada es requerida. Esta es la contraseña que configuró cuando obtuvo su eFirma del SAT.');
                    hasErrors = true;
                }
            }

            // Validate webservice key (only if WS section is visible)
            const wsSection = document.getElementById('ws-manual-section');
            const wsVisible = wsSection && !wsSection.classList.contains('hidden');
            const wsInput = document.getElementById('clave_webservice');
            
            if (wsVisible && (!wsInput || !wsInput.value.trim())) {
                showFieldError(wsInput, 'La contraseña del Web Service VUCEM es requerida');
                hasErrors = true;
            }
            
            if (hasErrors) {
                console.log('[COVE DEBUG] Validation errors found, preventing submit');
                e.preventDefault();
                return false;
            }
            
            console.log('[COVE DEBUG] No errors, form will submit. Action:', form.action);
            // Show loading state
            showLoadingState();
        });
    }
    
    function showFieldError(field, message) {
        if (!field) return;
        
        // Remove existing error
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error class to field
        field.classList.add('border-red-500');
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        
        // Insert error message
        field.parentNode.appendChild(errorDiv);
        
        // Focus on first error field
        if (!document.querySelector('.field-error-focused')) {
            field.classList.add('field-error-focused');
            field.focus();
        }
        
        // Remove error on input
        field.addEventListener('input', function() {
            field.classList.remove('border-red-500');
            const error = field.parentNode.querySelector('.field-error');
            if (error) {
                error.remove();
            }
        }, { once: true });
    }
    
    function showLoadingState() {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner"></span> Consultando...';
            submitButton.classList.add('loading');
        }
        
        // Add loading class to form
        form.classList.add('loading');
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Progress indicator for file uploads
    function updateProgress(percentage) {
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
            bar.style.width = percentage + '%';
        });
    }
    
    // File size validation
    function validateFileSize(file, maxSizeMB = 10) {
        const maxBytes = maxSizeMB * 1024 * 1024;
        if (file.size > maxBytes) {
            return `El archivo no debe superar ${maxSizeMB}MB`;
        }
        return null;
    }
    
    // File type validation
    function validateFileType(file, allowedTypes = ['.cer', '.key', '.pem']) {
        const fileName = file.name.toLowerCase();
        const isValid = allowedTypes.some(type => fileName.endsWith(type.toLowerCase()));
        if (!isValid) {
            return `Tipo de archivo no válido. Solo se permiten: ${allowedTypes.join(', ')}`;
        }
        return null;
    }
    
    // Enhanced file validation
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            let errorMessage = null;
            
            // Validate size
            errorMessage = validateFileSize(file);
            if (errorMessage) {
                showFieldError(input, errorMessage);
                input.value = '';
                return;
            }
            
            // Validate type based on input name
            if (input.name === 'certificado') {
                errorMessage = validateFileType(file, ['.cer', '.crt', '.pem', '.der', '.p7b', '.p7c']);
            } else if (input.name === 'llave_privada') {
                errorMessage = validateFileType(file, ['.key', '.pem', '.p8', '.der']);
            }
            
            if (errorMessage) {
                showFieldError(input, errorMessage);
                input.value = '';
                return;
            }
            
            // Clear any existing errors
            input.classList.remove('border-red-500');
            const existingError = input.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Show file selected feedback
            showFileSelected(input, file);
        });
    });
    
    function showFileSelected(input, file) {
        // Remove existing feedback
        const existingFeedback = input.parentNode.querySelector('.file-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }
        
        // Add success feedback
        const feedback = document.createElement('div');
        feedback.className = 'file-feedback text-green-600 text-sm mt-1 flex items-center';
        feedback.innerHTML = `
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Archivo seleccionado: ${file.name} (${(file.size / 1024).toFixed(1)} KB)
        `;
        
        input.parentNode.appendChild(feedback);
    }
});