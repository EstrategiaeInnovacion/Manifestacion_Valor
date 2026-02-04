// Applicants List Page Interactivity
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar iconos de Lucide
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
    
    // Auto-hide success alerts after 5 seconds
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s, transform 0.5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });

    const deleteButtons = document.querySelectorAll('[data-delete-applicant]');
    deleteButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.deleteApplicant;
            if (!id) {
                return;
            }

            if (confirm('¿Estás seguro de que deseas eliminar este solicitante? Esta acción no se puede deshacer.')) {
                const form = document.getElementById(`delete-form-${id}`);
                if (form) {
                    form.submit();
                }
            }
        });
    });
});
