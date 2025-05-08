/**
 * Script simplificado para upload de arquivos
 * Versão 3.0 - Solução direta para problemas de upload
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Upload Fix Script v3.0 - Solução direta carregada');
    
    // 1. Configuração dos botões de seleção de arquivo (abordagem direta)
    document.querySelectorAll('.select-file-btn').forEach(function(button) {
        console.log('Configurando botão de upload:', button);
        
        // Remover eventos existentes e adicionar novo
        button.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetInputId = this.getAttribute('data-target');
            console.log('Botão clicado para target:', targetInputId);
            
            if (targetInputId) {
                const fileInput = document.getElementById(targetInputId);
                if (fileInput) {
                    console.log('Acionando input de arquivo:', targetInputId);
                    fileInput.click();
                } else {
                    console.error('Input de arquivo não encontrado:', targetInputId);
                }
            }
            
            return false;
        };
    });
    
    // 2. Configuração dos inputs de arquivo
    document.querySelectorAll('.file-upload').forEach(function(input) {
        console.log('Configurando input de arquivo:', input.id);
    
    // Inicializar contadores
    let carouselFileCount = 0;
    const MAX_CAROUSEL_FILES = 20;
    
    // Configurar opções de formato
    formatOptions.forEach(option => {
        option.addEventListener('click', function() {
            const formatValue = this.dataset.value;
            document.getElementById('formato').value = formatValue;
            
            // Atualizar UI baseado no formato selecionado
            formatOptions.forEach(opt => {
                opt.classList.remove('active', 'btn-primary');
                opt.classList.add('btn-outline-secondary');
            });
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
    
    // Configurar botões de seleção de arquivo
    selectFileBtns.forEach(btn => {
        // Remover eventos antigos
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = this.getAttribute('data-target');
            console.log('Button clicked for target: ' + targetId);
            
            if (targetId) {
                const fileInput = document.getElementById(targetId);
                if (fileInput) {
                    console.log('Triggering click on file input: ' + targetId);
                    fileInput.click();
                }
            }
        });
    });
    
    // Configurar inputs de arquivo
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            handleFileSelect(e, this);
        });
    });
    
    // Configurar áreas de upload para drag & drop
    uploadAreas.forEach(area => {
        // Remover eventos antigos
        const newArea = area.cloneNode(true);
        area.parentNode.replaceChild(newArea, area);
        
        // Encontrar o botão dentro da área
        const button = newArea.querySelector('.select-file-btn');
        if (button) {
            const targetId = button.getAttribute('data-target');
            if (targetId) {
                const fileInput = document.getElementById(targetId);
                
                // Eventos de arrastar e soltar
                newArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.add('dragover');
                });
                
                newArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.remove('dragover');
                });
                
                newArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.remove('dragover');
                    
                    if (fileInput && e.dataTransfer.files.length > 0) {
                        // Verificar se o input aceita múltiplos arquivos
                        if (fileInput.multiple) {
                            fileInput.files = e.dataTransfer.files;
                        } else {
                            // Se não aceitar múltiplos, usar apenas o primeiro arquivo
                            const dt = new DataTransfer();
                            dt.items.add(e.dataTransfer.files[0]);
                            fileInput.files = dt.files;
                        }
                        
                        // Disparar evento de change para atualizar a UI
                        handleFileSelect({
                            target: fileInput,
                            preventDefault: function() {},
                            stopPropagation: function() {}
                        }, fileInput);
                    }
                });
                
                // Adicionar evento de clique na área
                newArea.addEventListener('click', function(e) {
                    // Verificar se o clique foi diretamente na área e não em um elemento filho
                    if (e.target === this || e.target.classList.contains('upload-area')) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (fileInput) {
                            console.log('Area clicked, triggering file input: ' + targetId);
                            fileInput.click();
                        }
                    }
                });
            }
        }
    });
    
    // Função para lidar com a seleção de arquivos
    function handleFileSelect(e, fileInput) {
        e.preventDefault();
        
        const files = fileInput.files;
        const previewId = fileInput.getAttribute('data-preview');
        const previewElement = document.getElementById(previewId);
        const isMultiple = fileInput.hasAttribute('multiple');
        
        if (!previewElement) return;
        
        // Limpar visualização prévia para upload único
        if (!isMultiple) {
            previewElement.innerHTML = '';
        }
        
        // Verificar se há arquivos selecionados
        if (files.length === 0) return;
        
        // Verificar limite de arquivos para carrossel
        if (isMultiple) {
            const currentCount = previewElement.querySelectorAll('.preview-item').length;
            const newCount = currentCount + files.length;
            
            if (newCount > MAX_CAROUSEL_FILES) {
                alert(`Você pode adicionar no máximo ${MAX_CAROUSEL_FILES} arquivos ao carrossel.`);
                return;
            }
            
            // Atualizar contador
            carouselFileCount = newCount;
            document.getElementById('carousel-counter').textContent = `${carouselFileCount}/${MAX_CAROUSEL_FILES}`;
        }
        
        // Processar cada arquivo
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Verificar se o arquivo já existe (para evitar duplicação)
            const existingPreviews = previewElement.querySelectorAll('.preview-item');
            let isDuplicate = false;
            
            for (let j = 0; j < existingPreviews.length; j++) {
                if (existingPreviews[j].dataset.filename === file.name) {
                    isDuplicate = true;
                    break;
                }
            }
            
            if (isDuplicate) {
                console.log('Arquivo duplicado ignorado:', file.name);
                continue;
            }
            
            // Criar visualização prévia
            createFilePreview(file, previewElement, isMultiple ? previewElement.children.length : null);
        }
    }
    
    // Função para criar visualização prévia de arquivo
    function createFilePreview(file, previewElement, index = null) {
        const reader = new FileReader();
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item position-relative';
        previewItem.dataset.filename = file.name;
        
        if (index !== null) {
            previewItem.dataset.index = index;
            previewItem.classList.add('carousel-item');
        }
        
        // Adicionar botão de remoção
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.style.width = '30px';
        removeBtn.style.height = '30px';
        removeBtn.style.padding = '0';
        removeBtn.style.zIndex = '10';
        removeBtn.addEventListener('click', function() {
            previewItem.remove();
            
            // Atualizar contador para carrossel
            if (previewElement.id === 'carouselPreview') {
                carouselFileCount--;
                document.getElementById('carousel-counter').textContent = `${carouselFileCount}/${MAX_CAROUSEL_FILES}`;
            }
        });
        previewItem.appendChild(removeBtn);
        
        reader.onload = function(e) {
            const isImage = file.type.startsWith('image/');
            
            // Obter dimensões desejadas do atributo data-dimensions
            const dimensionsAttr = document.getElementById(previewElement.id).previousElementSibling.querySelector('.file-upload').dataset.dimensions;
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
                
                // Garantir que a imagem seja carregada antes de adicioná-la ao DOM
                img.onload = function() {
                    console.log(`Imagem carregada: ${file.name} (${img.naturalWidth}x${img.naturalHeight})`);
                };
                
                mediaWrapper.appendChild(img);
            } else {
                // Criar visualização de vídeo
                const video = document.createElement('video');
                video.src = e.target.result;
                video.className = 'video-preview img-fluid';
                video.controls = true;
                video.alt = file.name;
                mediaWrapper.appendChild(video);
            }
            
            previewItem.appendChild(mediaWrapper);
            
            // Adicionar informações do arquivo
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info mt-2 text-muted';
            
            // Formatar tamanho do arquivo
            const fileSize = formatFileSize(file.size);
            
            fileInfo.innerHTML = `
                <div class="text-truncate" title="${file.name}">${file.name}</div>
                <small>${fileSize}</small>
            `;
            
            previewItem.appendChild(fileInfo);
        };
        
        reader.readAsDataURL(file);
        previewElement.appendChild(previewItem);
    }
    
    // Função para formatar o tamanho do arquivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Adicionar CSS para feedback visual durante o drag and drop
    const style = document.createElement('style');
    style.textContent = `
        .upload-area.dragover {
            background-color: #f0f8ff !important;
            border-color: #007bff !important;
        }
    `;
    document.head.appendChild(style);
});
