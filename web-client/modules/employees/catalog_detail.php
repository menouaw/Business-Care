<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireEmployeeLogin();

$employee_id = $_SESSION['user_id'];

$prestation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$prestation_id || $prestation_id <= 0) {
    $pageTitle = "Erreur";
    include_once __DIR__ . '/../../templates/header.php';
    echo '<main class="container py-5"><div class="alert alert-danger">ID de prestation invalide.</div>';
    echo '<a href="catalog.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Retour au catalogue</a></main>';
    include_once __DIR__ . '/../../templates/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $appointment_data = [
        'prestation_id' => $prestation_id,
        'praticien_id' => filter_input(INPUT_POST, 'praticien_id', FILTER_VALIDATE_INT),
        'date_rdv' => filter_input(INPUT_POST, 'selected_schedule', FILTER_SANITIZE_STRING), // La valeur du créneau choisi
        'duree' => filter_input(INPUT_POST, 'duree', FILTER_VALIDATE_INT),
        'type_rdv' => 'prestation', // Ou à adapter si nécessaire
        'lieu' => filter_input(INPUT_POST, 'lieu', FILTER_SANITIZE_STRING),
        'notes' => '' // Ajouter un champ si besoin
    ];

    if (empty($appointment_data['date_rdv'])) {
        flashMessage("Veuillez sélectionner un créneau horaire.", "warning");
    } else {
        if (!function_exists('bookEmployeeAppointment')) {
            flashMessage("Erreur: La fonction de réservation n'est pas disponible.", "danger");
        } else {
            $rdvId = bookEmployeeAppointment($employee_id, $appointment_data);
            if ($rdvId) {
                flashMessage("Rendez-vous réservé avec succès ! (ID: $rdvId)", "success");
                header('Location: appointments.php');
                exit;
            } else {
                flashMessage("Erreur lors de la réservation du rendez-vous. Le créneau est peut-être déjà pris ou une erreur est survenue.", "danger");
            }
        }
    }
}

if (!function_exists('getPrestationDetails')) {
    die("Erreur: La fonction getPrestationDetails n'est pas définie.");
}
$prestation = getPrestationDetails($prestation_id);

// 4. Définir le titre de la page
$pageTitle = $prestation ? $prestation['nom'] : "Service introuvable";
$pageDescription = $prestation ? htmlspecialchars(substr($prestation['description'] ?? '', 0, 160)) . '...' : "Le service demandé n'a pas pu être trouvé.";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-5">

    <?php
    if (function_exists('displayFlashMessages')) {
        displayFlashMessages();
    }
    ?>

    <?php if (!$prestation): ?>
        <div class="alert alert-warning">Le service demandé n'existe pas ou n'est plus disponible.</div>
        <a href="catalog.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Retour au catalogue</a>
    <?php else:
        if (!function_exists('getAvailableSchedulesForPrestation')) {
            die("Erreur: La fonction getAvailableSchedulesForPrestation n'est pas définie.");
        }
        $schedules = getAvailableSchedulesForPrestation($prestation_id);
    ?>
        <div class="row">
            <!-- Colonne de gauche : Détails -->
            <div class="col-lg-8 mb-4 mb-lg-0">
                <a href="catalog.php" class="btn btn-outline-secondary btn-sm mb-4">
                    <i class="fas fa-arrow-left me-1"></i> Retour au catalogue
                </a>

                <h1 class="display-6 mb-3"><?= htmlspecialchars($prestation['nom']) ?></h1>

                <div class="mb-3">
                    <?php if (!empty($prestation['type'])): ?>
                        <span class="badge bg-info me-1"><?= htmlspecialchars(ucfirst($prestation['type'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($prestation['categorie'])): ?>
                        <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($prestation['categorie'])) ?></span>
                    <?php endif; ?>
                </div>

                <p class="lead text-muted mb-4"><?= nl2br(htmlspecialchars($prestation['description'] ?? 'Aucune description fournie.')) ?></p>

                <h5>Détails</h5>
                <ul class="list-group list-group-flush mb-4">
                    <?php if (isset($prestation['duree']) && $prestation['duree'] > 0): ?>
                        <li class="list-group-item px-0"><i class="fas fa-clock fa-fw me-2 text-muted"></i>Durée : <?= $prestation['duree'] ?> minutes</li>
                    <?php endif; ?>
                    <?php if (!empty($prestation['praticien_nom'])): ?>
                        <li class="list-group-item px-0"><i class="fas fa-user-md fa-fw me-2 text-muted"></i>Intervenant : <?= htmlspecialchars($prestation['praticien_nom']) ?></li>
                    <?php endif; ?>
                    <?php if (!empty($prestation['lieu'])): ?>
                        <li class="list-group-item px-0"><i class="fas fa-map-marker-alt fa-fw me-2 text-muted"></i>Lieu : <?= htmlspecialchars($prestation['lieu']) ?></li>
                    <?php endif; ?>
                    <li class="list-group-item px-0"><i class="fas fa-euro-sign fa-fw me-2 text-muted"></i>Prix : <?= htmlspecialchars($prestation['prix_formate']) ?></li>
                    <?php if (!empty($prestation['niveau_difficulte'])): ?>
                        <li class="list-group-item px-0"><i class="fas fa-tachometer-alt fa-fw me-2 text-muted"></i>Niveau : <?= htmlspecialchars(ucfirst($prestation['niveau_difficulte'])) ?></li>
                    <?php endif; ?>
                    <?php if (isset($prestation['capacite_max']) && $prestation['capacite_max'] > 0): ?>
                        <li class="list-group-item px-0"><i class="fas fa-users fa-fw me-2 text-muted"></i>Capacité max : <?= $prestation['capacite_max'] ?> personnes</li>
                    <?php endif; ?>
                    <?php if (!empty($prestation['date_disponible_formatee'])): ?>
                        <li class="list-group-item px-0"><i class="fas fa-calendar-check fa-fw me-2 text-muted"></i>Date spécifique : <?= htmlspecialchars($prestation['date_disponible_formatee']) ?></li>
                    <?php endif; ?>
                </ul>

            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm sticky-lg-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0">Réserver cette prestation</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$prestation['est_disponible']): ?>
                            <div class="alert alert-warning small">Cette prestation n'est actuellement pas disponible à la réservation.</div>
                        <?php elseif (empty($schedules)): ?>
                            <div class="alert alert-info small">Aucun créneau horaire n'est actuellement proposé pour cette prestation. Vérifiez la date spécifique si indiquée ou revenez plus tard.</div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <!-- Champs cachés pour la soumission -->
                                <input type="hidden" name="prestation_id" value="<?= $prestation['id'] ?>">
                                <input type="hidden" name="praticien_id" value="<?= $prestation['praticien_id'] ?? '' ?>">
                                <input type="hidden" name="duree" value="<?= $prestation['duree'] ?? 60 ?>">
                                <input type="hidden" name="lieu" value="<?= htmlspecialchars($prestation['lieu'] ?? '') ?>">

                                <p class="mb-2 fw-bold small">Choisissez un créneau disponible :</p>
                                <div class="list-group mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($schedules as $index => $schedule): ?>
                                        <label class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" for="schedule_<?= $index ?>">
                                            <span class="small">
                                                <i class="far fa-calendar-check me-2"></i>
                                                <?= htmlspecialchars($schedule['date_debut_formattee']) ?><br>
                                                <small class="text-muted ms-4">(<?= htmlspecialchars($schedule['praticien_nom']) ?> - <?= htmlspecialchars($schedule['lieu']) ?>)</small>
                                            </span>
                                            <input type="radio" class="form-check-input" name="selected_schedule" id="schedule_<?= $index ?>" value="<?= htmlspecialchars($schedule['date_value']) ?>" required>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <button type="submit" name="book_appointment" class="btn btn-success w-100">Confirmer la réservation</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>