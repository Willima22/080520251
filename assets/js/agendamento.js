/**
 * Script para funcionalidades da página de agendamento
 * Versão 2.0 - Compatível com o novo sistema de upload
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando scripts de agendamento...');
    
    // Variável para rastrear se os eventos já foram configurados
    if (window.agendamentoScriptInitialized) {
        console.log('Scripts de agendamento já inicializados');
        return;
    }
    
    window.agendamentoScriptInitialized = true;
    
    try {
        // Configuração dos botões de opção (tipo de postagem)
        setupOptionButtons('.post-type-option', 'tipo_postagem');
        
        // Configuração dos botões de opção (formato)
        setupOptionButtons('.format-option', 'formato');
        
        // Mostrar/ocultar containers de upload baseado no formato
        setupFormatVisibility();
        
        // Configuração de agendamento recorrente
        setupRecorrencia();
        
        // Configuração de presets de horário
        setupTimePresets();
        
        // Configuração do contador de caracteres da legenda
        setupCharacterCounter();
        
        // Validação do formulário
        setupFormValidation();
        
        console.log('Scripts de agendamento inicializados com sucesso');
    } catch (error) {
        console.error('Erro ao inicializar scripts de agendamento:', error);
    }
});

// Configurar botões de opção
function setupOptionButtons(selector, hiddenInputId) {
    try {
        const options = document.querySelectorAll(selector);
        const hiddenInput = document.getElementById(hiddenInputId);
        
        if (!options || !options.length || !hiddenInput) {
            console.warn(`Configuração de botões de opção ignorada: Seletor "${selector}" ou input "${hiddenInputId}" não encontrado`);
            return;
        }
        
        options.forEach(option => {
            if (!option) return;
            
            option.addEventListener('click', function() {
                // Remover classe ativa de todas as opções
                options.forEach(opt => {
                    if (opt) opt.classList.remove('active');
                });
                
                // Adicionar classe ativa à opção clicada
                this.classList.add('active');
                
                // Atualizar valor do campo oculto
                const optionValue = this.getAttribute('data-value');
                if (optionValue) {
                    hiddenInput.value = optionValue;
                    
                    // Se for mudança de formato, atualizar visibilidade dos containers
                    if (hiddenInputId === 'formato') {
                        updateFormatContainers(optionValue);
                    }
                }
            });
        });
        
        // Verificar se já existe uma opção selecionada
        if (hiddenInput.value) {
            options.forEach(option => {
                if (option && option.getAttribute('data-value') === hiddenInput.value) {
                    option.classList.add('active');
                }
            });
        }
    } catch (error) {
        console.error('Erro ao configurar botões de opção:', error);
    }
}

// Atualizar visibilidade dos containers de upload baseado no formato
function updateFormatContainers(format) {
    try {
        const singleContainer = document.getElementById('single-upload-container');
        const carouselContainer = document.getElementById('carousel-upload-container');
        
        if (!singleContainer || !carouselContainer) {
            console.warn('Containers de upload não encontrados');
            return;
        }
        
        if (format === 'Carrossel') {
            singleContainer.classList.add('d-none');
            carouselContainer.classList.remove('d-none');
        } else {
            singleContainer.classList.remove('d-none');
            carouselContainer.classList.add('d-none');
        }
    } catch (error) {
        console.error('Erro ao atualizar visibilidade dos containers:', error);
    }
}

// Configurar visibilidade inicial dos containers de formato
function setupFormatVisibility() {
    try {
        const formatInput = document.getElementById('formato');
        if (formatInput && formatInput.value) {
            updateFormatContainers(formatInput.value);
        }
    } catch (error) {
        console.error('Erro ao configurar visibilidade inicial dos containers:', error);
    }
}

// Configurar agendamento recorrente
function setupRecorrencia() {
    try {
        const recorrenciaCheck = document.getElementById('agendamento_recorrente');
        const recorrenciaOptions = document.getElementById('recorrencia_options');
        const frequenciaSelect = document.getElementById('frequencia');
        
        if (!recorrenciaCheck || !recorrenciaOptions) {
            console.warn('Elementos de recorrência não encontrados');
            return;
        }
        
        recorrenciaCheck.addEventListener('change', function() {
            if (this.checked) {
                recorrenciaOptions.classList.remove('d-none');
            } else {
                recorrenciaOptions.classList.add('d-none');
            }
        });
        
        if (frequenciaSelect) {
            frequenciaSelect.addEventListener('change', updateRecorrenciaFields);
            // Inicializar campos
            updateRecorrenciaFields();
        }
    } catch (error) {
        console.error('Erro ao configurar agendamento recorrente:', error);
    }
}

// Atualizar campos de recorrência baseado na frequência
function updateRecorrenciaFields() {
    try {
        const frequenciaSelect = document.getElementById('frequencia');
        const diaSemanaContainer = document.getElementById('dia_semana_container');
        const diaMesContainer = document.getElementById('dia_mes_container');
        
        if (!frequenciaSelect || !diaSemanaContainer || !diaMesContainer) {
            console.warn('Elementos de campos de recorrência não encontrados');
            return;
        }
        
        const frequencia = frequenciaSelect.value;
        
        if (frequencia === 'semanal') {
            diaSemanaContainer.classList.remove('d-none');
            diaMesContainer.classList.add('d-none');
        } else if (frequencia === 'mensal') {
            diaSemanaContainer.classList.add('d-none');
            diaMesContainer.classList.remove('d-none');
        } else {
            diaSemanaContainer.classList.add('d-none');
            diaMesContainer.classList.add('d-none');
        }
    } catch (error) {
        console.error('Erro ao atualizar campos de recorrência:', error);
    }
}

// Configurar presets de horário
function setupTimePresets() {
    try {
        const presets = document.querySelectorAll('.time-presets .dropdown-item');
        
        if (!presets || !presets.length) {
            console.warn('Presets de horário não encontrados');
            return;
        }
        
        presets.forEach(preset => {
            if (!preset) return;
            
            preset.addEventListener('click', function(e) {
                e.preventDefault();
                
                const value = this.getAttribute('data-value');
                const timepicker = document.getElementById('hora_postagem');
                
                if (timepicker && value) {
                    timepicker.value = value;
                }
            });
        });
    } catch (error) {
        console.error('Erro ao configurar presets de horário:', error);
    }
}

// Configurar contador de caracteres da legenda
function setupCharacterCounter() {
    try {
        const legendaTextarea = document.getElementById('legenda');
        const characterCount = document.getElementById('character-count');
        
        if (!legendaTextarea || !characterCount) {
            console.warn('Elementos de contador de caracteres não encontrados');
            return;
        }
        
        const maxLength = 1000;
        
        legendaTextarea.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            characterCount.textContent = remaining;
            
            if (remaining < 0) {
                characterCount.classList.add('text-danger');
            } else {
                characterCount.classList.remove('text-danger');
            }
        });
        
        // Executar evento de input inicialmente para definir o contador
        legendaTextarea.dispatchEvent(new Event('input'));
    } catch (error) {
        console.error('Erro ao configurar contador de caracteres:', error);
    }
}

// Configurar validação do formulário
function setupFormValidation() {
    try {
        const form = document.getElementById('postForm');
        
        if (!form) {
            console.warn('Formulário de postagem não encontrado');
            return;
        }
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar tipo de postagem
            const tipoPostagem = document.getElementById('tipo_postagem');
            if (tipoPostagem && !tipoPostagem.value) {
                isValid = false;
                tipoPostagem.classList.add('is-invalid');
                
                // Destacar os botões de tipo de postagem
                highlightInvalidOptionButtons('.post-type-option');
            } else if (tipoPostagem) {
                tipoPostagem.classList.remove('is-invalid');
            }
            
            // Validar formato
            const formato = document.getElementById('formato');
            if (formato && !formato.value) {
                isValid = false;
                formato.classList.add('is-invalid');
                
                // Destacar os botões de formato
                highlightInvalidOptionButtons('.format-option');
            } else if (formato) {
                formato.classList.remove('is-invalid');
            }
            
            // Validar arquivos baseado no formato selecionado
            if (formato && formato.value) {
                const formatoValue = formato.value;
                
                if (formatoValue === 'Imagem Única' || formatoValue === 'Vídeo Único') {
                    const singleFile = document.getElementById('singleFile');
                    const singleFileContainer = document.querySelector('#single-upload-container .invalid-feedback');
                    
                    if (singleFile && (!singleFile.files || !singleFile.files.length)) {
                        isValid = false;
                        if (singleFileContainer) {
                            singleFileContainer.style.display = 'block';
                        }
                        
                        // Destacar a área de upload
                        const uploadArea = document.querySelector('#single-upload-container .upload-area');
                        if (uploadArea) {
                            uploadArea.classList.add('border-danger');
                        }
                    } else if (singleFileContainer) {
                        singleFileContainer.style.display = 'none';
                        
                        // Remover destaque da área de upload
                        const uploadArea = document.querySelector('#single-upload-container .upload-area');
                        if (uploadArea) {
                            uploadArea.classList.remove('border-danger');
                        }
                    }
                } else if (formatoValue === 'Carrossel') {
                    const carouselFiles = document.getElementById('carouselFiles');
                    const carouselFileContainer = document.querySelector('#carousel-upload-container .invalid-feedback');
                    
                    if (carouselFiles && (!carouselFiles.files || !carouselFiles.files.length)) {
                        isValid = false;
                        if (carouselFileContainer) {
                            carouselFileContainer.style.display = 'block';
                        }
                        
                        // Destacar a área de upload
                        const uploadArea = document.querySelector('#carousel-upload-container .upload-area');
                        if (uploadArea) {
                            uploadArea.classList.add('border-danger');
                        }
                    } else if (carouselFileContainer) {
                        carouselFileContainer.style.display = 'none';
                        
                        // Remover destaque da área de upload
                        const uploadArea = document.querySelector('#carousel-upload-container .upload-area');
                        if (uploadArea) {
                            uploadArea.classList.remove('border-danger');
                        }
                    }
                }
            }
            
            // Validar data e hora
            const dataPostagem = document.getElementById('data_postagem');
            const horaPostagem = document.getElementById('hora_postagem');
            
            if (dataPostagem && !dataPostagem.value) {
                isValid = false;
                dataPostagem.classList.add('is-invalid');
            } else if (dataPostagem) {
                dataPostagem.classList.remove('is-invalid');
            }
            
            if (horaPostagem && !horaPostagem.value) {
                isValid = false;
                horaPostagem.classList.add('is-invalid');
            } else if (horaPostagem) {
                horaPostagem.classList.remove('is-invalid');
            }
            
            // Impedir o envio do formulário se não for válido
            if (!isValid) {
                e.preventDefault();
                
                // Mostrar mensagem de erro geral
                showValidationAlert('Por favor, preencha todos os campos obrigatórios.');
                
                // Rolar para o topo para mostrar a mensagem
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    } catch (error) {
        console.error('Erro ao configurar validação do formulário:', error);
    }
}

// Função auxiliar para destacar botões de opção inválidos
function highlightInvalidOptionButtons(selector) {
    try {
        const optionButtons = document.querySelectorAll(selector);
        if (optionButtons && optionButtons.length > 0) {
            // Adicionar classe de destaque a todos os botões
            optionButtons.forEach(button => {
                if (button) {
                    button.classList.add('btn-outline-danger');
                    button.classList.remove('btn-outline-primary');
                }
            });
            
            // Adicionar efeito de animação de shake
            const container = optionButtons[0].closest('.option-buttons');
            if (container) {
                container.classList.add('shake-animation');
                
                // Remover a classe após a animação
                setTimeout(() => {
                    container.classList.remove('shake-animation');
                }, 820); // 800ms para a animação + 20ms de margem
            }
            
            // Remover o destaque após um tempo
            setTimeout(() => {
                optionButtons.forEach(button => {
                    if (button) {
                        button.classList.remove('btn-outline-danger');
                        button.classList.add('btn-outline-primary');
                    }
                });
            }, 3000);
        }
    } catch (error) {
        console.error('Erro ao destacar botões de opção inválidos:', error);
    }
}

// Função auxiliar para mostrar alerta de validação
function showValidationAlert(message) {
    try {
        // Verificar se já existe um alerta
        let alertElement = document.querySelector('.alert-validacao');
        
        if (!alertElement) {
            // Criar novo alerta
            alertElement = document.createElement('div');
            alertElement.className = 'alert alert-danger alert-validacao';
            alertElement.role = 'alert';
            
            // Adicionar botão de fechar
            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn-close';
            closeButton.setAttribute('data-bs-dismiss', 'alert');
            closeButton.setAttribute('aria-label', 'Fechar');
            
            // Adicionar o texto da mensagem
            const messageText = document.createTextNode(message);
            
            // Montar o alerta
            alertElement.appendChild(messageText);
            alertElement.appendChild(closeButton);
            
            // Inserir no início do formulário
            const form = document.getElementById('postForm');
            if (form) {
                form.parentNode.insertBefore(alertElement, form);
            }
        } else {
            // Atualizar mensagem do alerta existente
            alertElement.textContent = message;
        }
    } catch (error) {
        console.error('Erro ao mostrar alerta de validação:', error);
    }
}