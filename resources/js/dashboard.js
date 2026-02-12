// Dropdown menu functionality
document.addEventListener('DOMContentLoaded', function() {
    // Asegurar que Lucide se cargue
    lucide.createIcons();
    
    const avatarButton = document.getElementById('avatarButton');
    const dropdownMenu = document.getElementById('dropdownMenu');
    
    if (avatarButton && dropdownMenu) {
        // Toggle dropdown on avatar click
        avatarButton.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!avatarButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('active');
            }
        });
        
        // Close dropdown on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdownMenu.classList.remove('active');
            }
        });
    }
});
// Funciones para el modal de MVE
window.openMveModal = function() {
    const modal = document.getElementById('mveModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Reinicializar los iconos de Lucide en el modal
        setTimeout(() => {
            lucide.createIcons();
        }, 50);
    }
};

window.closeMveModal = function() {
    const modal = document.getElementById('mveModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
};

window.selectMveManual = function() {
    window.location.href = '/mve/select-applicant?mode=manual';
};

window.selectMveArchivoM = function() {
    window.location.href = '/mve/select-applicant?mode=archivo_m';
};

window.selectMvePendientes = function() {
    window.location.href = '/mve/pendientes';
};

window.selectMveCompletadas = function() {
    window.location.href = '/mve/completadas';
};

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMveModal();
    }
});

// ========== SOPORTE MODAL ==========
window.openSupportModal = function() {
    const modal = document.getElementById('supportModal');
    if (modal) {
        // Reset form state
        document.getElementById('supportForm').classList.remove('hidden');
        document.getElementById('supportSuccess').classList.add('hidden');
        document.getElementById('supportForm').reset();
        document.getElementById('charCount').textContent = '0';
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => lucide.createIcons(), 50);
    }
};

window.closeSupportModal = function() {
    const modal = document.getElementById('supportModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
};

// Character counter for description
document.addEventListener('DOMContentLoaded', function() {
    const desc = document.getElementById('supportDescription');
    const counter = document.getElementById('charCount');
    if (desc && counter) {
        desc.addEventListener('input', () => {
            counter.textContent = desc.value.length;
        });
    }
});

// Submit support form via AJAX
window.submitSupportForm = async function(e) {
    e.preventDefault();
    
    const form = document.getElementById('supportForm');
    const btn = document.getElementById('supportSubmitBtn');
    const formData = new FormData(form);
    
    btn.classList.add('loading');
    btn.innerHTML = '<span class="spinner"></span> Enviando...';
    
    try {
        const response = await fetch('/support/send', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData,
        });
        
        const data = await response.json();
        
        if (data.success) {
            form.classList.add('hidden');
            document.getElementById('supportSuccess').classList.remove('hidden');
            setTimeout(() => lucide.createIcons(), 50);
        } else {
            alert(data.message || 'Error al enviar el ticket.');
            btn.classList.remove('loading');
            btn.innerHTML = '<i data-lucide="send" class="w-4 h-4 btn-icon"></i> Enviar Ticket';
            lucide.createIcons();
        }
    } catch (error) {
        alert('Error de conexión. Intenta nuevamente.');
        btn.classList.remove('loading');
        btn.innerHTML = '<i data-lucide="send" class="w-4 h-4 btn-icon"></i> Enviar Ticket';
        lucide.createIcons();
    }
};

// Close support modal with ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSupportModal();
    }
});
