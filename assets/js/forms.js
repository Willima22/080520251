/**
 * Scripts para manipulação de formulários
 * Versão 2.0 - Com verificação de elementos nulos
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando scripts de formulários...');
    
    // Variável para rastrear se os eventos já foram configurados
    if (window.formsScriptInitialized) {
        console.log('Scripts de formulários já inicializados');
        return;
    }
    
    window.formsScriptInitialized = true;
    
    try {
        // Configurar máscaras de entrada para campos específicos
        setupInputMasks();
        
        // Configurar validação de formulários
        setupFormValidation();
        
        // Configurar comportamentos adicionais de formulários
        setupFormBehaviors();
        
        console.log('Scripts de formulários inicializados com sucesso');
    } catch (error) {
        console.error('Erro ao inicializar scripts de formulários:', error);
    }
});

// Configurar máscaras de entrada
function setupInputMasks() {
    try {
        // Máscaras para telefones
        const phoneInputs = document.querySelectorAll('.phone-mask');
        if (phoneInputs && phoneInputs.length > 0) {
            phoneInputs.forEach(input => {
                if (!input) return;
                
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        // Formatar como (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
                        if (value.length <= 10) {
                            value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                        } else {
                            value = value.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                        }
                    }
                    e.target.value = value;
                });
            });
        }
        
        // Máscaras para CPF
        const cpfInputs = document.querySelectorAll('.cpf-mask');
        if (cpfInputs && cpfInputs.length > 0) {
            cpfInputs.forEach(input => {
                if (!input) return;
                
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        // Formatar como XXX.XXX.XXX-XX
                        value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
                    }
                    e.target.value = value;
                });
            });
        }
        
        // Máscaras para CNPJ
        const cnpjInputs = document.querySelectorAll('.cnpj-mask');
        if (cnpjInputs && cnpjInputs.length > 0) {
            cnpjInputs.forEach(input => {
                if (!input) return;
                
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        // Formatar como XX.XXX.XXX/XXXX-XX
                        value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
                    }
                    e.target.value = value;
                });
            });
        }
        
        // Máscaras para CEP
        const cepInputs = document.querySelectorAll('.cep-mask');
        if (cepInputs && cepInputs.length > 0) {
            cepInputs.forEach(input => {
                if (!input) return;
                
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        // Formatar como XXXXX-XXX
                        value = value.replace(/^(\d{5})(\d{0,3})/, '$1-$2');
                    }
                    e.target.value = value;
                });
            });
        }
    } catch (error) {
        console.error('Erro ao configurar máscaras de entrada:', error);
    }
}

// Configurar validação de formulários
function setupFormValidation() {
    try {
        // Verificar se o Bootstrap está disponível
        if (typeof bootstrap === 'undefined') {
            console.warn('Bootstrap não detectado. Algumas funcionalidades de validação podem não funcionar.');
        }
        
        // Obter todos os formulários que precisam de validação
        const forms = document.querySelectorAll('.needs-validation');
        
        if (forms && forms.length > 0) {
            forms.forEach(form => {
                // Verificar se o formulário existe antes de tentar acessar propriedades
                if (!form) return;
                
                // Verificar se o formulário tem um elemento de submissão
                const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                
                if (submitButton) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                }
            });
        }
    } catch (error) {
        console.error('Erro ao configurar validação de formulários:', error);
    }
}

// Configurar comportamentos adicionais de formulários
function setupFormBehaviors() {
    try {
        // Configurar tooltips para campos com informações adicionais
        const tooltipTriggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (tooltipTriggers && tooltipTriggers.length > 0 && typeof bootstrap !== 'undefined') {
            tooltipTriggers.forEach(trigger => {
                if (!trigger) return;
                
                try {
                    new bootstrap.Tooltip(trigger);
                } catch (e) {
                    console.warn('Erro ao inicializar tooltip:', e);
                }
            });
        }
        
        // Configurar botões de alternância
        const toggleButtons = document.querySelectorAll('.toggle-button-group .btn');
        if (toggleButtons && toggleButtons.length > 0) {
            toggleButtons.forEach(btn => {
                if (!btn) return;
                
                btn.addEventListener('click', function() {
                    // Verificar se o botão tem um grupo pai
                    const group = this.closest('.toggle-button-group');
                    if (!group) return;
                    
                    // Remover classe ativa de todos os botões no grupo
                    const buttons = group.querySelectorAll('.btn');
                    if (buttons && buttons.length > 0) {
                        buttons.forEach(button => {
                            if (button) button.classList.remove('active');
                        });
                    }
                    
                    // Adicionar classe ativa ao botão clicado
                    this.classList.add('active');
                    
                    // Atualizar valor de campo oculto, se existir
                    const targetId = group.getAttribute('data-target');
                    if (targetId) {
                        const hiddenInput = document.getElementById(targetId);
                        if (hiddenInput) {
                            hiddenInput.value = this.getAttribute('data-value') || '';
                        }
                    }
                });
            });
        }
        
        // Configurar campos dependentes
        const dependentFields = document.querySelectorAll('[data-depends-on]');
        if (dependentFields && dependentFields.length > 0) {
            dependentFields.forEach(field => {
                if (!field) return;
                
                const dependsOnId = field.getAttribute('data-depends-on');
                if (!dependsOnId) return;
                
                const dependsOnField = document.getElementById(dependsOnId);
                
                if (dependsOnField) {
                    dependsOnField.addEventListener('change', function() {
                        const requiredValue = field.getAttribute('data-required-value');
                        const container = field.closest('.dependent-field-container');
                        
                        if (this.value === requiredValue) {
                            if (container) container.classList.remove('d-none');
                            field.required = true;
                        } else {
                            if (container) container.classList.add('d-none');
                            field.required = false;
                            field.value = '';
                        }
                    });
                    
                    // Trigger inicial
                    dependsOnField.dispatchEvent(new Event('change'));
                }
            });
        }
    } catch (error) {
        console.error('Erro ao configurar comportamentos de formulários:', error);
    }
}