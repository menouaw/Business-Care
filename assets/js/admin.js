/**
 * Tableau de bord admin
 */

document.addEventListener('DOMContentLoaded', function() {
    setupDeleteConfirmation();
    
    setupDynamicFormFields();

    
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    const logoutButton = document.getElementById('logout-link'); 

    if (logoutButton) {
        logoutButton.addEventListener('click', async (event) => {
            event.preventDefault(); 
            console.log("Logout button clicked.");

            if (!window.firebaseAuth) {
                console.error("Les fonctions Firebase Auth ne sont pas disponibles pour la déconnexion.");
                alert("Erreur lors de la déconnexion. Réessayez."); 
                return;
            }

            try {
                await window.firebaseAuth.logOut();
                console.log("Déconnexion réussie. Redirection...");
                
                window.location.href = '/admin/login.php'; 
                
                
            } catch (error) {
                console.error("Déconnexion échouée:", error);
                alert("Erreur lors de la déconnexion: " + error.message); 
            }
        });
    } else {
        console.warn("Bouton de déconnexion avec le sélecteur '#logout-link' non trouvé."); 
        
    }
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