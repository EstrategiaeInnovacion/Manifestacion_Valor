// License Panel - JavaScript

function openLicenseModal(adminId, adminName) {
    document.getElementById('licenseAdminId').value = adminId;
    document.getElementById('licenseModalSubtitle').textContent = adminName;
    const modal = document.getElementById('licenseModal');
    modal.classList.remove('hidden');
    modal.classList.add('active');
    setTimeout(() => lucide.createIcons(), 50);
}

function closeLicenseModal() {
    const modal = document.getElementById('licenseModal');
    modal.classList.add('hidden');
    modal.classList.remove('active');
}

function openLimitsModal(userId, userName, maxUsers, maxApplicants) {
    document.getElementById('limitsModalSubtitle').textContent = userName;
    document.getElementById('limitsMaxApplicants').value = maxApplicants ?? 10;
    
    // Si maxUsers es null, es un Usuario (no Admin), ocultar campo de max_users
    const maxUsersGroup = document.getElementById('limitsMaxUsersGroup');
    const maxUsersInput = document.getElementById('limitsMaxUsers');
    if (maxUsers !== null && maxUsers !== undefined) {
        maxUsersGroup.style.display = 'block';
        maxUsersInput.value = maxUsers;
    } else {
        maxUsersGroup.style.display = 'none';
        maxUsersInput.value = '';
    }
    
    // Actualizar action del form
    document.getElementById('limitsForm').action = '/admin/licenses/limits/' + userId;
    
    const modal = document.getElementById('limitsModal');
    modal.classList.remove('hidden');
    modal.classList.add('active');
    setTimeout(() => lucide.createIcons(), 50);
}

function closeLimitsModal() {
    const modal = document.getElementById('limitsModal');
    modal.classList.add('hidden');
    modal.classList.remove('active');
}

function toggleAdminUsers(adminId) {
    const content = document.getElementById('admin-users-' + adminId);
    const chevron = document.getElementById('chevron-admin-' + adminId);
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        chevron.style.transform = 'rotate(180deg)';
    } else {
        content.classList.add('hidden');
        chevron.style.transform = 'rotate(0deg)';
    }
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLicenseModal();
        closeLimitsModal();
    }
});

// Close modals on backdrop click
document.getElementById('licenseModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeLicenseModal();
});
document.getElementById('limitsModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeLimitsModal();
});

// Expose functions globally for inline onclick handlers
window.openLicenseModal = openLicenseModal;
window.closeLicenseModal = closeLicenseModal;
window.openLimitsModal = openLimitsModal;
window.closeLimitsModal = closeLimitsModal;
window.toggleAdminUsers = toggleAdminUsers;
