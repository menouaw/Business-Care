/**
 * Tableau de bord admin
 */

document.addEventListener('DOMContentLoaded', function() {
    // cache les alertes apres 5 secondes
    autoHideAlerts();
    
    // confirme les actions de suppression
    setupDeleteConfirmation();
    
    // active le menu lateral sur mobile
    setupSidebarToggle();
    
    // gere les elements dynamiques du formulaire
    setupDynamicFormFields();
    
    // initialise les datepickers si disponibles
    initDatepickers();
    
    // initialise les dropdowns select2 si disponibles
    initSelect2();
});

function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

function setupDeleteConfirmation() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Etes-vous sûr de vouloir supprimer cet element ? Cette action ne peut pas etre annulee.')) {
                e.preventDefault();
            }
        });
    });
}

function setupSidebarToggle() {
    const sidebarToggle = document.querySelector('.navbar-toggler');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }
}

function setupDynamicFormFields() {
    const addFieldButtons = document.querySelectorAll('.add-field');
    addFieldButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const container = document.querySelector(button.dataset.container);
            const template = document.querySelector(button.dataset.template).innerHTML;
            const count = container.querySelectorAll('.dynamic-field').length;
            const newHtml = template.replace(/\{index\}/g, count);
            const div = document.createElement('div');
            div.className = 'dynamic-field mb-3';
            div.innerHTML = newHtml;
            container.appendChild(div);
            
            const removeButton = div.querySelector('.remove-field');
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    div.remove();
                });
            }
        });
    });
}

function initDatepickers() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.datepicker', {
            dateFormat: 'd/m/Y',
            locale: 'fr'
        });
    }
}

function initSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
} 