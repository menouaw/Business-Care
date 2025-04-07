<?php
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireEmployeeLogin();

$employee_id = $_SESSION['user_id'] ?? null;
$pdo = getDbConnection(); // Assure la disponibilité de la connexion PDO

$pageTitle = "Défis Sportifs";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employee_id) {
    $challenge_id_post = filter_input(INPUT_POST, 'challenge_id', FILTER_VALIDATE_INT);

    if ($challenge_id_post) {
        if (isset($_POST['register_challenge'])) {
            if (registerEmployeeToEvent($employee_id, $challenge_id_post)) {
                // Le message flash est défini dans la fonction
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } elseif (isset($_POST['unregister_challenge'])) {
            if (unregisterEmployeeFromEvent($employee_id, $challenge_id_post)) {
            }
            // Redirection pour éviter la resoumission du formulaire
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        flashMessage("Action invalide ou ID de défi manquant.", "danger");
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$registered_challenge_ids = [];
if ($employee_id) {
    $registered_challenge_ids = getRegisteredEventIds($employee_id); // Utilise la fonction existante pour les événements
}


$challenges = [];
$dbError = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, titre, description, date_debut, date_fin, lieu, niveau_difficulte, materiel_necessaire, 
                   (SELECT COUNT(*) FROM evenements_participants WHERE evenement_id = e.id AND statut_inscription = 'inscrit') as nombre_inscrits, 
                   capacite_max
            FROM evenements e
            WHERE type = 'defi_sportif'
              AND (date_fin >= CURDATE() OR date_fin IS NULL) -- Défis en cours ou futurs
            ORDER BY date_debut ASC
        ");
        $stmt->execute();
        $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error fetching challenges: " . $e->getMessage());
        $dbError = "Une erreur est survenue lors de la récupération des défis sportifs.";
    }
} else {
    $dbError = "Impossible de se connecter à la base de données.";
}


require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-trophy text-primary me-2"></i>Défis Sportifs</h1>
        <!-- Peut-être un bouton pour filtrer ou autre ? -->
    </div>
    <p class="lead mb-4">Relevez le défi, restez en forme et connectez-vous avec vos collègues !</p>

    <?php include __DIR__ . '/../../templates/flash_messages.php'; ?>


    <?php if ($dbError): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($dbError) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($challenges) && !$dbError): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>Aucun défi sportif n'est programmé pour le moment. Consultez cette page régulièrement !
        </div>
    <?php elseif (!empty($challenges)): ?>
        <div class="row g-4">
            <?php foreach ($challenges as $challenge):
                $is_registered = in_array($challenge['id'], $registered_challenge_ids);
                $is_full = isset($challenge['capacite_max']) && $challenge['capacite_max'] !== null && ($challenge['nombre_inscrits'] ?? 0) >= $challenge['capacite_max'];
                $can_register = !$is_registered && !$is_full;
                $can_unregister = $is_registered;
            ?>
                <div class="col-md-6 col-lg-4 d-flex">
                    <div class="card h-100 border-light shadow-sm challenge-card flex-fill">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="card-title mb-0 text-truncate"><?= htmlspecialchars($challenge['titre']) ?></h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="card-text small flex-grow-1"><?= nl2br(htmlspecialchars($challenge['description'] ?? 'Pas de description détaillée.')) ?></p>
                            <hr class="my-2">
                            <ul class="list-unstyled small mb-3">
                                <?php if ($challenge['date_debut']): ?>
                                    <li class="mb-1"><i class="fas fa-calendar-alt fa-fw text-muted me-2"></i> <strong>Début :</strong> <?= formatDate($challenge['date_debut'], 'd/m/Y à H:i') ?></li>
                                <?php endif; ?>
                                <?php if ($challenge['date_fin']): ?>
                                    <li class="mb-1"><i class="fas fa-calendar-check fa-fw text-muted me-2"></i> <strong>Fin :</strong> <?= formatDate($challenge['date_fin'], 'd/m/Y à H:i') ?></li>
                                <?php endif; ?>
                                <?php if ($challenge['lieu']): ?>
                                    <li class="mb-1"><i class="fas fa-map-marker-alt fa-fw text-muted me-2"></i> <strong>Lieu :</strong> <?= htmlspecialchars($challenge['lieu']) ?></li>
                                <?php endif; ?>
                                <?php if ($challenge['niveau_difficulte']): ?>
                                    <li class="mb-1"><i class="fas fa-tachometer-alt fa-fw text-muted me-2"></i> <strong>Niveau :</strong> <span class="badge bg-info text-dark"><?= ucfirst(htmlspecialchars($challenge['niveau_difficulte'])) ?></span></li>
                                <?php endif; ?>
                                <?php if ($challenge['materiel_necessaire']): ?>
                                    <li class="mb-1"><i class="fas fa-tools fa-fw text-muted me-2"></i> <strong>Matériel requis :</strong> <?= htmlspecialchars($challenge['materiel_necessaire']) ?></li>
                                <?php endif; ?>
                                <?php if (isset($challenge['capacite_max']) && $challenge['capacite_max'] !== null):
                                    $places_disponibles = $challenge['capacite_max'] - ($challenge['nombre_inscrits'] ?? 0);
                                    $badge_class = 'bg-secondary'; // Défaut
                                    $badge_text = 'N/A';
                                    if ($places_disponibles > 0) {
                                        $badge_class = $places_disponibles < 5 ? 'bg-warning text-dark' : 'bg-success';
                                        $badge_text = $places_disponibles . ' restante(s)';
                                    } elseif ($places_disponibles <= 0) { // Changed to <= 0 for safety
                                        $badge_class = 'bg-danger';
                                        $badge_text = 'Complet';
                                    }
                                ?>
                                    <li class="mb-1"><i class="fas fa-users fa-fw text-muted me-2"></i> <strong>Places :</strong> <?= htmlspecialchars($challenge['nombre_inscrits'] ?? 0) ?> / <?= htmlspecialchars($challenge['capacite_max']) ?>
                                        <span class="badge <?= $badge_class ?> ms-2"><?= $badge_text ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <div class="text-center mt-auto">
                                <?php if ($is_registered): ?>
                                    <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" class="d-inline">
                                        <input type="hidden" name="challenge_id" value="<?= $challenge['id'] ?>">
                                        <button type="submit" name="unregister_challenge" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times-circle me-1"></i> Se désinscrire
                                        </button>
                                    </form>
                                <?php elseif ($is_full): ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        <i class="fas fa-ban me-1"></i> Complet
                                    </button>
                                <?php else: // Peut s'inscrire 
                                ?>
                                    <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" class="d-inline">
                                        <input type="hidden" name="challenge_id" value="<?= $challenge['id'] ?>">
                                        <button type="submit" name="register_challenge" class="btn btn-primary btn-sm">
                                            <i class="fas fa-pencil-alt me-1"></i> S'inscrire
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <!-- Lien Détails (optionnel) -->
                                <!-- <a href="challenge_details.php?id=<?= $challenge['id'] ?>" class="btn btn-outline-secondary btn-sm ms-2">Détails</a> -->
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>



<?php

require_once __DIR__ . '/../../templates/footer.php';
?>