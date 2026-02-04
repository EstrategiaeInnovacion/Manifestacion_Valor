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
    
    // Drag and drop functionality
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('archivoM');
    
    if (uploadArea && fileInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.add('dragover');
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.remove('dragover');
            });
        });
        
        uploadArea.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect({ target: fileInput });
            }
        });
        
        uploadArea.addEventListener('click', function(e) {
            if (!e.target.closest('button')) {
                fileInput.click();
            }
        });
    }
});

window.handleFileSelect = function(event) {
    const file = event.target.files[0];
    
    if (!file) return;
    
    // Validar tamaño (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        alert('El archivo no debe superar los 2MB');
        clearFile();
        return;
    }
    
    // Mostrar información del archivo
    document.getElementById('uploadPrompt').classList.add('hidden');
    document.getElementById('fileInfo').classList.remove('hidden');
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
    document.getElementById('submitBtn').disabled = false;
    
    // Actualizar iconos
    setTimeout(() => {
        lucide.createIcons();
    }, 50);
};

window.clearFile = function() {
    document.getElementById('archivoM').value = '';
    document.getElementById('uploadPrompt').classList.remove('hidden');
    document.getElementById('fileInfo').classList.add('hidden');
    document.getElementById('submitBtn').disabled = true;
    
    // Actualizar iconos
    setTimeout(() => {
        lucide.createIcons();
    }, 50);
};

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
