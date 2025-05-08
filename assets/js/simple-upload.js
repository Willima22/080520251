/**
 * Script simplificado para upload de arquivos
 * Versão 3.0 - Solução robusta anti-duplicação
 */

// IIFE para isolar o escopo e evitar conflitos
(function() {
    // Flag global para rastrear inicialização
    let initialized = false;
    let initAttempts = 0;
    const MAX_INIT_ATTEMPTS = 3;
    
    // Função principal de inicialização 
    function initUploadSystem() {
        console.log('Verificando inicialização do sistema de upload...');
        
        // Verificar se já foi inicializado
        if (initialized) {
            console.log('Sistema de upload já inicializado - ignorando nova chamada.');
            return;
        }
        
        initAttempts++;
        console.log('Tentativa de inicialização #' + initAttempts);
        
        // Evitar múltiplas tentativas
        if (initAttempts > MAX_INIT_ATTEMPTS) {
            console.error('Limite de tentativas de inicialização excedido.');
            return;
        }
        
        console.log('Upload Script v3.0 - Inicializando...');
        
        try {
            // Limpar qualquer estado anterior
            cleanupPreviousState();
            
            // Configurar os componentes do sistema
            setupFileButtons();
            setupFileInputs();
            setupDragAndDrop();
            
            // Marcar como inicializado
            initialized = true;
            console.log('Sistema de upload inicializado com sucesso.');
        } catch (error) {
            console.error('Erro ao inicializar sistema de upload:', error);
        }
    }
    
    // Limpeza completa do estado anterior
    function cleanupPreviousState() {
        console.log('Limpando estado anterior...');
        
        try {
            // Limpar todos os previews
            document.querySelectorAll('#singlePreview, #carouselPreview').forEach(el => {
                if (el) el.innerHTML = '';
            });
            
            // Remover e recriar botões de seleção de arquivo
            document.querySelectorAll('.select-file-btn').forEach(btn => {
                if (!btn || !btn.parentNode) return;
                
                // Clone o botão para remover listeners antigos
                const targetId = btn.getAttribute('data-target');
                const clonedBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(clonedBtn, btn);
                
                // Adicionar novo listener
                clonedBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const fileInput = document.getElementById(targetId);
                    if (fileInput) {
                        fileInput.click();
                    }
                });
            });
            
            // Remover e recriar inputs de arquivo
            document.querySelectorAll('.file-upload').forEach(input => {
                if (!input || !input.parentNode) return;
                
                const previewId = input.getAttribute('data-preview');
                const newInput = input.cloneNode(true);
                newInput.value = '';
                input.parentNode.replaceChild(newInput, input);
                
                // Adicionar listener para o novo input
                newInput.addEventListener('change', function() {
                    handleFileSelection(this, previewId);
                });
            });
            
            // Remover classes de estado das áreas de upload
            document.querySelectorAll('.upload-area').forEach(area => {
                if (area) area.classList.remove('dragover');
            });
        } catch (error) {
            console.error('Erro ao limpar estado anterior:', error);
        }
    }
    
    // Manipulador de seleção de arquivos (centralizado)
    function handleFileSelection(input, previewId) {
        if (!input || !input.files || input.files.length === 0) {
            console.log('Nenhum arquivo selecionado');
            return;
        }
        
        console.log(`${input.files.length} arquivo(s) selecionado(s) para ${previewId}`);
        
        // Obter o container de preview
        const previewContainer = document.getElementById(previewId);
        if (!previewContainer) {
            console.error('Container de preview não encontrado:', previewId);
            return;
        }
        
        // Limpar preview anterior (sempre limpar para evitar duplicação)
        previewContainer.innerHTML = '';
        
        // Processar cada arquivo
        Array.from(input.files).forEach(file => {
            createFilePreview(file, previewContainer);
        });
        
        // Atualizar contador de arquivos para carrossel
        if (input.id === 'carouselFiles') {
            const counter = document.getElementById('carousel-counter');
            if (counter) {
                counter.textContent = `${input.files.length}/20`;
            }
        }
    }
    
    // Configurar botões de seleção de arquivo
    function setupFileButtons() {
        // Esta função é redundante pois já configuramos os event listeners na limpeza
        console.log('Botões de seleção já configurados durante a limpeza.');
    }
    
    // Configurar inputs de arquivo
    function setupFileInputs() {
        // Esta função é redundante pois já configuramos os event listeners na limpeza
        console.log('Inputs de arquivo já configurados durante a limpeza.');
    }
    
    // Configurar áreas de drag & drop
    function setupDragAndDrop() {
        document.querySelectorAll('.upload-area').forEach(area => {
            // Ignorar áreas inexistentes
            if (!area || !area.parentNode) return;
            
            // Remover event listeners anteriores clonando o elemento
            const newArea = area.cloneNode(true);
            area.parentNode.replaceChild(newArea, area);
            
            // Identificar o input alvo
            const button = newArea.querySelector('.select-file-btn');
            if (!button) return;
            
            const targetId = button.getAttribute('data-target');
            const fileInput = document.getElementById(targetId);
            if (!fileInput) return;
            
            // Reconfigurar o botão interno para novos event listeners
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (fileInput) {
                    fileInput.click();
                }
            });
            
            console.log('Configurando drag & drop para:', targetId);
            
            // Eventos de arrastar e soltar
            newArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            newArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            newArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                if (e.dataTransfer.files.length > 0) {
                    try {
                        // Usar apenas o primeiro arquivo para upload único
                        if (!fileInput.multiple) {
                            const dt = new DataTransfer();
                            dt.items.add(e.dataTransfer.files[0]);
                            fileInput.files = dt.files;
                        } else {
                            fileInput.files = e.dataTransfer.files;
                        }
                        
                        // Disparar evento de mudança
                        const changeEvent = new Event('change');
                        fileInput.dispatchEvent(changeEvent);
                    } catch (err) {
                        console.error('Erro ao processar arquivos:', err);
                    }
                }
            });
        });
    }
    
    // Função para criar a pré-visualização do arquivo
    function createFilePreview(file, container) {
        if (!file || !container) {
            console.error('Arquivo ou container inválido');
            return;
        }
        
        console.log('Criando preview para:', file.name);
        
        // Criar card para o arquivo
        const card = document.createElement('div');
        card.className = 'card mb-3';
        card.style.maxWidth = '1080px';
        card.style.margin = '0 auto';
        
        // Criar container para a imagem com tamanho fixo
        const imageContainer = document.createElement('div');
        imageContainer.className = 'image-container';
        
        // Botão de remoção
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-danger position-absolute top-0 end-0 m-2 rounded-circle';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.style.width = '30px';
        removeBtn.style.height = '30px';
        removeBtn.style.padding = '0';
        removeBtn.style.zIndex = '10';
        removeBtn.addEventListener('click', function() {
            card.remove();
            
            // Atualizar contador de arquivos para carrossel, se aplicável
            const carouselCounter = document.getElementById('carousel-counter');
            if (carouselCounter && container.id === 'carouselPreview') {
                const currentCount = container.querySelectorAll('.card').length;
                carouselCounter.textContent = `${currentCount}/20`;
            }
        });
        
        // Adicionar botão ao container
        imageContainer.appendChild(removeBtn);
        
        // Processar o arquivo
        const reader = new FileReader();
        reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
                // Criar elemento de imagem
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = file.name;
                img.className = 'card-img-top';
                imageContainer.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                // Criar elemento de vídeo
                const videoContainer = document.createElement('div');
                videoContainer.className = 'd-flex justify-content-center align-items-center h-100';
                
                const videoIcon = document.createElement('div');
                videoIcon.className = 'text-center';
                videoIcon.innerHTML = `
                    <i class="fas fa-video fa-3x text-secondary mb-2"></i>
                    <p class="mb-0">Vídeo</p>
                `;
                
                videoContainer.appendChild(videoIcon);
                imageContainer.appendChild(videoContainer);
            } else {
                // Criar ícone para outros tipos de arquivo
                const fileIcon = document.createElement('div');
                fileIcon.className = 'd-flex justify-content-center align-items-center h-100';
                fileIcon.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-file fa-3x text-secondary mb-2"></i>
                        <p class="mb-0">Arquivo</p>
                    </div>
                `;
                imageContainer.appendChild(fileIcon);
            }
            
            // Adicionar container de imagem ao card
            card.appendChild(imageContainer);
            
            // Adicionar informações do arquivo
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-2';
            cardBody.innerHTML = `
                <p class="card-text mb-0">
                    <small class="text-muted">${file.name}</small>
                </p>
                <p class="card-text">
                    <small class="text-muted">${formatFileSize(file.size)}</small>
                </p>
            `;
            
            card.appendChild(cardBody);
            
            // Adicionar card ao container
            container.appendChild(card);
        };
        
        reader.readAsDataURL(file);
    }
    
    // Função para formatar o tamanho do arquivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Inicializar somente quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Pequeno atraso para garantir que tudo esteja carregado
            setTimeout(initUploadSystem, 100);
        });
    } else {
        // DOM já está pronto
        setTimeout(initUploadSystem, 100);
    }
    
    // Exportar função para uso global
    window.initUploadSystem = initUploadSystem;
})();