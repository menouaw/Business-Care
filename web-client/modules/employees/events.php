<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/events.php';

$viewData = setupEventsPage();

$pageTitle = $viewData['pageTitle'];
$preferredEvents = $viewData['preferredEvents'];
$otherEvents = $viewData['otherEvents'];

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="btn btn-sm btn-outline-info me-2">
                        <i class="fas fa-th-list me-1"></i> Retour Catalogue Services
                    </a>
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>


            <h3 class="mt-4 mb-3">Pour vous</h3>
            <?php if (empty($preferredEvents)): ?>
                <div class="alert alert-light text-muted">Aucun événement correspondant à vos intérêts n'est programmé pour le moment.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                    <?php foreach ($preferredEvents as $event):

                        $titre = htmlspecialchars($event['titre'] ?? 'Événement sans titre');
                        $descriptionCourte = !empty($event['description']) ? htmlspecialchars(substr($event['description'], 0, 100)) . '...' : 'Pas de description.';
                        $dateDebut = !empty($event['date_debut']) ? formatDate($event['date_debut'], 'd/m/Y H:i') : 'Date inconnue';
                        $dateFin = !empty($event['date_fin']) ? formatDate($event['date_fin'], 'H:i') : '';
                        $lieu = !empty($event['lieu']) ? htmlspecialchars($event['lieu']) : 'Lieu non spécifié';
                        $type = !empty($event['type']) ? ucfirst(htmlspecialchars($event['type'])) : 'Type inconnu';
                        $icon = getServiceIcon($event['type'] ?? 'autre');
                        $isRegistered = $event['is_registered'] ?? false;
                        $isFull = (isset($event['remaining_spots']) && $event['remaining_spots'] === 0);
                        $currentPage = (isset($pagination) && isset($pagination['currentPage'])) ? $pagination['currentPage'] : 1;
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <strong class="text-primary"><i class="<?= $icon ?> me-2"></i><?= $type ?></strong>
                                    <small class="text-muted"><?= $dateDebut ?><?= $dateFin ? ' - ' . $dateFin : '' ?></small>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= $titre ?></h5>
                                    <p class="card-text flex-grow-1"><small><?= $descriptionCourte ?></small></p>
                                    <p class="card-text mb-1"><small><strong>Lieu :</strong> <?= $lieu ?></small></p>
                                    <p class="card-text mb-2">
                                        <small>
                                            <strong>Capacité :</strong>
                                            <?php if (isset($event['capacite_max']) && $event['capacite_max'] > 0): ?>
                                                <?php if ($event['remaining_spots'] !== null): ?>
                                                    Places restantes: <?= $event['remaining_spots'] ?> / <?= $event['capacite_max'] ?>
                                                <?php else: ?>
                                                    <?= $event['capacite_max'] ?> (Info indisponible)
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Illimitée
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                    <?php if ($isRegistered): ?>
                                        <a href="?action=unregister&id=<?= $event['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-danger mt-auto align-self-start">
                                            <i class="fas fa-times-circle me-1"></i> Se désinscrire
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=register&id=<?= $event['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-success mt-auto align-self-start<?= $isFull ? ' disabled' : '' ?>" <?= $isFull ? 'aria-disabled="true"' : '' ?>>
                                            <i class="fas fa-<?= $isFull ? 'exclamation-circle' : 'check-circle' ?> me-1"></i> <?= $isFull ? 'Complet' : 'S\'inscrire' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr>


            <h3 class="mt-4 mb-3">Autres événements</h3>
            <?php if (empty($otherEvents)): ?>
                <div class="alert alert-light text-muted">Aucun autre événement à venir n'est programmé pour le moment.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                    <?php foreach ($otherEvents as $event):

                        $titre = htmlspecialchars($event['titre'] ?? 'Événement sans titre');
                        $descriptionCourte = !empty($event['description']) ? htmlspecialchars(substr($event['description'], 0, 100)) . '...' : 'Pas de description.';
                        $dateDebut = !empty($event['date_debut']) ? formatDate($event['date_debut'], 'd/m/Y H:i') : 'Date inconnue';
                        $dateFin = !empty($event['date_fin']) ? formatDate($event['date_fin'], 'H:i') : '';
                        $lieu = !empty($event['lieu']) ? htmlspecialchars($event['lieu']) : 'Lieu non spécifié';
                        $type = !empty($event['type']) ? ucfirst(htmlspecialchars($event['type'])) : 'Type inconnu';
                        $icon = getServiceIcon($event['type'] ?? 'autre');
                        $isRegistered = $event['is_registered'] ?? false;
                        $isFull = (isset($event['remaining_spots']) && $event['remaining_spots'] === 0);
                        $currentPage = (isset($pagination) && isset($pagination['currentPage'])) ? $pagination['currentPage'] : 1;
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <strong class="text-primary"><i class="<?= $icon ?> me-2"></i><?= $type ?></strong>
                                    <small class="text-muted"><?= $dateDebut ?><?= $dateFin ? ' - ' . $dateFin : '' ?></small>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= $titre ?></h5>
                                    <p class="card-text flex-grow-1"><small><?= $descriptionCourte ?></small></p>
                                    <p class="card-text mb-1"><small><strong>Lieu :</strong> <?= $lieu ?></small></p>
                                    <p class="card-text mb-2">
                                        <small>
                                            <strong>Capacité :</strong>
                                            <?php if (isset($event['capacite_max']) && $event['capacite_max'] > 0): ?>
                                                <?php if ($event['remaining_spots'] !== null): ?>
                                                    Places restantes: <?= $event['remaining_spots'] ?> / <?= $event['capacite_max'] ?>
                                                <?php else: ?>
                                                    <?= $event['capacite_max'] ?> (Info indisponible)
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Illimitée
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                    <?php if ($isRegistered): ?>
                                        <a href="?action=unregister&id=<?= $event['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-danger mt-auto align-self-start">
                                            <i class="fas fa-times-circle me-1"></i> Se désinscrire
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=register&id=<?= $event['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-success mt-auto align-self-start<?= $isFull ? ' disabled' : '' ?>" <?= $isFull ? 'aria-disabled="true"' : '' ?>>
                                            <i class="fas fa-<?= $isFull ? 'exclamation-circle' : 'check-circle' ?> me-1"></i> <?= $isFull ? 'Complet' : 'S\'inscrire' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>