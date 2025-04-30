<?php
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/employees/services.php';


$viewData = setupEmployeeServicesPage();
extract($viewData);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if (empty($prestations)):
            ?>
                <div class="alert alert-info">Aucun service disponible dans le catalogue pour le moment.</div>
            <?php else:
            ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($prestations as $presta):

                        $nom = htmlspecialchars($presta['nom'] ?? 'Service sans nom');
                        $description = !empty($presta['description']) ? htmlspecialchars($presta['description']) : 'Pas de description disponible.';
                        $type = htmlspecialchars(ucfirst($presta['type'] ?? 'N/D'));
                        $categorie = htmlspecialchars($presta['categorie'] ?? 'Non classé');
                        $duree = !empty($presta['duree']) ? $presta['duree'] . ' min' : 'N/D';

                        $reserveUrl = WEBCLIENT_URL . '/modules/employees/appointments.php?prestation_id=' . $presta['id'];
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= $nom ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted small">
                                        <i class="fas fa-tag me-1"></i><?= $categorie ?> | <i class="fas fa-puzzle-piece me-1"></i><?= $type ?> | <i class="far fa-clock me-1"></i><?= $duree ?>
                                    </h6>
                                    <p class="card-text small flex-grow-1">
                                        <?= nl2br(substr($description, 0, 150)) . (strlen($description) > 150 ? '...' : '') ?>
                                    </p>
                                    <a href="<?= $reserveUrl ?>" class="btn btn-sm btn-outline-primary align-self-start mt-auto">
                                        <i class="far fa-calendar-alt me-1"></i> Voir disponibilités / Réserver
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php
                
                if (!empty($pagination) && $pagination['totalPages'] > 1) {
                    echo renderPagination($pagination, WEBCLIENT_URL . '/modules/employees/services.php?page={page}');
                }
            endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>