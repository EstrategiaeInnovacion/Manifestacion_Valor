// MVE Pendientes page interactivity

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    const avatarButton = document.getElementById('avatarButton');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (avatarButton && dropdownMenu) {
        avatarButton.addEventListener('click', () => {
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', (event) => {
            if (!avatarButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
});
