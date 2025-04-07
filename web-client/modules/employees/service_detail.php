<?php
require_once __DIR__ . '/../../includes/init.php';

// Vérifier si l'utilisateur est connecté et est un salarié
requireEmployeeLogin();

$employee_id = $_SESSION['user_id'];

$pageTitle = generatePageTitle('Détails du Service'); // Mettre à jour le titre dynamiquement

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Détails du Service</h1>
    <?php echo displayFlashMessages(); ?>

    <p>Cette page affichera les détails spécifiques d'un service pour le salarié.</p>
    <p>Il faudra récupérer les informations du service depuis la base de données.</p>

    <!-- Contenu des détails du service ici -->

</div>

<?php
include __DIR__ . '/../../includes/footer.php';
?>