/**
 * Tableau de bord admin
 */

document.addEventListener('DOMContentLoaded', function() {
    // cache les alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // confirme les actions de suppression
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // active le menu lateral sur mobile
    const sidebarToggle = document.querySelector('.navbar-toggler');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }
    
    // gère les elements dynamiques du formulaire
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
            
            // ajoute la fonctionnalite du bouton de suppression
            const removeButton = div.querySelector('.remove-field');
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    div.remove();
                });
            }
        });
    });
    
    // initialise les datepickers si disponibles
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.datepicker', {
            dateFormat: 'd/m/Y',
            locale: 'fr'
        });
    }
    
    // initialise les dropdowns select2 si disponibles
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});