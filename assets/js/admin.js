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
    
    // initialise les icones feather
    initFeatherIcons();
    
    // initialise les tooltips
    initTooltips();
});

/**
 * Masque automatiquement les alertes non permanentes après un délai de 5 secondes.
 *
 * Cette fonction parcourt tous les éléments d'alerte qui n'ont pas la classe "alert-permanent"
 * et déclenche leur fermeture via Bootstrap après un délai de 5000 millisecondes.
 *
 * @example
 * autoHideAlerts();
 */
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Initialise la confirmation de suppression pour les boutons.
 *
 * Parcourt tous les éléments possédant la classe "btn-delete" et leur associe un écouteur d'événement "click".
 * Lors du clic, une boîte de dialogue demande à l'utilisateur de confirmer la suppression. Si la confirmation est refusée,
 * l'action par défaut est empêchée, évitant ainsi une suppression involontaire.
 */
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

/**
 * Configure le comportement du bouton de basculement de la barre latérale.
 *
 * Cette fonction ajoute un écouteur d'événements sur l'élément avec la classe "navbar-toggler".
 * Lorsqu'on clique sur ce bouton, la fonction bascule la visibilité de la barre latérale
 * en ajoutant ou en retirant la classe "show" sur l'élément avec la classe "sidebar".
 *
 * Note : Si le bouton de basculement n'est pas présent dans le DOM, aucune action n'est effectuée.
 */
function setupSidebarToggle() {
    const sidebarToggle = document.querySelector('.navbar-toggler');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }
}

/**
 * Initialise la gestion des champs de formulaire dynamiques.
 *
 * Cette fonction ajoute un écouteur d'événement "click" à chaque bouton comportant la classe "add-field".
 * Lors d'un clic, elle récupère le conteneur cible et le template défini via les attributs "data-container" et "data-template" du bouton,
 * génère un nouveau champ de formulaire en remplaçant le placeholder "{index}" par le nombre de champs existants,
 * et l'ajoute au conteneur. Si un bouton de suppression est présent dans le nouveau champ (classe "remove-field"), il est configuré
 * pour permettre la suppression du champ lorsqu'il est cliqué.
 */
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

/**
 * Initialise les datepickers avec flatpickr si la bibliothèque est disponible.
 *
 * Cette fonction applique flatpickr sur tous les éléments possédant la classe "datepicker", en configurant le format de date sur "d/m/Y" et la locale sur "fr".
 *
 * @remark Aucun datepicker n'est initialisé si la bibliothèque flatpickr n'est pas définie.
 */
function initDatepickers() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.datepicker', {
            dateFormat: 'd/m/Y',
            locale: 'fr'
        });
    }
}

/**
 * Initialise les éléments select2 avec le thème Bootstrap 5 et une largeur de 100%.
 *
 * La fonction vérifie d'abord si le plugin jQuery select2 est disponible. Si c'est le cas, elle applique
 * l'initialisation à tous les éléments de la page possédant la classe "select2".
 */
function initSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
}

/**
 * Initialise et remplace les icônes avec la bibliothèque Feather.
 *
 * Si la bibliothèque Feather est disponible, cette fonction appelle sa méthode
 * `replace()` pour mettre à jour les éléments d'icônes dans le document.
 */
function initFeatherIcons() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

/**
 * Initialise les tooltips Bootstrap sur tous les éléments possédant l'attribut `data-bs-toggle="tooltip"`.
 *
 * Cette fonction parcourt le document pour trouver les éléments configurés pour afficher des tooltips et crée une instance de tooltip pour chacun d'eux à l'aide du composant Bootstrap.
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
} 