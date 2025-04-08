<?php


require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$pageData = displayEmployeeCommunitiesPage();
$communities = $pageData['communities'] ?? [];

$pageTitle = "Communautés - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';


function getCommunityIcon($type)
{
    switch ($type) {
        case COMMUNITY_TYPES[0]: // sport
            return 'fas fa-futbol';
        case COMMUNITY_TYPES[1]: // bien_etre
            return 'fas fa-spa';
        case COMMUNITY_TYPES[2]: // sante
            return 'fas fa-heartbeat';
        default:
            return 'fas fa-users';
    }
}

?>

<main class="employee-communities-page py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">Communautés</h1>
                <p class="text-muted">Rejoignez des groupes, partagez vos passions et organisez des événements.</p>
            </div>

        </div>

        <?php echo displayFlashMessages(); ?>

        <?php if (empty($communities)) : ?>
            <div class="alert alert-info text-center" role="alert">
                Aucune communauté n'est disponible pour le moment.
            </div>
        <?php else : ?>
            <div class="row g-4">
                <?php foreach ($communities as $community) : ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="icon-box bg-info bg-opacity-10 text-info rounded p-3 me-3 flex-shrink-0">
                                        <i class="<?= getCommunityIcon($community['type'] ?? 'autre') ?> fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($community['nom'] ?? 'Communauté sans nom') ?></h5>
                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars(ucfirst($community['type'] ?? 'Autre')) ?></span>
                                        <?php if (!empty($community['niveau'])) : ?>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars(ucfirst($community['niveau'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="card-text text-muted flex-grow-1"><?= nl2br(htmlspecialchars($community['description'] ?? 'Pas de description.')) ?></p>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <!-- Mettre à jour le lien quand la page détail existe -->
                                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/community_details.php?id=<?= $community['id'] ?>" class="btn btn-sm btn-outline-primary">Voir la communauté</a>
                                    <?php if (!empty($community['capacite_max'])) : ?>
                                        <small class="text-muted"><i class="fas fa-users me-1"></i> <?= htmlspecialchars($community['capacite_max']) ?> max</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>