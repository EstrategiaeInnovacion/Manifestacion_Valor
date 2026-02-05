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