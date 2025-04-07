<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireEmployeeLogin();

$employee_id = $_SESSION['user_id'];
$pageTitle = generatePageTitle('Mes Événements');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(); 

    if (isset($_POST['register_event'])) {
        $event_to_register = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
        if ($event_to_register) {
            registerEmployeeToEvent($employee_id, $event_to_register);
            redirectTo('events.php'); 
            exit;
        } else {
            flashMessage("ID d'événement invalide pour l'inscription.", "danger");
        }
    } elseif (isset($_POST['unregister_event'])) { // Gérer la désinscription
        $event_to_unregister = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
        if ($event_to_unregister) {
            unregisterEmployeeFromEvent($employee_id, $event_to_unregister); // Appel de la nouvelle fonction
            redirectTo('events.php'); 
            exit;
        } else {
            flashMessage("ID d'événement invalide pour la désinscription.", "danger");
        }
    }
}

$events = getEmployeeEvents($employee_id);

// Récupérer les IDs des événements auxquels l'employé est inscrit en utilisant la nouvelle fonction
$registeredEventIds = getRegisteredEventIds($employee_id);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Événements à venir</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
        </a>
    </div>

    <?php echo displayFlashMessages(); ?>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Liste des événements</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($events)) : ?>
                <div class="alert alert-info mb-0" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Aucun événement à venir pour le moment.
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Titre</th>
                                <th><i class="fas fa-tag me-1"></i>Type</th>
                                <th><i class="fas fa-calendar-alt me-1"></i>Début</th>
                                <th><i class="fas fa-calendar-alt me-1"></i>Fin</th>
                                <th><i class="fas fa-map-marker-alt me-1"></i>Lieu</th>
                                <th><i class="fas fa-users me-1"></i>Capacité</th>
                                <th><i class="fas fa-level-up-alt me-1"></i>Niveau</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event) :
                                $typeBadgeClass = 'bg-secondary';
                                switch ($event['type']) {
                                    case 'conference':
                                        $typeBadgeClass = 'bg-primary';
                                        break;
                                    case 'webinar':
                                        $typeBadgeClass = 'bg-info text-dark';
                                        break;
                                    case 'atelier':
                                        $typeBadgeClass = 'bg-success';
                                        break;
                                    case 'defi_sportif':
                                        $typeBadgeClass = 'bg-warning text-dark';
                                        break;
                                    case 'autre':
                                        $typeBadgeClass = 'bg-secondary';
                                        break;
                                }
                                $niveauBadgeClass = 'bg-light text-dark';
                                if ($event['niveau_difficulte'] === 'debutant') $niveauBadgeClass = 'bg-success';
                                elseif ($event['niveau_difficulte'] === 'intermediaire') $niveauBadgeClass = 'bg-warning text-dark';
                                elseif ($event['niveau_difficulte'] === 'avance') $niveauBadgeClass = 'bg-danger';

                                $isRegistered = in_array($event['id'], $registeredEventIds);
                            ?>
                                <tr>
                                    <td><?= sanitizeInput($event['titre']) ?></td>
                                    <td><span class="badge <?= $typeBadgeClass ?>"><?= ucfirst(sanitizeInput($event['type'])) ?></span></td>
                                    <td><?= $event['date_debut_formatted'] ?? 'N/A' ?></td>
                                    <td><?= $event['date_fin_formatted'] ?? 'N/A' ?></td>
                                    <td><?= sanitizeInput($event['lieu']) ?: 'N/A' ?></td>
                                    <td>
                                        <?php
                                        $capaciteText = 'N/A';
                                        if (!empty($event['capacite_max'])) {
                                            $inscrits = $event['nombre_inscrits'] ?? 0; // Récupérer le nombre d'inscrits
                                            $max = $event['capacite_max'];
                                            $capaciteText = "{$inscrits} / {$max}";
                                        } elseif (isset($event['nombre_inscrits'])) {
                                            // Si capacité_max non définie mais nombre_inscrits oui
                                            $capaciteText = ($event['nombre_inscrits'] ?? 0) . " inscrits";
                                        }
                                        echo sanitizeInput($capaciteText);
                                        ?>
                                    </td>
                                    <td><span class="badge <?= $niveauBadgeClass ?>"><?= $event['niveau_formatted'] ?? 'N/A' ?></span></td>
                                    <td class="small" title="<?= sanitizeInput($event['description']) ?>">
                                        <?= nl2br(sanitizeInput(substr($event['description'], 0, 50))) . (strlen($event['description']) > 50 ? '...' : '') ?>
                                    </td>
                                    <td>
                                        <?php if ($isRegistered): ?>
                                            <!-- Formulaire de désinscription -->
                                            <form action="events.php" method="POST" class="d-inline">
                                                <input type="hidden" name="unregister_event" value="1">
                                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Se désinscrire de cet événement">
                                                    <i class="fas fa-times me-1"></i> Se désinscrire
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Formulaire d'inscription (existant) -->
                                            <form action="events.php" method="POST" class="d-inline">
                                                <input type="hidden" name="register_event" value="1">
                                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"> <!-- Important pour la sécurité -->
                                                <button type="submit" class="btn btn-sm btn-success" title="Participer à cet événement">
                                                    <i class="fas fa-plus me-1"></i> Participer
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
// Inclure le pied de page
include __DIR__ . '/../../templates/footer.php';
?>