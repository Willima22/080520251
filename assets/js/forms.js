/**
 * Form handling JavaScript for Instagram Post Scheduler
 */

document.addEventListener('DOMContentLoaded', function() {
    // Post type selection
    const postTypeOptions = document.querySelectorAll('.post-type-option');
    if (postTypeOptions.length) {
        postTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                postTypeOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tipo_postagem').value = this.dataset.value;
            });
        });
    }

    // Post format selection
    const formatOptions = document.querySelectorAll('.format-option');
    if (formatOptions.length) {
        formatOptions.forEach(option => {
            option.addEventListener('click', function() {
                formatOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('formato').value = this.dataset.value;
                
                // Show/hide file upload based on format
                toggleFileUploadInterface();
            });
        });
    }

    // Handle file uploads and previews
    const fileInputs = document.querySelectorAll('.file-upload');
    if (fileInputs.length) {
        fileInputs.forEach(input => {
            input.addEventListener('change', handleFileUpload);
        });
    }

    // Initialize dynamic form elements
    initFormElements();
});

/**
 * Initialize dynamic form elements
 */
function initFormElements() {
    // Toggle file upload interface based on selected format
    toggleFileUploadInterface();
    
    // Character counter for legend field
    const legendaField = document.getElementById('legenda');
    const characterCounter = document.getElementById('character-count');
    
    if (legendaField && characterCounter) {
        legendaField.addEventListener('input', function() {
            const remaining = 1000 - this.value.length;
            characterCounter.textContent = remaining;
            
            if (remaining < 0) {
                characterCounter.classList.add('text-danger');
                legendaField.classList.add('is-invalid');
            } else {
                characterCounter.classList.remove('text-danger');
                legendaField.classList.remove('is-invalid');
            }
        });
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', validateForm);
    }
}

/**
 * Toggle file upload interface based on selected format
 */
function toggleFileUploadInterface() {
    const formato = document.querySelector('input[name="formato"]:checked') || 
                  document.getElementById('formato');
    
    if (!formato) return;
    
    const singleUploadContainer = document.getElementById('single-upload-container');
    const carouselUploadContainer = document.getElementById('carousel-upload-container');
    
    if (singleUploadContainer && carouselUploadContainer) {
        if (formato.value === 'Carrossel') {
            singleUploadContainer.classList.add('d-none');
            carouselUploadContainer.classList.remove('d-none');
        } else {
            singleUploadContainer.classList.remove('d-none');
            carouselUploadContainer.classList.add('d-none');
        }
    }
}

/**
 * Handle file upload and preview
 */
function handleFileUpload(e) {
    const files = e.target.files;
    const previewContainer = document.getElementById(e.target.dataset.preview);
    const isCarousel = e.target.id === 'carouselFiles';
    const maxFiles = isCarousel ? 20 : 1;
    
    if (!previewContainer) return;
    
    // Clear preview if not carousel or if we're starting fresh
    if (!isCarousel || previewContainer.children.length === 0) {
        previewContainer.innerHTML = '';
    }
    
    // Check if we exceed maximum files for carousel
    if (isCarousel && (previewContainer.children.length + files.length > maxFiles)) {
        alert(`Você pode adicionar no máximo ${maxFiles} arquivos para um carrossel.`);
        return;
    }
    
    // Add preview for each file
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
        
        reader.onload = function(event) {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            
            if (file.type.startsWith('image/')) {
                previewItem.innerHTML = `
                    <img src="${event.target.result}" alt="Preview">
                    <button type="button" class="remove-btn" onclick="removePreview(this)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else if (file.type.startsWith('video/')) {
                previewItem.innerHTML = `
                    <div class="video-preview">
                        <i class="fas fa-video fa-2x"></i>
                        <span>${file.name}</span>
                    </div>
                    <button type="button" class="remove-btn" onclick="removePreview(this)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            }
            
            previewContainer.appendChild(previewItem);
        };
        
        reader.readAsDataURL(file);
    }
}

/**
 * Remove preview item
 */
function removePreview(button) {
    const previewItem = button.parentElement;
    const previewContainer = previewItem.parentElement;
    previewContainer.removeChild(previewItem);
}

/**
 * Validate form before submission
 */
function validateForm(e) {
    let isValid = true;
    const requiredFields = [
        'cliente_id',
        'tipo_postagem',
        'formato',
        'data_postagem',
        'hora_postagem'
    ];
    
    // Check required fields
    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (element && !element.value.trim()) {
            element.classList.add('is-invalid');
            isValid = false;
        } else if (element) {
            element.classList.remove('is-invalid');
        }
    });
    
    // Check file uploads
    const formato = document.getElementById('formato').value;
    let filesValid = true;
    
    if (formato === 'Imagem Única' || formato === 'Vídeo Único') {
        const fileInput = document.getElementById('singleFile');
        if (!fileInput.files.length) {
            document.getElementById('single-upload-container').classList.add('border', 'border-danger');
            filesValid = false;
        } else {
            document.getElementById('single-upload-container').classList.remove('border', 'border-danger');
        }
    } else if (formato === 'Carrossel') {
        const previewContainer = document.getElementById('carouselPreview');
        if (!previewContainer.children.length) {
            document.getElementById('carousel-upload-container').classList.add('border', 'border-danger');
            filesValid = false;
        } else {
            document.getElementById('carousel-upload-container').classList.remove('border', 'border-danger');
        }
    }
    
    isValid = isValid && filesValid;
    
    // Prevent form submission if validation fails
    if (!isValid) {
        e.preventDefault();
        alert('Por favor, preencha todos os campos obrigatórios e selecione os arquivos necessários.');
    }
    
    return isValid;
}
