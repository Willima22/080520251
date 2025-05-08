/**
 * Script para gerenciamento de uploads de mídia
 * Inclui visualização prévia, contador de arquivos e reordenação de carrossel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const formatOptions = document.querySelectorAll('.format-option');
    const singleUploadContainer = document.getElementById('single-upload-container');
    const carouselUploadContainer = document.getElementById('carousel-upload-container');
    const singleFileInput = document.getElementById('singleFile');
    const carouselFilesInput = document.getElementById('carouselFiles');
    const singlePreview = document.getElementById('singlePreview');
    const carouselPreview = document.getElementById('carouselPreview');
    const carouselCounter = document.getElementById('carousel-counter');
    
    // Inicializar contadores
    let carouselFileCount = 0;
    const MAX_CAROUSEL_FILES = 20;
    
    // Configurar opções de formato
    formatOptions.forEach(option => {
        option.addEventListener('click', function() {
            const formatValue = this.dataset.value;
            document.getElementById('formato').value = formatValue;
            
            // Atualizar UI baseado no formato selecionado
            formatOptions.forEach(opt => opt.classList.remove('active', 'btn-primary'));
            this.classList.add('active', 'btn-primary');
            this.classList.remove('btn-outline-secondary');
            
            // Mostrar o container de upload apropriado
            if (formatValue === 'Carrossel') {
                singleUploadContainer.classList.add('d-none');
                carouselUploadContainer.classList.remove('d-none');
            } else {
                singleUploadContainer.classList.remove('d-none');
                carouselUploadContainer.classList.add('d-none');
            }
        });
    });
    
    // Função para criar visualização prévia de imagem
    function createImagePreview(file, previewElement, index = null) {
        // Verificar se já existe um preview com o mesmo nome de arquivo para evitar duplicação
        const existingPreviews = previewElement.querySelectorAll('.preview-item');
        for (let i = 0; i < existingPreviews.length; i++) {
            if (existingPreviews[i].dataset.filename === file.name) {
                console.log('Preview já existe para o arquivo:', file.name);
                return null; // Evitar duplicação
            }
        }
        
        const reader = new FileReader();
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item position-relative';
        previewItem.dataset.filename = file.name; // Armazenar o nome do arquivo para verificar duplicação
        
        if (index !== null) {
            previewItem.dataset.index = index;
            previewItem.classList.add('carousel-item');
        }
        
        reader.onload = function(e) {
            const isImage = file.type.startsWith('image/');
            
            // Obter dimensões desejadas do atributo data-dimensions
            const dimensionsAttr = file.inputElement ? file.inputElement.dataset.dimensions : null;
            const dimensions = dimensionsAttr ? dimensionsAttr.split('x') : [1080, 1350];
            
            // Criar um wrapper para a imagem/vídeo com fundo branco
            const mediaWrapper = document.createElement('div');
            mediaWrapper.className = 'image-preview-wrapper';
            
            if (isImage) {
                // Criar visualização de imagem
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-preview img-fluid';
                img.alt = file.name;
                mediaWrapper.appendChild(img);
                previewItem.appendChild(mediaWrapper);
            } else {
                // Criar visualização de vídeo
                const video = document.createElement('video');
                video.src = e.target.result;
                video.className = 'video-preview img-fluid';
                video.controls = true;
                video.alt = file.name;
                mediaWrapper.appendChild(video);
                previewItem.appendChild(mediaWrapper);
            }
            
            // Adicionar informações do arquivo
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info mt-2 small';
            fileInfo.innerHTML = `
                <div class="text-truncate" title="${file.name}">${file.name}</div>
                <div class="text-muted">${formatFileSize(file.size)}</div>
            `;
            previewItem.appendChild(fileInfo);
            
            // Adicionar botão de remover
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.style.width = '24px';
            removeBtn.style.height = '24px';
            removeBtn.style.padding = '0';
            
            removeBtn.addEventListener('click', function() {
                previewItem.remove();
                
                if (index !== null) {
                    // Atualizar contador de carrossel
                    carouselFileCount--;
                    updateCarouselCounter();
                    
                    // Remover arquivo do input (criando um novo FileList é complexo, então reordenamos na submissão)
                    if (carouselFileCount === 0) {
                        carouselFilesInput.value = '';
                    }
                } else {
                    // Limpar input de arquivo único
                    singleFileInput.value = '';
                }
            });
            
            previewItem.appendChild(removeBtn);
            
            // Adicionar número de ordem para carrossel
            if (index !== null) {
                const orderBadge = document.createElement('div');
                orderBadge.className = 'position-absolute top-0 start-0 m-1 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center order-badge';
                orderBadge.style.width = '24px';
                orderBadge.style.height = '24px';
                orderBadge.style.fontSize = '12px';
                orderBadge.textContent = index + 1;
                previewItem.appendChild(orderBadge);
            }
        };
        
        reader.readAsDataURL(file);
        return previewItem;
    }
    
    // Função para formatar tamanho de arquivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Função para atualizar contador de carrossel
    function updateCarouselCounter() {
        if (carouselCounter) {
            carouselCounter.textContent = `${carouselFileCount}/${MAX_CAROUSEL_FILES}`;
            
            // Atualizar cor baseado na quantidade
            if (carouselFileCount >= MAX_CAROUSEL_FILES) {
                carouselCounter.classList.remove('bg-light', 'text-dark');
                carouselCounter.classList.add('bg-danger', 'text-white');
            } else if (carouselFileCount > 0) {
                carouselCounter.classList.remove('bg-danger', 'text-white');
                carouselCounter.classList.add('bg-light', 'text-dark');
            }
        }
    }
    
    // Função para atualizar números de ordem no carrossel
    function updateCarouselOrder() {
        const items = carouselPreview.querySelectorAll('.carousel-item');
        items.forEach((item, index) => {
            const orderBadge = item.querySelector('.order-badge');
            if (orderBadge) {
                orderBadge.textContent = index + 1;
            }
            item.dataset.index = index;
        });
    }
    
    // Configurar upload de arquivo único
    if (singleFileInput) {
        singleFileInput.addEventListener('change', function(e) {
            const file = this.files[0];
            
            if (file) {
                // Adicionar referência ao input element para acessar data-dimensions
                file.inputElement = this;
                
                // Limpar preview anterior
                singlePreview.innerHTML = '';
                
                // Criar preview
                const previewItem = createImagePreview(file, singlePreview);
                singlePreview.appendChild(previewItem);
                
                // Adicionar classe para indicar que há um arquivo
                singleUploadContainer.classList.add('has-file');
            }
        });
    }
    
    // Configurar upload de carrossel
    if (carouselFilesInput) {
        carouselFilesInput.addEventListener('change', function(e) {
            const files = Array.from(this.files);
            
            if (files.length > 0) {
                // Verificar limite de arquivos
                if (carouselFileCount + files.length > MAX_CAROUSEL_FILES) {
                    alert(`Você pode adicionar no máximo ${MAX_CAROUSEL_FILES} arquivos ao carrossel.`);
                    return;
                }
                
                // Processar cada arquivo
                files.forEach((file, index) => {
                    // Adicionar referência ao input element para acessar data-dimensions
                    file.inputElement = this;
                    
                    const previewItem = createImagePreview(file, carouselPreview, carouselFileCount + index);
                    carouselPreview.appendChild(previewItem);
                });
                
                // Atualizar contador
                carouselFileCount += files.length;
                updateCarouselCounter();
                
                // Atualizar números de ordem
                updateCarouselOrder();
                
                // Adicionar classe para indicar que há arquivos
                carouselUploadContainer.classList.add('has-files');
            }
        });
    }
    
    // Configurar drag and drop para reordenação do carrossel
    if (typeof Sortable !== 'undefined' && carouselPreview) {
        new Sortable(carouselPreview, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                updateCarouselOrder();
            }
        });
    }
    
    // Configurar drag and drop para upload
    const dropAreas = document.querySelectorAll('.upload-area');
    dropAreas.forEach(area => {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            area.addEventListener(eventName, () => {
                area.classList.add('highlight');
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, () => {
                area.classList.remove('highlight');
            });
        });
        
        area.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            // Determinar qual input deve receber os arquivos
            const inputId = area.querySelector('input[type=file]').id;
            
            if (inputId === 'singleFile' && files.length > 0) {
                // Para upload único, usar apenas o primeiro arquivo
                singleFileInput.files = files;
                
                // Disparar evento change manualmente
                const event = new Event('change');
                singleFileInput.dispatchEvent(event);
            } else if (inputId === 'carouselFiles') {
                // Para carrossel, usar todos os arquivos
                carouselFilesInput.files = files;
                
                // Disparar evento change manualmente
                const event = new Event('change');
                carouselFilesInput.dispatchEvent(event);
            }
        });
    });
    
    // Estilizar áreas de upload e configurar cliques nos botões
    document.querySelectorAll('.upload-area').forEach(area => {
        area.addEventListener('mouseenter', function() {
            this.classList.add('hover');
        });
        
        area.addEventListener('mouseleave', function() {
            this.classList.remove('hover');
        });
        
        // Configurar clique direto no botão de seleção de arquivo
        const selectButton = area.querySelector('.btn.btn-outline-primary');
        const fileInput = area.querySelector('input[type=file]');
        
        if (selectButton && fileInput) {
            selectButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.click();
            });
        }
    });
    
    // Validação de formulário antes de enviar
    const postForm = document.getElementById('postForm');
    if (postForm) {
        postForm.addEventListener('submit', function(e) {
            const formato = document.getElementById('formato').value;
            
            // Verificar se há arquivos selecionados
            if (formato === 'Imagem Única' || formato === 'Vídeo Único') {
                if (!singleFileInput.files || singleFileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Por favor, selecione um arquivo para upload.');
                    return false;
                }
            } else if (formato === 'Carrossel') {
                if (carouselFileCount === 0) {
                    e.preventDefault();
                    alert('Por favor, selecione pelo menos um arquivo para o carrossel.');
                    return false;
                }
            }
            
            return true;
        });
    }
});

// Adicionar CSS para os elementos de upload
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .upload-area {
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .upload-area.highlight {
            background-color: #f8f9fa;
            border-color: #0d6efd !important;
        }
        
        .upload-area.hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .custom-file-upload {
            cursor: pointer;
            display: inline-block;
            padding: 20px;
            text-align: center;
            width: 100%;
        }
        
        .preview-item {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            width: 200px;
        }
        
        .preview-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .carousel-item {
            cursor: grab;
        }
        
        .carousel-item:active {
            cursor: grabbing;
        }
        
        .sortable-ghost {
            opacity: 0.5;
        }
    `;
    document.head.appendChild(style);
});
