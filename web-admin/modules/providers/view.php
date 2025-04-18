<?php
require_once '../../includes/page_functions/modules/providers.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tab = $_GET['tab'] ?? 'details'; 

if ($id <= 0) {
    flashMessage('Identifiant de prestataire invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php');
}

$provider = getProviderDetails($id);

if (!$provider) {
    flashMessage("Prestataire non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php');
}


$habilitations = getProviderHabilitations($id);
$assignedPrestations = getProviderAssignedPrestations($id);
$evaluationData = getProviderEvaluations($id);
$appointments = getProviderAppointments($id, [], ['page' => 1, 'perPage' => 10]); 



$pageTitle = "Informations du prestataire";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/edit.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier ce prestataire">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/delete.php?id=<?php echo $provider['id']; ?>&csrf_token=<?php echo generateToken(); ?>" 
                       class="btn btn-sm btn-danger me-2 btn-delete"
                       data-bs-toggle="tooltip" title="Supprimer ce prestataire"
                       data-provider-name="<?php echo htmlspecialchars($provider['prenom'] . ' ' . $provider['nom']); ?>">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste des prestataires">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <nav>
                <div class="nav nav-tabs" id="providerTab" role="tablist">
                    <button class="nav-link <?php echo ($tab === 'details') ? 'active' : ''; ?>" id="nav-details-tab" data-bs-toggle="tab" data-bs-target="#nav-details" type="button" role="tab" aria-controls="nav-details" aria-selected="<?php echo ($tab === 'details') ? 'true' : 'false'; ?>">Détails</button>
                    <button class="nav-link <?php echo ($tab === 'habilitations') ? 'active' : ''; ?>" id="nav-habilitations-tab" data-bs-toggle="tab" data-bs-target="#nav-habilitations" type="button" role="tab" aria-controls="nav-habilitations" aria-selected="<?php echo ($tab === 'habilitations') ? 'true' : 'false'; ?>">Habilitations</button>
                    <button class="nav-link <?php echo ($tab === 'prestations') ? 'active' : ''; ?>" id="nav-prestations-tab" data-bs-toggle="tab" data-bs-target="#nav-prestations" type="button" role="tab" aria-controls="nav-prestations" aria-selected="<?php echo ($tab === 'prestations') ? 'true' : 'false'; ?>">Prestations</button>
                    <button class="nav-link <?php echo ($tab === 'evaluations') ? 'active' : ''; ?>" id="nav-evaluations-tab" data-bs-toggle="tab" data-bs-target="#nav-evaluations" type="button" role="tab" aria-controls="nav-evaluations" aria-selected="<?php echo ($tab === 'evaluations') ? 'true' : 'false'; ?>">Évaluations</button>
                    <button class="nav-link <?php echo ($tab === 'calendar') ? 'active' : ''; ?>" id="nav-calendar-tab" data-bs-toggle="tab" data-bs-target="#nav-calendar" type="button" role="tab" aria-controls="nav-calendar" aria-selected="<?php echo ($tab === 'calendar') ? 'true' : 'false'; ?>">Calendrier</button>
                    <button class="nav-link <?php echo ($tab === 'appointments') ? 'active' : ''; ?>" id="nav-appointments-tab" data-bs-toggle="tab" data-bs-target="#nav-appointments" type="button" role="tab" aria-controls="nav-appointments" aria-selected="<?php echo ($tab === 'appointments') ? 'true' : 'false'; ?>">Rendez-vous</button>
                </div>
            </nav>
            
            <div class="tab-content py-3" id="nav-tabContent">
                
                
                <div class="tab-pane fade <?php echo ($tab === 'details') ? 'show active' : ''; ?>" id="nav-details" role="tabpanel" aria-labelledby="nav-details-tab">
                     <div class="card mb-4">
                        <div class="card-header">
                           <i class="fas fa-info-circle me-1"></i> Détails
                        </div>
                        <div class="card-body">
                             <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Nom</small>
                                        <strong><?php echo htmlspecialchars($provider['prenom'] . ' ' . $provider['nom']); ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Email</small>
                                        <strong><a href="mailto:<?php echo htmlspecialchars($provider['email']); ?>"><?php echo htmlspecialchars($provider['email']); ?></a></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Téléphone</small>
                                        <strong><?php echo htmlspecialchars($provider['telephone'] ?: '-'); ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Date de naissance</small>
                                        <strong><?php echo $provider['date_naissance'] ? formatDate($provider['date_naissance'], 'd/m/Y') : '-'; ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Statut</small>
                                        <?php echo getStatusBadge($provider['statut']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Genre</small>
                                        <strong><?php echo htmlspecialchars($provider['genre'] ?: 'Non spécifié'); ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Membre depuis</small>
                                        <strong><?php echo formatDate($provider['created_at']); ?></strong>
                                    </div>
                                     <div class="mb-3">
                                        <small class="text-muted d-block">Dernière connexion</small>
                                        <strong><?php echo $provider['derniere_connexion'] ? formatDate($provider['derniere_connexion']) : 'Jamais'; ?></strong>
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="tab-pane fade <?php echo ($tab === 'habilitations') ? 'show active' : ''; ?>" id="nav-habilitations" role="tabpanel" aria-labelledby="nav-habilitations-tab">
                     <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-certificate me-1"></i> Habilitations
                            <button type="button" class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#addHabilitationModal">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($habilitations)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Document</th>
                                            <th>Organisme</th>
                                            <th>Expiration</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($habilitations as $hab): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(ucfirst($hab['type'])); ?></td>
                                            <td>
                                                <?php if ($hab['document_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($hab['document_url']); ?>" target="_blank" title="Voir le document"><?php echo htmlspecialchars($hab['nom_document'] ?: 'Document'); ?> <i class="fas fa-external-link-alt fa-xs"></i></a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($hab['nom_document'] ?: '-'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($hab['organisme_emission'] ?: '-'); ?></td>
                                            <td><?php echo $hab['date_expiration'] ? formatDate($hab['date_expiration'], 'd/m/Y') : 'N/A'; ?></td>
                                            <td><?php echo getStatusBadge($hab['statut']); ?></td>
                                            <td class="table-actions d-flex flex-nowrap">
                                                <?php if ($hab['statut'] === HABILITATION_STATUS_PENDING): ?>
                                                    <form action="<?php echo WEBADMIN_URL; ?>/modules/providers/actions.php" method="POST" class="d-inline me-1"> 
                                                        <input type="hidden" name="action" value="verify_habilitation">
                                                        <input type="hidden" name="id" value="<?php echo $hab['id']; ?>">
                                                        <input type="hidden" name="provider_id" value="<?php echo $id; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Valider"><i class="fas fa-check"></i></button>
                                                    </form>
                                                    <form action="<?php echo WEBADMIN_URL; ?>/modules/providers/actions.php" method="POST" class="d-inline me-1"> 
                                                        <input type="hidden" name="action" value="reject_habilitation">
                                                        <input type="hidden" name="id" value="<?php echo $hab['id']; ?>">
                                                        <input type="hidden" name="provider_id" value="<?php echo $id; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Rejeter"><i class="fas fa-times"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/edit_habilitation.php?id=<?php echo $hab['id']; ?>&provider_id=<?php echo $id; ?>" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" title="Modifier"><i class="fas fa-edit"></i></a>
                                                <form action="<?php echo WEBADMIN_URL; ?>/modules/providers/actions.php" method="POST" class="d-inline btn-delete-form">
                                                    <input type="hidden" name="action" value="delete_habilitation">
                                                    <input type="hidden" name="id" value="<?php echo $hab['id']; ?>">
                                                    <input type="hidden" name="provider_id" value="<?php echo $id; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette habilitation ?');"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-center text-muted">Aucune habilitation enregistrée pour ce prestataire.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                
                <div class="tab-pane fade <?php echo ($tab === 'prestations') ? 'show active' : ''; ?>" id="nav-prestations" role="tabpanel" aria-labelledby="nav-prestations-tab">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-concierge-bell me-1"></i> Prestations
                             <button type="button" class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#assignPrestationModal">
                                <i class="fas fa-plus"></i> Assigner
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($assignedPrestations)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Type</th>
                                            <th>Prix</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignedPrestations as $prest): ?>
                                        <tr>
                                            <td><a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $prest['id']; ?>"><?php echo htmlspecialchars($prest['nom']); ?></a></td>
                                            <td><?php echo htmlspecialchars(ucfirst($prest['type'])); ?></td>
                                            <td><?php echo formatMoney($prest['prix']); ?></td>
                                            <td class="table-actions">
                                                <form action="<?php echo WEBADMIN_URL; ?>/modules/providers/actions.php" method="POST" class="d-inline btn-delete-form">
                                                    <input type="hidden" name="action" value="remove_prestation">
                                                    <input type="hidden" name="provider_id" value="<?php echo $id; ?>">
                                                    <input type="hidden" name="prestation_id" value="<?php echo $prest['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Retirer l'assignation" onclick="return confirm('Êtes-vous sûr de vouloir retirer cette prestation du prestataire ?');">
                                                         <i class="fas fa-unlink"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                             <?php else: ?>
                                <p class="text-center text-muted">Aucune prestation assignée à ce prestataire.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                
                <div class="tab-pane fade <?php echo ($tab === 'evaluations') ? 'show active' : ''; ?>" id="nav-evaluations" role="tabpanel" aria-labelledby="nav-evaluations-tab">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-star me-1"></i> Évaluations
                        </div>
                        <div class="card-body">
                             <div class="row mb-3 text-center">
                                <div class="col-md-6">
                                    <div class="stat-box border rounded p-3">
                                        <div class="fs-4 fw-bold"><?php echo $evaluationData['average_score'] ?? 'N/A'; ?> / 5</div>
                                        <div class="text-muted">Note</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                     <div class="stat-box border rounded p-3">
                                        <div class="fs-4 fw-bold"><?php echo $evaluationData['total_evaluations']; ?></div>
                                        <div class="text-muted">Total</div>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mt-4">Dernières évaluations reçues</h5>
                             <?php if (!empty($evaluationData['evaluations'])): ?>
                                <ul class="list-group">
                                    <?php foreach ($evaluationData['evaluations'] as $eval): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><strong><?php echo htmlspecialchars($eval['prestation_nom']); ?></strong> - <?php echo htmlspecialchars($eval['client_nom']); ?></h6>
                                                <small><?php echo formatDate($eval['date_evaluation'], 'd/m/Y'); ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo ($i <= $eval['note']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </p>
                                            <small><?php echo nl2br(htmlspecialchars($eval['commentaire'] ?: 'Pas de commentaire.')); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-center text-muted">Aucune évaluation récente trouvée pour les prestations de ce prestataire.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                
                <div class="tab-pane fade <?php echo ($tab === 'calendar') ? 'show active' : ''; ?>" id="nav-calendar" role="tabpanel" aria-labelledby="nav-calendar-tab">
                     <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-calendar-alt me-1"></i> Calendrier
                            
                        </div>
                        <div class="card-body">
                           
                           <div id="provider-calendar"></div>
                           <p class="text-center text-muted mt-3">TODO: Calendrier</p>
                           
                        </div>
                    </div>
                </div>

                 
                <div class="tab-pane fade <?php echo ($tab === 'appointments') ? 'show active' : ''; ?>" id="nav-appointments" role="tabpanel" aria-labelledby="nav-appointments-tab">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-calendar-check me-1"></i> Rendez-vous
                        </div>
                        <div class="card-body">
                             <?php if (!empty($appointments['items'])): ?>
                             <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Prestation</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments['items'] as $appt): ?>
                                        <tr>
                                            <td><?php echo formatDate($appt['date_rdv'], 'd/m/Y H:i'); ?></td>
                                            <td>
                                                <?php if ($appt['personne_id']): ?>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $appt['personne_id']; ?>">
                                                    <?php echo htmlspecialchars($appt['nom_client']); ?>
                                                </a>
                                                <?php else: echo '-'; endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($appt['prestation_id']): ?>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $appt['prestation_id']; ?>">
                                                    <?php echo htmlspecialchars($appt['nom_prestation']); ?>
                                                </a>
                                                <?php else: echo '-'; endif; ?>
                                            </td>
                                            <td><?php echo getStatusBadge($appt['statut']); ?></td>
                                            <td>
                                                 <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir RDV">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                             </div>
                            <?php else: ?>
                                <p class="text-center text-muted">Aucun rendez-vous récent trouvé pour ce prestataire.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div> 

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>



<div class="modal fade" id="addHabilitationModal" tabindex="-1" aria-labelledby="addHabilitationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addHabilitationModalLabel">Ajouter une Habilitation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addHabilitationForm" method="post" action="<?php echo WEBADMIN_URL; ?>/modules/providers/actions.php"> 
        <input type="hidden" name="action" value="add_habilitation">
        <input type="hidden" name="provider_id" value="<?php echo $id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label for="add_hab_type" class="form-label">Type</label>
            <select class="form-select" id="add_hab_type" name="type" required>
              <option value="" selected disabled>Sélectionner...</option>
              <option value="diplome">Diplôme</option>
              <option value="certification">Certification</option>
              <option value="agrement">Agrément</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="add_hab_nom_document" class="form-label">Nom du Document/Diplôme</label>
            <input type="text" class="form-control" id="add_hab_nom_document" name="nom_document">
          </div>
          <div class="mb-3">
            <label for="add_hab_organisme" class="form-label">Organisme Émetteur</label>
            <input type="text" class="form-control" id="add_hab_organisme" name="organisme_emission">
          </div>
          <div class="row">
              <div class="col-md-6 mb-3">
                <label for="add_hab_date_obtention" class="form-label">Date Obtention</label>
                <input type="date" class="form-control" id="add_hab_date_obtention" name="date_obtention">
              </div>
              <div class="col-md-6 mb-3">
                <label for="add_hab_date_expiration" class="form-label">Date Expiration (si applicable)</label>
                <input type="date" class="form-control" id="add_hab_date_expiration" name="date_expiration">
              </div>
          </div>
           <div class="mb-3">
            <label for="add_hab_document_url" class="form-label">URL Document (Scan)</label>
            <input type="url" class="form-control" id="add_hab_document_url" name="document_url">
          </div>
           <div class="mb-3">
            <label for="add_hab_notes" class="form-label">Notes</label>
            <textarea class="form-control" id="add_hab_notes" name="notes" rows="2"></textarea>
          </div>
           <div class="mb-3">
             <label for="add_hab_statut" class="form-label">Statut Initial</label>
             <select class="form-select" id="add_hab_statut" name="statut">
                <option value="<?php echo HABILITATION_STATUS_PENDING; ?>" selected>En attente de validation</option>
                <option value="<?php echo HABILITATION_STATUS_VERIFIED; ?>">Vérifiée</option>
             </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal fade" id="assignPrestationModal" tabindex="-1" aria-labelledby="assignPrestationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignPrestationModalLabel">Assigner une Prestation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
        <form id="assignPrestationForm" method="post" action="<?php echo WEBADMIN_URL; ?>/modules/providers/actions.php"> 
            <input type="hidden" name="action" value="assign_prestation">
            <input type="hidden" name="provider_id" value="<?php echo $id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
            <div class="modal-body">
                 <div class="mb-3">
                    <label for="assign_prestation_id" class="form-label">Sélectionner la Prestation</label>
                    <select class="form-select" id="assign_prestation_id" name="prestation_id" required>
                        <option value="" selected disabled>Choisir...</option>
                        <?php 
                           
                           $allPrestations = fetchAll(TABLE_PRESTATIONS, '', 'nom ASC');
                           $assignedIds = array_column($assignedPrestations, 'id');
                           foreach ($allPrestations as $p): 
                                if (!in_array($p['id'], $assignedIds)): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['nom']); ?> (<?php echo formatMoney($p['prix']); ?>)
                                </option>
                        <?php   endif;
                            endforeach; 
                        ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-primary">Assigner</button>
            </div>
        </form>
    </div>
  </div>
</div>