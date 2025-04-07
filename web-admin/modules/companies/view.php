<?php
require_once '../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ADMIN);

$companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($companyId <= 0) {
    flashMessage('Identifiant entreprise invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/');
}

$company = companiesGetDetails($companyId);

if (!$company) {
    flashMessage("Entreprise non trouvee.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/');
}

$pageTitle = "Informations de l'entreprise";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
             <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="edit.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier cette entreprise">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="delete.php?id=<?php echo $company['id']; ?>&csrf_token=<?php echo generateToken(); ?>" 
                       class="btn btn-sm btn-danger btn-delete me-2"
                       data-bs-toggle="tooltip" title="Supprimer cette entreprise">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste des entreprises">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-building me-1"></i> Entreprise
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Nom</small>
                                <strong><?php echo htmlspecialchars($company['nom']); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">SIRET</small>
                                <strong><?php echo htmlspecialchars($company['siret'] ?: '-'); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Adresse</small>
                                <strong><?php echo nl2br(htmlspecialchars($company['adresse'] ?: '-')); ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Code postal</small>
                                <strong><?php echo htmlspecialchars($company['code_postal'] ?: '-'); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Ville</small>
                                <strong><?php echo htmlspecialchars($company['ville'] ?: '-'); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Telephone</small>
                                <strong><?php echo htmlspecialchars($company['telephone'] ?: '-'); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Email</small>
                                <?php if($company['email']): ?>
                                    <strong><a href="mailto:<?php echo htmlspecialchars($company['email']); ?>" title="Envoyer un email"><?php echo htmlspecialchars($company['email']); ?></a></strong>
                                <?php else: ?>
                                    <strong>-</strong>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Site web</small>
                                <?php if($company['site_web']): ?>
                                    <strong><a href="<?php echo htmlspecialchars($company['site_web']); ?>" target="_blank" title="Visiter le site web"><?php echo htmlspecialchars($company['site_web']); ?></a></strong>
                                <?php else: ?>
                                    <strong>-</strong>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Taille</small>
                                <strong><?php echo htmlspecialchars($company['taille_entreprise'] ?: 'Non renseignee'); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Secteur d'activite</small>
                                <strong><?php echo htmlspecialchars($company['secteur_activite'] ?: 'Non renseigne'); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Date de creation</small>
                                <strong><?php echo $company['date_creation'] ? formatDate($company['date_creation'], 'd/m/Y') : 'Non renseignée'; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-contract me-1"></i> Contrats associes
                </div>
                <div class="card-body p-0">
                    <?php $contracts = $company['contracts']; ?>
                    <?php if ($contracts): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 small">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Date debut</th>
                                        <th>Date fin</th>
                                        <th>Montant mensuel</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $contract): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($contract['type_contrat']); ?></td>
                                            <td><?php echo formatDate($contract['date_debut'], 'd/m/Y'); ?></td>
                                            <td><?php echo $contract['date_fin'] ? formatDate($contract['date_fin'], 'd/m/Y') : '-'; ?></td>
                                            <td><?php echo formatMoney((float)$contract['montant_mensuel'], '€'); ?></td>
                                            <td><?php echo getStatusBadge($contract['statut']); ?></td>
                                            <td>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/view.php?id=<?php echo $contract['id']; ?>" 
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir le contrat">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="card-body text-center text-muted fst-italic">
                            Aucun contrat associe a cette entreprise.
                        </div>
                    <?php endif; ?>
                </div>
                 <div class="card-footer text-muted small">
                    Liste des contrats enregistrés pour cette entreprise.
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-users me-1"></i> Utilisateurs associes
                </div>
                 <div class="card-body p-0">
                    <?php $users = $company['users']; ?>
                    <?php if ($users): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 small">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Telephone</th>
                                        <th>Rôle</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['telephone'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                            <td>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir l'utilisateur">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                         <div class="card-body text-center text-muted fst-italic">
                             Aucun utilisateur associe a cette entreprise.
                        </div>
                    <?php endif; ?>
                </div>
                 <div class="card-footer text-muted small">
                    Liste des utilisateurs enregistrés pour cette entreprise.
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
