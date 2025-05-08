/**
 * Inicialização do calendário Flatpickr
 * Permite abrir o calendário ao clicar no ícone
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar o datepicker com Flatpickr
    const datepickerElements = document.querySelectorAll('.datepicker');
    
    datepickerElements.forEach(function(element) {
        const datepicker = flatpickr(element, {
            dateFormat: "d/m/Y",
            locale: "pt",
            allowInput: true,
            disableMobile: true,
            static: true,
            inline: false,
            appendTo: element.parentElement
        });
        
        // Associar o ícone de calendário ao input
        const inputId = element.id;
        const calendarTrigger = document.querySelector(`.calendar-trigger[data-input="${inputId}"]`);
        
        if (calendarTrigger) {
            calendarTrigger.addEventListener('click', function() {
                datepicker.open();
            });
        }
    });
    
    // Inicializar o timepicker com Flatpickr
    const timepickerElements = document.querySelectorAll('.timepicker');
    
    timepickerElements.forEach(function(element) {
        const timepicker = flatpickr(element, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 15,
            disableMobile: true
        });
        
        // Associar o ícone de relógio ao input
        const inputId = element.id;
        const clockTrigger = document.querySelector(`.clock-trigger[data-input="${inputId}"]`);
        
        if (clockTrigger) {
            clockTrigger.addEventListener('click', function() {
                timepicker.open();
            });
        }
    });
    
    // Configurar os presets de horário
    const timePresetLinks = document.querySelectorAll('.time-presets .dropdown-item');
    
    timePresetLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.dataset.value;
            const horaPostagem = document.getElementById('hora_postagem');
            
            if (horaPostagem) {
                horaPostagem.value = value;
                
                // Se estiver usando flatpickr, atualizar a instância
                if (horaPostagem._flatpickr) {
                    horaPostagem._flatpickr.setDate(value);
                }
            }
        });
    });
});
