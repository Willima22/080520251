/**
 * Script avançado para visualização prévia de uploads
 * Inclui recursos de arrastar e soltar, reordenação e progresso de upload
 */

document.addEventListener('DOMContentLoaded', function() {
    // Configuração de visualização prévia para upload único
    setupSingleFilePreview();
    
    // Configuração de visualização prévia para carrossel
    setupCarouselPreview();
    
    // Configuração de drag-and-drop para reordenação
    setupSortable();
    
    // Configuração de validação de formulário
    setupFormValidation();
});

/**
 * Configura a visualização prévia para upload de arquivo único
 */
function setupSingleFilePreview() {
    const singleFileInput = document.getElementById('singleFile');
    const singlePreview = document.getElementById('singlePreview');
    
    if (!singleFileInput || !singlePreview) return;
    
    singleFileInput.addEventListener('change', function() {
        // Limpar visualização prévia anterior
        singlePreview.innerHTML = '';
        
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Criar container de visualização
            const previewContainer = document.createElement('div');
            previewContainer.className = 'single-preview-container position-relative';
            
            // Verificar se é imagem ou vídeo
            const isImage = file.type.startsWith('image/');
            
            if (isImage) {
                // Criar elemento de imagem
                const img = document.createElement('img');
                img.className = 'img-fluid rounded shadow-sm';
                img.style.maxHeight = '300px';
                
                // Usar FileReader para carregar a imagem
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                previewContainer.appendChild(img);
            } else {
                // Criar elemento de vídeo
                const video = document.createElement('video');
                video.className = 'img-fluid rounded shadow-sm';
                video.style.maxHeight = '300px';
                video.controls = true;
                
                // Usar FileReader para carregar o vídeo
                const reader = new FileReader();
                reader.onload = function(e) {
                    video.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                previewContainer.appendChild(video);
            }
            
            // Adicionar informações do arquivo
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info mt-3 text-center';
            fileInfo.innerHTML = `
                <h5 class="mb-1">${file.name}</h5>
                <p class="text-muted mb-0">${formatFileSize(file.size)}</p>
                <p class="text-muted">${isImage ? 'Imagem' : 'Vídeo'}</p>
            `;
            previewContainer.appendChild(fileInfo);
            
            // Adicionar botão para remover
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.style.width = '30px';
            removeBtn.style.height = '30px';
            removeBtn.style.padding = '0';
            removeBtn.style.display = 'flex';
            removeBtn.style.alignItems = 'center';
            removeBtn.style.justifyContent = 'center';
            
            removeBtn.addEventListener('click', function() {
                singleFileInput.value = '';
                singlePreview.innerHTML = '';
            });
            
            previewContainer.appendChild(removeBtn);
            
            // Adicionar ao container de visualização
            singlePreview.appendChild(previewContainer);
        }
    });
}

/**
 * Configura a visualização prévia para upload de carrossel
 */
function setupCarouselPreview() {
    const carouselFilesInput = document.getElementById('carouselFiles');
    const carouselPreview = document.getElementById('carouselPreview');
    const carouselCounter = document.getElementById('carousel-counter');
    
    if (!carouselFilesInput || !carouselPreview) return;
    
    // Armazenar arquivos selecionados
    let selectedFiles = [];
    
    carouselFilesInput.addEventListener('change', function(e) {
        // Obter novos arquivos
        const newFiles = Array.from(this.files);
        
        // Verificar limite de arquivos
        if (selectedFiles.length + newFiles.length > 20) {
            alert('Um carrossel pode ter no máximo 20 imagens. Por favor, remova algumas imagens antes de adicionar mais.');
            return;
        }
        
        // Adicionar novos arquivos à lista
        selectedFiles = [...selectedFiles, ...newFiles];
        
        // Atualizar visualização
        updateCarouselPreview();
    });
    
    /**
     * Atualiza a visualização prévia do carrossel
     */
    function updateCarouselPreview() {
        // Limpar visualização prévia
        carouselPreview.innerHTML = '';
        
        // Atualizar contador
        if (carouselCounter) {
            carouselCounter.textContent = `${selectedFiles.length}/20`;
            
            // Mudar cor do contador baseado na quantidade
            if (selectedFiles.length >= 20) {
                carouselCounter.className = 'badge bg-danger text-white float-end';
            } else if (selectedFiles.length > 0) {
                carouselCounter.className = 'badge bg-light text-dark float-end';
            } else {
                carouselCounter.className = 'badge bg-light text-dark float-end';
            }
        }
        
        // Criar visualização para cada arquivo
        selectedFiles.forEach((file, index) => {
            // Criar container de visualização
            const previewItem = document.createElement('div');
            previewItem.className = 'carousel-preview-item position-relative';
            previewItem.dataset.index = index;
            
            // Verificar se é imagem ou vídeo
            const isImage = file.type.startsWith('image/');
            
            // Criar container para mídia
            const mediaContainer = document.createElement('div');
            mediaContainer.className = 'media-container';
            mediaContainer.style.width = '180px';
            mediaContainer.style.height = '180px';
            mediaContainer.style.overflow = 'hidden';
            mediaContainer.style.borderRadius = '8px';
            mediaContainer.style.backgroundColor = '#f8f9fa';
            mediaContainer.style.display = 'flex';
            mediaContainer.style.alignItems = 'center';
            mediaContainer.style.justifyContent = 'center';
            mediaContainer.style.position = 'relative';
            
            if (isImage) {
                // Criar elemento de imagem
                const img = document.createElement('img');
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                
                // Usar FileReader para carregar a imagem
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                mediaContainer.appendChild(img);
            } else {
                // Criar elemento de vídeo
                const videoThumb = document.createElement('div');
                videoThumb.className = 'video-thumbnail';
                videoThumb.style.width = '100%';
                videoThumb.style.height = '100%';
                videoThumb.style.backgroundColor = '#000';
                videoThumb.style.display = 'flex';
                videoThumb.style.alignItems = 'center';
                videoThumb.style.justifyContent = 'center';
                
                // Ícone de vídeo
                const videoIcon = document.createElement('i');
                videoIcon.className = 'fas fa-play-circle fa-3x text-white';
                videoThumb.appendChild(videoIcon);
                
                mediaContainer.appendChild(videoThumb);
            }
            
            // Adicionar número de ordem
            const orderBadge = document.createElement('div');
            orderBadge.className = 'order-badge position-absolute top-0 start-0 m-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center';
            orderBadge.style.width = '24px';
            orderBadge.style.height = '24px';
            orderBadge.style.fontSize = '12px';
            orderBadge.textContent = index + 1;
            mediaContainer.appendChild(orderBadge);
            
            previewItem.appendChild(mediaContainer);
            
            // Adicionar informações do arquivo
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info mt-2 small text-center';
            fileInfo.innerHTML = `
                <div class="text-truncate" title="${file.name}" style="max-width: 180px;">${file.name}</div>
                <div class="text-muted">${formatFileSize(file.size)}</div>
            `;
            previewItem.appendChild(fileInfo);
            
            // Adicionar botão para remover
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.style.width = '24px';
            removeBtn.style.height = '24px';
            removeBtn.style.padding = '0';
            removeBtn.style.display = 'flex';
            removeBtn.style.alignItems = 'center';
            removeBtn.style.justifyContent = 'center';
            
            removeBtn.addEventListener('click', function() {
                // Remover arquivo da lista
                selectedFiles.splice(index, 1);
                
                // Atualizar visualização
                updateCarouselPreview();
            });
            
            previewItem.appendChild(removeBtn);
            
            // Adicionar ao container de visualização
            carouselPreview.appendChild(previewItem);
        });
        
        // Reinicializar Sortable após atualizar os itens
        if (typeof Sortable !== 'undefined') {
            initSortable();
        }
    }
    
    /**
     * Inicializa o Sortable para permitir reordenação
     */
    function initSortable() {
        if (carouselPreview && typeof Sortable !== 'undefined') {
            new Sortable(carouselPreview, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    // Reordenar arquivos na lista
                    const item = selectedFiles[evt.oldIndex];
                    selectedFiles.splice(evt.oldIndex, 1);
                    selectedFiles.splice(evt.newIndex, 0, item);
                    
                    // Atualizar visualização
                    updateCarouselPreview();
                }
            });
        }
    }
}

/**
 * Configura o Sortable para permitir reordenação por arrastar e soltar
 */
function setupSortable() {
    const carouselPreview = document.getElementById('carouselPreview');
    
    if (carouselPreview && typeof Sortable !== 'undefined') {
        new Sortable(carouselPreview, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                // Atualizar números de ordem
                const items = carouselPreview.querySelectorAll('.carousel-preview-item');
                items.forEach((item, index) => {
                    const orderBadge = item.querySelector('.order-badge');
                    if (orderBadge) {
                        orderBadge.textContent = index + 1;
                    }
                });
            }
        });
    }
}

/**
 * Configura validação de formulário
 */
function setupFormValidation() {
    const postForm = document.getElementById('postForm');
    
    if (postForm) {
        postForm.addEventListener('submit', function(e) {
            const formato = document.getElementById('formato').value;
            const singleFileInput = document.getElementById('singleFile');
            const carouselFilesInput = document.getElementById('carouselFiles');
            
            // Verificar se há arquivos selecionados
            if ((formato === 'Imagem Única' || formato === 'Vídeo Único') && 
                (!singleFileInput.files || singleFileInput.files.length === 0)) {
                e.preventDefault();
                alert('Por favor, selecione um arquivo para upload.');
                return false;
            } else if (formato === 'Carrossel' && 
                       (!carouselFilesInput.files || carouselFilesInput.files.length === 0)) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos um arquivo para o carrossel.');
                return false;
            }
            
            return true;
        });
    }
}

/**
 * Formata o tamanho do arquivo para exibição
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
