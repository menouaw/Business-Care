<?php
require_once(__DIR__ . '/../../includes/init.php');

require_once(__DIR__ . '/../../includes/page_functions/modules/employees.php');

requireEmployeeLogin();

$userId = $_SESSION['user_id'];

$upcomingPage = isset($_GET['upcoming_page']) ? (int)$_GET['upcoming_page'] : 1;
$pastPage = isset($_GET['past_page']) ? (int)$_GET['past_page'] : 1;
$canceledPage = isset($_GET['canceled_page']) ? (int)$_GET['canceled_page'] : 1;
$limit = 5;

$prestationPage = isset($_GET['prestation_page']) ? (int)$_GET['prestation_page'] : 1;
$prestationLimit = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserver') {
    $prestationId = filter_var($_POST['prestation_id'] ?? 0, FILTER_VALIDATE_INT);
    $dateRdv = sanitizeInput($_POST['date_rdv'] ?? '');
    $duree = filter_var($_POST['duree'] ?? 0, FILTER_VALIDATE_INT);
    $typeRdv = sanitizeInput($_POST['type_rdv'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $horaireId = sanitizeInput($_POST['horaire_id'] ?? '');

    if ($prestationId && $dateRdv && $duree) {
        $query = "SELECT date_heure_disponible, praticien_id, lieu FROM prestations WHERE id = ?";
        $prestation = executeQuery($query, [$prestationId])->fetch();

        if (!$prestation || empty($prestation['date_heure_disponible'])) {
            flashMessage("Cette prestation n'est pas disponible ou n'a pas d'horaire défini", "warning");
        } else {
            $datePrestation = new DateTime($prestation['date_heure_disponible']);
            $now = new DateTime();

            if ($datePrestation < $now) {
                flashMessage("Impossible de réserver une prestation dont l'horaire est déjà passé.", "warning");
            } else {
                $appointmentData = [
                    'prestation_id' => $prestationId,
                    'praticien_id' => $prestation['praticien_id'],
                    'date_rdv' => $prestation['date_heure_disponible'], // Utiliser l'heure stockée
                    'duree' => $duree,
                    'type_rdv' => $typeRdv,
                    'lieu' => $prestation['lieu']??'',
                    'notes' => $notes
                ];

                $result = bookEmployeeAppointment($userId, $appointmentData);

                if ($result) {
                    $checkCapacityQuery = "SELECT capacite_max FROM prestations WHERE id = ?";
                    $capacityResult = executeQuery($checkCapacityQuery, [$prestationId])->fetch();
                    if (!$capacityResult || empty($capacityResult['capacite_max']) || $capacityResult['capacite_max'] <= 1) {
                        $updateQuery = "UPDATE prestations SET est_disponible = FALSE WHERE id = ?";
                        executeQuery($updateQuery, [$prestationId]);
                    }

                    flashMessage("Votre rendez-vous a été réservé avec succès", "success");
                } else {
                    // Le message flash d'erreur est déjà géré dans bookEmployeeAppointment
                    // flashMessage("Une erreur est survenue lors de la réservation", "danger");
                }
            }
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?upcoming_page=" . $upcomingPage . "&past_page=" . $pastPage . "&canceled_page=" . $canceledPage);        exit;
    } else {
        flashMessage("Veuillez remplir tous les champs obligatoires", "warning");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'annuler' || $_POST['action'] === 'annule')) {
    $rdvId = filter_var($_POST['rdv_id'] ?? 0, FILTER_VALIDATE_INT);

    if ($rdvId) {
        $cancellationSuccess = cancelEmployeeAppointment($userId, $rdvId);
        if ($cancellationSuccess) {
            $message = $_POST['action'] === 'annuler'
                ? "Votre rendez-vous a été annulé avec succès. Il a été déplacé dans la section 'Rendez-vous annulés'."
                : "Le rendez-vous a été marqué comme 'Annulé' et déplacé dans la section correspondante."; // Garder cette option ?
            flashMessage($message, "success");
        }
        // Si cancelEmployeeAppointment retourne false, un message flash (erreur, warning, info) a déjà été défini.

        // Redirige dans tous les cas pour afficher le message flash et rafraîchir l'état
        $upcomingPage = isset($_GET['upcoming_page']) ? (int)$_GET['upcoming_page'] : 1;
        $pastPage = isset($_GET['past_page']) ? (int)$_GET['past_page'] : 1;
        $canceledPage = isset($_GET['canceled_page']) ? (int)$_GET['canceled_page'] : 1;
        header("Location: " . $_SERVER['PHP_SELF'] . "?upcoming_page=" . $upcomingPage . "&past_page=" . $pastPage . "&canceled_page=" . $canceledPage);
        exit;
    }
    // else: Si $rdvId est invalide, on pourrait ajouter un flashMessage ici aussi, mais cancelEmployeeAppointment le gère déjà.
}

$upcomingAppointmentsData = getEmployeeAppointments($userId, 'upcoming', $upcomingPage, $limit);
$pastAppointmentsData = getEmployeeAppointments($userId, 'past', $pastPage, $limit);
$canceledAppointmentsData = getEmployeeAppointments($userId, 'canceled', $canceledPage, $limit);

$upcomingAppointments = $upcomingAppointmentsData['appointments'];
$upcomingPagination = $upcomingAppointmentsData['pagination'];
$upcomingPaginationHtml = $upcomingAppointmentsData['pagination_html'];

$pastAppointments = $pastAppointmentsData['appointments'];
$pastPagination = $pastAppointmentsData['pagination'];
$pastPaginationHtml = $pastAppointmentsData['pagination_html'];

$canceledAppointments = $canceledAppointmentsData['appointments'];
$canceledPagination = $canceledAppointmentsData['pagination'];
$canceledPaginationHtml = $canceledAppointmentsData['pagination_html'];

// Récupérer les prestations avec pagination
$availablePrestationsData = getAvailablePrestationsForEmployee($userId, $prestationPage, $prestationLimit);
$availablePrestations = $availablePrestationsData['prestations'];
$prestationPagination = $availablePrestationsData['pagination'];
$prestationPaginationHtml = $availablePrestationsData['pagination_html'];

$pageTitle = "Mes rendez-vous";
require_once(__DIR__ . '/../../templates/header.php');
?>

<div class="container py-5">
    <h1 class="mb-4">Mes rendez-vous</h1>

    <div class="mb-4">
        <a href="../../modules/employees/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
    <?php endif; ?>

    <!-- Carte pour prendre rendez-vous -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-calendar-plus me-2"></i>Prendre rendez-vous</h5>
        </div>
        <div class="card-body">
            <p>Vous souhaitez consulter un praticien ? Prenez rendez-vous selon vos disponibilités.</p>
            <div class="text-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalReservation">
                    <i class="fas fa-user-md me-2"></i>Réserver une prestation
                </button>
            </div>
        </div>
    </div>

    <!-- Section Rendez-vous à venir -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Rendez-vous à venir</h5>
        </div>
        <div class="card-body">
            <?php if (empty($upcomingAppointments)): ?>
                <p class="text-muted">Aucun rendez-vous à venir</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activité</th>
                                <th>Prestation</th>
                                <th>Praticien/Animateur</th>
                                <th>Type</th>
                                <th>Lieu</th>
                                <th>Durée</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                <tr>
                                    <td><?= $appointment['date_rdv_formatee'] ?? 'N/A' ?></td>
                                    <td><?= htmlspecialchars($appointment['evenement_titre'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['prestation_nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['praticien_nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['type_rdv'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['lieu'] ?? $appointment['evenement_lieu'] ?? 'N/A') ?></td>
                                    <td><?= isset($appointment['duree']) ? $appointment['duree'] . ' min' : 'N/A' ?></td>
                                    <td><?= $appointment['statut_badge'] ?? 'N/A' ?></td>
                                    <td>
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalAnnulation"
                                                data-rdv-id="<?= $appointment['rdv_id'] ?>"
                                                data-rdv-date="<?= $appointment['date_rdv_formatee'] ?>"
                                                data-rdv-prestation="<?= htmlspecialchars($appointment['prestation_nom'] ?? 'N/A') ?>">
                                                <i class="fas fa-times"></i> Annuler
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination pour les rendez-vous à venir -->
                <?php if ($upcomingPagination['totalPages'] > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <?= $upcomingPaginationHtml ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section Rendez-vous annulés -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0">Rendez-vous annulés</h5>
        </div>
        <div class="card-body">
            <?php if (empty($canceledAppointments)): ?>
                <p class="text-muted">Aucun rendez-vous annulé</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activité</th>
                                <th>Prestation</th>
                                <th>Praticien/Animateur</th>
                                <th>Type</th>
                                <th>Lieu</th>
                                <th>Durée</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($canceledAppointments as $appointment): ?>
                                <tr>
                                    <td><?= $appointment['date_rdv_formatee'] ?? 'N/A' ?></td>
                                    <td><?= htmlspecialchars($appointment['evenement_titre'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['prestation_nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['praticien_nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['type_rdv'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['lieu'] ?? $appointment['evenement_lieu'] ?? 'N/A') ?></td>
                                    <td><?= isset($appointment['duree']) ? $appointment['duree'] . ' min' : 'N/A' ?></td>
                                    <td><?= $appointment['statut_badge'] ?? 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination pour les rendez-vous annulés -->
                <?php if ($canceledPagination['totalPages'] > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <?= $canceledPaginationHtml ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section Historique des rendez-vous -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Historique des rendez-vous</h5>
        </div>
        <div class="card-body">
            <?php if (empty($pastAppointments)): ?>
                <p class="text-muted">Aucun rendez-vous passé</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activité</th>
                                <th>Prestation</th>
                                <th>Praticien/Animateur</th>
                                <th>Type</th>
                                <th>Lieu</th>
                                <th>Durée</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastAppointments as $appointment): ?>
                                <tr>
                                    <td><?= $appointment['date_rdv_formatee'] ?? 'N/A' ?></td>
                                    <td><?= htmlspecialchars($appointment['evenement_titre'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['prestation_nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['praticien_nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['type_rdv'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($appointment['lieu'] ?? $appointment['evenement_lieu'] ?? 'N/A') ?></td>
                                    <td><?= isset($appointment['duree']) ? $appointment['duree'] . ' min' : 'N/A' ?></td>
                                    <td><?= $appointment['statut_badge'] ?? 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination pour l'historique des rendez-vous -->
                <?php if ($pastPagination['totalPages'] > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <?= $pastPaginationHtml ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour la réservation -->
<div class="modal fade" id="modalReservation" tabindex="-1" aria-labelledby="modalReservationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalReservationLabel">Réserver une prestation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <h4 class="mb-3">Prestations disponibles</h4>

                <?php if (empty($availablePrestations)): ?>
                    <div class="alert alert-info">
                        Aucune prestation disponible actuellement.
                    </div>
                <?php else: ?>
                    <div class="list-group mb-4">
                        <?php foreach ($availablePrestations as $prestation): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($prestation['nom']) ?></h5>
                                    <small><?= $prestation['duree'] ?? 0 ?> min - <?= $prestation['prix_formate'] ?? 'N/A' ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($prestation['description'] ?? 'Aucune description disponible') ?></p>
                                <small class="text-muted">
                                    Type: <?= htmlspecialchars($prestation['type'] ?? 'N/A') ?> |
                                    Niveau: <?= htmlspecialchars($prestation['niveau_difficulte'] ?? 'N/A') ?>
                                </small>

                                <!-- Informations sur l'horaire disponible -->
                                <div class="mt-2 mb-2 bg-light p-2 rounded">
                                    <strong>Horaire disponible:</strong>
                                    <?= $prestation['date_disponible_formatee'] ?? 'Non disponible' ?>
                                    <?php if (!empty($prestation['praticien_nom'])): ?>
                                        avec <?= htmlspecialchars($prestation['praticien_nom']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($prestation['lieu'])): ?>
                                        à <?= htmlspecialchars($prestation['lieu']) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-2">
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="reserver">
                                        <input type="hidden" name="prestation_id" value="<?= $prestation['id'] ?>">
                                        <input type="hidden" name="date_rdv" value="<?= $prestation['date_heure_disponible'] ?>">
                                        <input type="hidden" name="duree" value="<?= $prestation['duree'] ?>">
                                        <input type="hidden" name="horaire_id" value="<?= $prestation['id'] ?>-0">

                                        <div class="row align-items-end g-3">
                                            <div class="col-md-6">
                                                <label for="type_rdv_<?= $prestation['id'] ?>" class="form-label">Type de rendez-vous</label>
                                                <select class="form-select form-select-sm" id="type_rdv_<?= $prestation['id'] ?>" name="type_rdv" required>
                                                    <option value="presentiel">Présentiel</option>
                                                    <option value="visio">Visioconférence</option>
                                                    <option value="telephone">Téléphone</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="notes_<?= $prestation['id'] ?>" class="form-label">Notes</label>
                                                <input type="text" class="form-control form-control-sm" id="notes_<?= $prestation['id'] ?>" name="notes" placeholder="Facultatif">
                                            </div>
                                            <div class="col-md-12 text-end">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-calendar-check me-1"></i> Réserver
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination pour les prestations disponibles -->
                    <?php if ($prestationPagination['totalPages'] > 1): ?>
                        <div class="d-flex justify-content-center mt-3">
                            <?= $prestationPaginationHtml ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAnnulation" tabindex="-1" aria-labelledby="modalAnnulationLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalAnnulationLabel">Confirmer l'annulation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Êtes-vous sûr de vouloir annuler ce rendez-vous ?</p>
                <ul class="list-group mt-3">
                    <li class="list-group-item"><strong>Date :</strong> <span id="rdv-date-annulation"></span></li>
                    <li class="list-group-item"><strong>Prestation :</strong> <span id="rdv-prestation-annulation"></span></li>
                </ul>
                <p class="text-muted mt-3 small">Une fois annulé, ce rendez-vous sera déplacé dans la section "Rendez-vous annulés" et la plage horaire sera à nouveau disponible pour d'autres réservations.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="post" action="" id="formAnnulation">
                    <input type="hidden" name="rdv_id" id="rdv-id-annulation">
                    <button type="submit" class="btn btn-danger" name="action" value="annuler">Confirmer l'annulation</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/../../templates/footer.php'); ?>