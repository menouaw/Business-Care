<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/habilitations.php';


requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;
$pageTitle = "Mes Habilitations";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_habilitation'])) {
    handleHabilitationAddRequest($provider_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_habilitation'])) {
    verifyCsrfToken();
    $habilitation_id_to_delete = filter_input(INPUT_POST, 'habilitation_id', FILTER_VALIDATE_INT);
    if ($habilitation_id_to_delete) {
        if (deleteProviderHabilitation($habilitation_id_to_delete, $provider_id)) {
            
        } else {
            
        }
    } else {
        flashMessage("ID d'habilitation invalide pour la suppression.", "danger");
    }
    redirectTo(WEBCLIENT_URL . '/modules/providers/habilitations.php');
    exit;
}


$habilitations = getProviderHabilitations($provider_id);


include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php';
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                    </a>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addHabilitationModal">
                        <i class="fas fa-plus me-1"></i> Ajouter une Habilitation
                    </button>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    Liste de vos qualifications et certifications
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Nom du Document</th>
                                    <th>Organisme</th>
                                    <th>Date Obtention</th>
                                    <th>Date Expiration</th>
                                    <th>Statut</th>
                                    <th>Document</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($habilitations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Aucune habilitation enregistrée.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($habilitations as $hab): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(ucfirst($hab['type'] ?? 'N/A')) ?></td>
                                            <td><?= htmlspecialchars($hab['nom_document'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($hab['organisme_emission'] ?? 'N/A') ?></td>
                                            <td><?= $hab['date_obtention'] ? htmlspecialchars(date('d/m/Y', strtotime($hab['date_obtention']))) : 'N/A' ?></td>
                                            <td><?= $hab['date_expiration'] ? htmlspecialchars(date('d/m/Y', strtotime($hab['date_expiration']))) : 'N/A' ?></td>
                                            <td>
                                                <span class="badge bg-<?= getHabilitationStatusBadgeClass($hab['statut']) ?>">
                                                    <?= htmlspecialchars(str_replace('_', ' ', ucfirst($hab['statut']))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($hab['document_url'])): ?>
                                                    <a href="<?= htmlspecialchars($hab['document_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir document">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($hab['statut'] === 'en_attente_validation'): ?>
                                                    <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/providers/habilitations.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette habilitation en attente ?');">
                                                        <input type="hidden" name="delete_habilitation" value="1">
                                                        <input type="hidden" name="habilitation_id" value="<?= $hab['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-danger" disabled title="Suppression impossible">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text">
                        Le statut "En attente de validation" signifie que nos équipes doivent vérifier votre document. "Vérifiée" indique que tout est en ordre.
                    </div>
                </div>
            </div>

            <!-- Modal Ajout Habilitation -->
            <div class="modal fade" id="addHabilitationModal" tabindex="-1" aria-labelledby="addHabilitationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addHabilitationModalLabel">Ajouter une nouvelle Habilitation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/providers/habilitations.php" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" name="add_habilitation" value="1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                                <div class="mb-3">
                                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">-- Choisir un type --</option>
                                        <option value="diplome">Diplôme</option>
                                        <option value="certification">Certification</option>
                                        <option value="agrement">Agrément</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="nom_document" class="form-label">Nom du document/diplôme <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom_document" name="nom_document" required maxlength="255">
                                </div>

                                <div class="mb-3">
                                    <label for="organisme_emission" class="form-label">Organisme Émetteur</label>
                                    <input type="text" class="form-control" id="organisme_emission" name="organisme_emission" maxlength="150">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date_obtention" class="form-label">Date d'obtention</label>
                                        <input type="date" class="form-control" id="date_obtention" name="date_obtention">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_expiration" class="form-label">Date d'expiration (si applicable)</label>
                                        <input type="date" class="form-control" id="date_expiration" name="date_expiration">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="document_file" class="form-label">Joindre un justificatif (Optionnel)</label>
                                    <input class="form-control" type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <div class="form-text">Formats acceptés: PDF, JPG, PNG, DOC, DOCX. Taille max: 5Mo.</div>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer Habilitation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Fin Modal -->

        </main>
    </div>
</div>

<?php

include __DIR__ . '/../../templates/footer.php';
?>