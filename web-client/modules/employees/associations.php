<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../../shared/web-client/db.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/associations.php';
requireRole(ROLE_SALARIE);

$associations = getAssociationsList();
$pageTitle = "Plateforme des Associations";

$selectedAsso = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    foreach ($associations as $a) {
        if ($a['id'] == $id) {
            $selectedAsso = $a;
            break;
        }
    }
    
    if ($selectedAsso) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT titre, description FROM association_projets WHERE association_id = ? ORDER BY id ASC');
        $stmt->execute([$selectedAsso['id']]);
        $selectedAsso['projets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include __DIR__ . '/../../templates/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Plateforme des Associations</h1>
            </div>
            <div class="row">
                <?php
                
                $topThree = array_slice($associations, 0, 3);
                foreach ($topThree as $asso) :
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <img src="<?= htmlspecialchars($asso['logo']) ?>" class="card-img-top" alt="Logo <?= htmlspecialchars($asso['nom']) ?>" style="max-height:120px;object-fit:contain;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($asso['nom']) ?></h5>
                                <?php if (!empty($asso['resume'])): ?>
                                    <p class="card-text small flex-grow-1"><?= htmlspecialchars($asso['resume']) ?></p>
                                <?php endif; ?>
                                <a href="associations.php?id=<?= $asso['id'] ?>" class="btn btn-outline-primary mt-2<?= ($selectedAsso && $selectedAsso['id'] == $asso['id']) ? ' active' : '' ?>">Découvrir</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
            
            $rest = array_slice($associations, 3);
            if (count($rest) > 0) :
            ?>
            <div class="row mt-4">
                <?php foreach ($rest as $asso) : ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <img src="<?= htmlspecialchars($asso['logo']) ?>" class="card-img-top" alt="Logo <?= htmlspecialchars($asso['nom']) ?>" style="max-height:120px;object-fit:contain;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($asso['nom']) ?></h5>
                                <?php if (!empty($asso['resume'])): ?>
                                    <p class="card-text small flex-grow-1"><?= htmlspecialchars($asso['resume']) ?></p>
                                <?php endif; ?>
                                <a href="associations.php?id=<?= $asso['id'] ?>" class="btn btn-outline-primary mt-2<?= ($selectedAsso && $selectedAsso['id'] == $asso['id']) ? ' active' : '' ?>">Découvrir</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($selectedAsso): ?>
                <div class="card shadow mb-4 mt-4">
                    <div class="card-body">
                        <h2 class="card-title mb-3"><?= htmlspecialchars($selectedAsso['nom']) ?></h2>
                        <?php if (!empty($selectedAsso['resume'])): ?>
                            <p class="text-muted mb-3"><strong>Résumé :</strong> <?= nl2br(htmlspecialchars($selectedAsso['resume'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($selectedAsso['histoire'])): ?>
                            <p class="mb-4"><strong>Histoire :</strong><br><?= nl2br(htmlspecialchars($selectedAsso['histoire'])) ?></p>
                        <?php endif; ?>
                        <h5 class="mt-4">Projets / Actions</h5>
                        <?php if (empty($selectedAsso['projets'])): ?>
                            <p class="text-muted">Aucun projet enregistré pour cette association.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($selectedAsso['projets'] as $projet): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($projet['titre']) ?></strong>
                                        <?php if (!empty($projet['description'])): ?>
                                            <br><span class="small text-muted"><?= nl2br(htmlspecialchars($projet['description'])) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
