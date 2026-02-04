// Users List page interactivity
document.addEventListener('DOMContentLoaded', function() {
    // Asegurar que Lucide se cargue
    lucide.createIcons();
    
    // Dropdown functionality
    const avatarButton = document.getElementById('avatarButton');
    const dropdownMenu = document.getElementById('dropdownMenu');
    
    if (avatarButton && dropdownMenu) {
        avatarButton.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!avatarButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('active');
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdownMenu.classList.remove('active');
            }
        });
    }
});

// Accordion functionality
window.toggleAccordion = function(id) {
    console.log('Toggling accordion for ID:', id); // Debug log
    
    const content = document.getElementById('content-' + id);
    const icon = document.getElementById('icon-' + id);
    
    if (!content || !icon) {
        console.error('Content or icon not found for ID:', id);
        return;
    }
    
    if (content.classList.contains('active')) {
        content.classList.remove('active');
        icon.style.transform = 'rotate(0deg)';
    } else {
        // Cerrar todos los demás
        document.querySelectorAll('.accordion-content').forEach(function(el) {
            el.classList.remove('active');
        });
        document.querySelectorAll('[id^="icon-"]').forEach(function(el) {
            el.style.transform = 'rotate(0deg)';
        });
        
        // Abrir el seleccionado
        content.classList.add('active');
        icon.style.transform = 'rotate(180deg)';
    }
    
    // Recargar iconos de Lucide
    setTimeout(function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }, 100);
}

// Confirmación de eliminación de usuario
window.confirmDeleteUser = function(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        document.getElementById('delete-user-form-' + id).submit();
    }
}
