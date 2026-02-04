document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
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

window.selectApplicant = function(applicantId, mode) {
    if (mode === 'manual') {
        window.location.href = `/mve/manual/${applicantId}`;
    } else {
        window.location.href = `/mve/archivo-m/${applicantId}`;
    }
};
