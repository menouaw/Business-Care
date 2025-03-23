<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// verifie si l'utilisateur est connecte
requireAuthentication();

// recupere les param de la requete
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$entreprise = isset($_GET['entreprise']) ? (int)$_GET['entreprise'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// traitement du formulaire de creation/edition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'entreprise_id' => $_POST['entreprise_id'] ?? '',
        'date_debut' => $_POST['date_debut'] ?? '',
        'date_fin' => $_POST['date_fin'] ?? null,
        'montant_mensuel' => $_POST['montant_mensuel'] ?? null,
        'nombre_salaries' => $_POST['nombre_salaries'] ?? null,
        'type_contrat' => $_POST['type_contrat'] ?? '',
        'statut' => $_POST['statut'] ?? 'actif',
        'conditions_particulieres' => $_POST['conditions_particulieres'] ?? null
    ];

    // validation des donnees
    $errors = [];
    
    if (empty($data['entreprise_id'])) {
        $errors[] = "L'entreprise est obligatoire";
    }
    
    if (empty($data['date_debut'])) {
        $errors[] = "La date de debut est obligatoire";
    }
    
    if (empty($data['type_contrat'])) {
        $errors[] = "Le type de contrat est obligatoire";
    }
    
    if (!empty($data['date_fin']) && strtotime($data['date_fin']) < strtotime($data['date_debut'])) {
        $errors[] = "La date de fin ne peut pas etre anterieure a la date de debut";
    }
    
    if (empty($errors)) {
        $pdo = getDbConnection();
        
        // verification que l'entreprise existe
        $stmt = $pdo->prepare("SELECT id FROM entreprises WHERE id = ?");
        $stmt->execute([$data['entreprise_id']]);
        if ($stmt->rowCount() === 0) {
            $errors[] = "L'entreprise selectionnee n'existe pas";
        } else {
            // cas de mise a jour
            if ($id > 0) {
                $sql = "UPDATE contrats SET 
                        entreprise_id = ?, date_debut = ?, date_fin = ?, montant_mensuel = ?, 
                        nombre_salaries = ?, type_contrat = ?, statut = ?, conditions_particulieres = ? 
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['entreprise_id'],
                    $data['date_debut'],
                    $data['date_fin'],
                    $data['montant_mensuel'],
                    $data['nombre_salaries'],
                    $data['type_contrat'],
                    $data['statut'],
                    $data['conditions_particulieres'],
                    $id
                ]);
                
                flashMessage("Le contrat a ete mis a jour avec succes", "success");
            } 
            // cas de creation
            else {
                $sql = "INSERT INTO contrats (entreprise_id, date_debut, date_fin, montant_mensuel, 
                        nombre_salaries, type_contrat, statut, conditions_particulieres) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['entreprise_id'],
                    $data['date_debut'],
                    $data['date_fin'],
                    $data['montant_mensuel'],
                    $data['nombre_salaries'],
                    $data['type_contrat'],
                    $data['statut'],
                    $data['conditions_particulieres']
                ]);
                
                flashMessage("Le contrat a ete cree avec succes", "success");
            }
            
            // redirection vers la liste
            header('Location: ' . APP_URL . '/modules/contracts/');
            exit;
        }
    }
}

// traitement de la suppression
if ($action === 'delete' && $id > 0) {
    $pdo = getDbConnection();
    
    // verification que le contrat existe
    $stmt = $pdo->prepare("SELECT id FROM contrats WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        flashMessage("Contrat non trouve", "danger");
    } else {
        $stmt = $pdo->prepare("DELETE FROM contrats WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage("Le contrat a ete supprime avec succes", "success");
    }
    
    header('Location: ' . APP_URL . '/modules/contracts/');
    exit;
}

// recuperation des donnees pour l'edition
$contract = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT c.*, e.nom as nom_entreprise 
                          FROM contrats c 
                          LEFT JOIN entreprises e ON c.entreprise_id = e.id 
                          WHERE c.id = ?");
    $stmt->execute([$id]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contract) {
        flashMessage("Contrat non trouve", "danger");
        header('Location: ' . APP_URL . '/modules/contracts/');
        exit;
    }
}

// construit la clause WHERE pour le filtrage
$where = '';
$params = [];

if ($entreprise > 0) {
    $where .= " c.entreprise_id = ?";
    $params[] = $entreprise;
}

if ($statut) {
    if ($where) {
        $where .= " AND c.statut = ?";
    } else {
        $where .= " c.statut = ?";
    }
    $params[] = $statut;
}

if ($search) {
    if ($where) {
        $where .= " AND (e.nom LIKE ? OR c.type_contrat LIKE ?)";
    } else {
        $where .= " (e.nom LIKE ? OR c.type_contrat LIKE ?)";
    }
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// recupere les contrats pagines
$perPage = 10;
$offset = ($page - 1) * $perPage;

$pdo = getDbConnection();
$countSql = "SELECT COUNT(c.id) FROM contrats c LEFT JOIN entreprises e ON c.entreprise_id = e.id";
if ($where) {
    $countSql .= " WHERE $where";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalContracts = $countStmt->fetchColumn();
$totalPages = ceil($totalContracts / $perPage);
$page = max(1, min($page, $totalPages));

$sql = "SELECT c.*, e.nom as nom_entreprise 
        FROM contrats c 
        LEFT JOIN entreprises e ON c.entreprise_id = e.id";
if ($where) {
    $sql .= " WHERE $where";
}
$sql .= " ORDER BY c.date_debut DESC LIMIT $offset, $perPage";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// recuperation des entreprises pour le formulaire et le filtre
$entreprisesSql = "SELECT id, nom FROM entreprises ORDER BY nom";
$entreprisesStmt = $pdo->prepare($entreprisesSql);
$entreprisesStmt->execute();
$entreprises = $entreprisesStmt->fetchAll(PDO::FETCH_ASSOC);

// inclusion du header
$pageTitle = "Gestion des contrats";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
<?php include_once '../../templates/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérification du jeton CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            flashMessage("Erreur de sécurité, veuillez réessayer", "danger");
            header('Location: ' . APP_URL . '/modules/contracts/');
            exit;
        }
        
        $data = [
            // ...
        ];
    }
    ?>
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gestion des contrats</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="?action=add" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Nouveau contrat
            </a>
        </div>
    </div>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php echo displayFlashMessages(); ?>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- formulaire d'ajout/edition -->
        <div class="card">
            <div class="card-header">
                <?php echo $action === 'add' ? 'Ajouter un nouveau contrat' : 'Modifier le contrat'; ?>
            </div>
            <div class="card-body">
                <form method="post">
+                   <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="entreprise_id" class="form-label">Entreprise*</label>
                            <select class="form-select" id="entreprise_id" name="entreprise_id" required>
                                <option value="">Selectionnez une entreprise...</option>
                                <?php foreach ($entreprises as $e): ?>
                                    <option value="<?php echo $e['id']; ?>" <?php echo (isset($contract['entreprise_id']) && $contract['entreprise_id'] == $e['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($e['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="type_contrat" class="form-label">Type de contrat*</label>
                            <select class="form-select" id="type_contrat" name="type_contrat" required>
                                <option value="">Selectionnez un type...</option>
                                <?php
                                $types = ['standard', 'premium', 'entreprise'];
                                foreach ($types as $t) {
                                    $selected = (isset($contract['type_contrat']) && $contract['type_contrat'] === $t) ? 'selected' : '';
                                    echo "<option value=\"$t\" $selected>" . ucfirst($t) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de debut*</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo isset($contract['date_debut']) ? htmlspecialchars($contract['date_debut']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo isset($contract['date_fin']) ? htmlspecialchars($contract['date_fin']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="montant_mensuel" class="form-label">Montant mensuel (€)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="montant_mensuel" name="montant_mensuel" value="<?php echo isset($contract['montant_mensuel']) ? htmlspecialchars($contract['montant_mensuel']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="nombre_salaries" class="form-label">Nombre de salaries</label>
                            <input type="number" min="1" class="form-control" id="nombre_salaries" name="nombre_salaries" value="<?php echo isset($contract['nombre_salaries']) ? htmlspecialchars($contract['nombre_salaries']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <?php
                                $statuts = ['actif', 'expire', 'resilie', 'en_attente'];
                                foreach ($statuts as $s) {
                                    $selected = (isset($contract['statut']) && $contract['statut'] === $s) ? 'selected' : '';
                                    echo "<option value=\"$s\" $selected>" . ucfirst($s) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="conditions_particulieres" class="form-label">Conditions particulieres</label>
                            <textarea class="form-control" id="conditions_particulieres" name="conditions_particulieres" rows="3"><?php echo isset($contract['conditions_particulieres']) ? htmlspecialchars($contract['conditions_particulieres']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="<?php echo APP_URL; ?>/modules/contracts/" class="btn btn-secondary">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>
                </div>
            <?php elseif ($action === 'view' && $contract): ?>
                <!-- affichage des details d'un contrat -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Details du contrat</span>
                        <div>
                            <a href="?action=edit&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="?action=delete&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce contrat ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Entreprise:</strong> <a href="<?php echo APP_URL; ?>/modules/companies/?action=view&id=<?php echo $contract['entreprise_id']; ?>"><?php echo htmlspecialchars($contract['nom_entreprise']); ?></a></p>
                                <p><strong>Type de contrat:</strong> <?php echo htmlspecialchars(ucfirst($contract['type_contrat'])); ?></p>
                                <p><strong>Date de debut:</strong> <?php echo date('d/m/Y', strtotime($contract['date_debut'])); ?></p>
                                <p><strong>Date de fin:</strong> <?php echo $contract['date_fin'] ? date('d/m/Y', strtotime($contract['date_fin'])) : 'Indeterminee'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Montant mensuel:</strong> <?php echo $contract['montant_mensuel'] ? number_format($contract['montant_mensuel'], 2, ',', ' ') . ' €' : 'Non specifie'; ?></p>
                                <p><strong>Nombre de salaries:</strong> <?php echo $contract['nombre_salaries'] ?: 'Non specifie'; ?></p>
                                <p><strong>Statut:</strong> <?php echo getStatusBadge($contract['statut']); ?></p>
                                <p><strong>Date de creation:</strong> <?php echo date('d/m/Y', strtotime($contract['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if ($contract['conditions_particulieres']): ?>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h5>Conditions particulieres</h5>
                                    <p><?php echo nl2br(htmlspecialchars($contract['conditions_particulieres'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- calcul de la duree et des stats -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Statistiques du contrat</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php
                                            $dateDebut = new DateTime($contract['date_debut']);
                                            $dateFin = $contract['date_fin'] ? new DateTime($contract['date_fin']) : new DateTime();
                                            $duree = $dateDebut->diff($dateFin);
                                            
                                            $montantTotal = 0;
                                            if ($contract['montant_mensuel']) {
                                                $mois = ($duree->y * 12) + $duree->m;
                                                $montantTotal = $mois * $contract['montant_mensuel'];
                                            }
                                            ?>
                                            <div class="col-md-4 text-center">
                                                <h6>Duree</h6>
                                                <p class="h4"><?php echo $duree->y > 0 ? $duree->y . ' an(s) ' : ''; ?><?php echo $duree->m > 0 ? $duree->m . ' mois' : ''; ?></p>
                                            </div>
                                            <?php if ($contract['montant_mensuel']): ?>
                                                <div class="col-md-4 text-center">
                                                    <h6>Montant mensuel</h6>
                                                    <p class="h4"><?php echo number_format($contract['montant_mensuel'], 2, ',', ' '); ?> €</p>
                                                </div>
                                                <div class="col-md-4 text-center">
                                                    <h6>Montant total</h6>
                                                    <p class="h4"><?php echo number_format($montantTotal, 2, ',', ' '); ?> €</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- liste des contrats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <form method="get" class="row g-3">
                            <div class="col-md-2">
                                <input type="text" class="form-control" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="entreprise" name="entreprise">
                                    <option value="">Toutes les entreprises</option>
                                    <?php foreach ($entreprises as $e): ?>
                                        <option value="<?php echo $e['id']; ?>" <?php echo $entreprise == $e['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($e['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statut" name="statut">
                                    <option value="">Tous les statuts</option>
                                    <?php
                                    $statutsList = ['actif', 'expire', 'resilie', 'en_attente'];
                                    foreach ($statutsList as $s) {
                                        $selected = $statut === $s ? 'selected' : '';
                                        echo "<option value=\"$s\" $selected>" . ucfirst($s) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Rechercher
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/contracts/" class="btn btn-secondary">Reinitialiser</a>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (count($contracts) > 0): ?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Entreprise</th>
                                        <th>Type</th>
                                        <th>Date de debut</th>
                                        <th>Date de fin</th>
                                        <th>Montant mensuel</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $contract): ?>
                                        <tr>
                                            <td><a href="<?php echo APP_URL; ?>/modules/companies/?action=view&id=<?php echo $contract['entreprise_id']; ?>"><?php echo htmlspecialchars($contract['nom_entreprise']); ?></a></td>
                                            <td><?php echo htmlspecialchars(ucfirst($contract['type_contrat'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($contract['date_debut'])); ?></td>
                                            <td><?php echo $contract['date_fin'] ? date('d/m/Y', strtotime($contract['date_fin'])) : '-'; ?></td>
                                            <td><?php echo $contract['montant_mensuel'] ? number_format($contract['montant_mensuel'], 2, ',', ' ') . ' €' : '-'; ?></td>
                                            <td><?php echo getStatusBadge($contract['statut']); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce contrat ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <!-- pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&entreprise=<?php echo $entreprise; ?>">Precedent</a>
                                        </li>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&entreprise=<?php echo $entreprise; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&entreprise=<?php echo $entreprise; ?>">Suivant</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Aucun contrat trouve. <a href="?action=add" class="alert-link">Ajouter un nouveau contrat</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
?> 