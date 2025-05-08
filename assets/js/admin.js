/**
 * Tableau de bord admin
 */

document.addEventListener('DOMContentLoaded', function() {
    setupDeleteConfirmation();
    
    setupSidebarToggle();
    
    setupDynamicFormFields();
    
    setupMenuToggle();
    setupMenuOptions();
    setupChatboxEnterSubmit();
});

function setupDeleteConfirmation() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action ne peut pas être annulée.')) {
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

function setupMenuToggle() {
    const menuButton = document.getElementById('chatbot-toggle-button');
    const menuContainer = document.getElementById('menu-container');

    if (menuButton && menuContainer) {
        menuButton.addEventListener('click', function() {
            if (menuContainer.style.display === 'none' || menuContainer.style.display === '') {
                menuContainer.style.display = 'flex';
            } else {
                menuContainer.style.display = 'none';
            }
        });
    }
}

function setupMenuOptions() {
    const menuOptions = document.querySelectorAll('.menu-option');

    menuOptions.forEach(function(option) {
        option.addEventListener('click', function() {
            const action = this.dataset.action;

            document.getElementById('menu-container').style.display = 'none';
        });
    });
}

function setupChatboxEnterSubmit() {
    const chatTextarea = document.querySelector('textarea[name="user_message"]');
    if (chatTextarea) {
        chatTextarea.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault(); 
                const form = chatTextarea.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    }
} 