// 1. Configuraciones que deben ejecutarse apenas cargue el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar iconos globales
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Funcionalidad del dropdown del perfil (Avatar)
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
    }
});

// 2. FUNCIÓN GLOBAL (Fuera del DOMContentLoaded)
// Se vincula a 'window' para que el 'onclick' del HTML pueda ejecutarla
window.toggleAccordion = function(id) {
    const content = document.getElementById('content-' + id);
    const icon = document.getElementById('icon-' + id);
    
    if (!content || !icon) return;

    if (content.classList.contains('active')) {
        content.classList.remove('active');
        icon.style.transform = 'rotate(0deg)';
    } else {
        // Cerrar otros acordeones abiertos para mantener la vista limpia
        document.querySelectorAll('.accordion-content.active').forEach(function(el) {
            el.classList.remove('active');
        });
        document.querySelectorAll('[id^="icon-"]').forEach(function(el) {
            el.style.transform = 'rotate(0deg)';
        });
        
        // Abrir el seleccionado
        content.classList.add('active');
        icon.style.transform = 'rotate(180deg)';
    }
    
    // Refrescar iconos dentro del contenido que acaba de aparecer
    setTimeout(() => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }, 50);
};