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
$type = isset($_GET['type']) ? $_GET['type'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// traitement du formulaire de creation/edition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'description' => $_POST['description'] ?? '',
        'prix' => $_POST['prix'] ?? '',
        'duree' => $_POST['duree'] ?? null,
        'type' => $_POST['type'] ?? '',
        'categorie' => $_POST['categorie'] ?? null,
        'niveau_difficulte' => $_POST['niveau_difficulte'] ?? null,
        'capacite_max' => $_POST['capacite_max'] ?? null,
        'materiel_necessaire' => $_POST['materiel_necessaire'] ?? null,
        'prerequis' => $_POST['prerequis'] ?? null
    ];

    // validation des donnees
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom du service est obligatoire";
    }
    
    if (empty($data['prix']) || !is_numeric($data['prix'])) {
        $errors[] = "Le prix du service est obligatoire et doit etre un nombre";
    }
    
    if (empty($data['type'])) {
        $errors[] = "Le type de service est obligatoire";
    }
    
    if (empty($errors)) {
        $pdo = getDbConnection();
        
        // cas de mise a jour
        if ($id > 0) {
            $sql = "UPDATE prestations SET 
                    nom = ?, description = ?, prix = ?, duree = ?, type = ?, 
                    categorie = ?, niveau_difficulte = ?, capacite_max = ?, 
                    materiel_necessaire = ?, prerequis = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'],
                $data['description'],
                $data['prix'],
                $data['duree'],
                $data['type'],
                $data['categorie'],
                $data['niveau_difficulte'],
                $data['capacite_max'],
                $data['materiel_necessaire'],
                $data['prerequis'],
                $id
            ]);
            
            flashMessage("Le service a ete mis a jour avec succes", "success");
        } 
        // cas de creation
        else {
            $sql = "INSERT INTO prestations (nom, description, prix, duree, type, 
                   categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'],
                $data['description'],
                $data['prix'],
                $data['duree'],
                $data['type'],
                $data['categorie'],
                $data['niveau_difficulte'],
                $data['capacite_max'],
                $data['materiel_necessaire'],
                $data['prerequis']
            ]);
            
            flashMessage("Le service a ete cree avec succes", "success");
        }
        
        // redirection vers la liste
        header('Location: ' . APP_URL . '/modules/services/');
        exit;
    }
}

// traitement de la suppression
if ($action === 'delete' && $id > 0) {
    $pdo = getDbConnection();
    
    // verifie si le service a des rendez-vous associes
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM rendez_vous WHERE prestation_id = ?");
    $stmt->execute([$id]);
    $appointmentCount = $stmt->fetchColumn();
    
    // verifie si le service a des evaluations associees
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM evaluations WHERE prestation_id = ?");
    $stmt->execute([$id]);
    $evaluationCount = $stmt->fetchColumn();
    
    if ($appointmentCount > 0) {
        flashMessage("Impossible de supprimer ce service car il a des rendez-vous associes", "danger");
    } else if ($evaluationCount > 0) {
        flashMessage("Impossible de supprimer ce service car il a des evaluations associees", "danger");
    } else {
        $stmt = $pdo->prepare("DELETE FROM prestations WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage("Le service a ete supprime avec succes", "success");
    }
    
    header('Location: ' . APP_URL . '/modules/services/');
    exit;
}

// recuperation des donnees pour l'edition
$service = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM prestations WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        flashMessage("Service non trouve", "danger");
        header('Location: ' . APP_URL . '/modules/services/');
        exit;
    }
}

// construit la clause WHERE pour le filtrage
$where = '';
$params = [];

if ($search) {
    $where .= " (nom LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type) {
    if ($where) {
        $where .= " AND type = ?";
    } else {
        $where .= " type = ?";
    }
    $params[] = $type;
}

// recupere les services pagines
$perPage = 10;
$offset = ($page - 1) * $perPage;

$pdo = getDbConnection();
$countSql = "SELECT COUNT(id) FROM prestations";
if ($where) {
    $countSql .= " WHERE $where";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalServices = $countStmt->fetchColumn();
$totalPages = ceil($totalServices / $perPage);
$page = max(1, min($page, $totalPages));

$sql = "SELECT * FROM prestations";
if ($where) {
    $sql .= " WHERE $where";
}
$sql .= " ORDER BY nom ASC LIMIT $offset, $perPage";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// recuperation des types de services pour le filtre
$typesSql = "SELECT DISTINCT type FROM prestations ORDER BY type";
$typesStmt = $pdo->prepare($typesSql);
$typesStmt->execute();
$serviceTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

// inclusion du header
$pageTitle = "Gestion des services";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des services</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="?action=add" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i> Nouveau service
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
                        <?php echo $action === 'add' ? 'Ajouter un nouveau service' : 'Modifier le service'; ?>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom du service*</label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($service['nom']) ? htmlspecialchars($service['nom']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Type de service*</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Selectionnez...</option>
                                        <?php
                                        $types = ['conference', 'webinar', 'atelier', 'consultation', 'evenement', 'autre'];
                                        foreach ($types as $t) {
                                            $selected = (isset($service['type']) && $service['type'] === $t) ? 'selected' : '';
                                            echo "<option value=\"$t\" $selected>" . ucfirst($t) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($service['description']) ? htmlspecialchars($service['description']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="prix" class="form-label">Prix (€)*</label>
                                    <input type="number" class="form-control" id="prix" name="prix" min="0" step="0.01" value="<?php echo isset($service['prix']) ? htmlspecialchars($service['prix']) : ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="duree" class="form-label">Duree (minutes)</label>
                                    <input type="number" class="form-control" id="duree" name="duree" min="0" value="<?php echo isset($service['duree']) ? htmlspecialchars($service['duree']) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="capacite_max" class="form-label">Capacite maximale</label>
                                    <input type="number" class="form-control" id="capacite_max" name="capacite_max" min="1" value="<?php echo isset($service['capacite_max']) ? htmlspecialchars($service['capacite_max']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="categorie" class="form-label">Categorie</label>
                                    <input type="text" class="form-control" id="categorie" name="categorie" value="<?php echo isset($service['categorie']) ? htmlspecialchars($service['categorie']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="niveau_difficulte" class="form-label">Niveau de difficulte</label>
                                    <select class="form-select" id="niveau_difficulte" name="niveau_difficulte">
                                        <option value="">Selectionnez...</option>
                                        <?php
                                        $levels = ['debutant', 'intermediaire', 'avance'];
                                        foreach ($levels as $level) {
                                            $selected = (isset($service['niveau_difficulte']) && $service['niveau_difficulte'] === $level) ? 'selected' : '';
                                            echo "<option value=\"$level\" $selected>" . ucfirst($level) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="materiel_necessaire" class="form-label">Materiel necessaire</label>
                                    <textarea class="form-control" id="materiel_necessaire" name="materiel_necessaire" rows="2"><?php echo isset($service['materiel_necessaire']) ? htmlspecialchars($service['materiel_necessaire']) : ''; ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="prerequis" class="form-label">Prerequis</label>
                                    <textarea class="form-control" id="prerequis" name="prerequis" rows="2"><?php echo isset($service['prerequis']) ? htmlspecialchars($service['prerequis']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    <a href="<?php echo APP_URL; ?>/modules/services/" class="btn btn-secondary">Annuler</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($action === 'view' && $service): ?>
                <!-- affichage des details d'un service -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Details du service</span>
                        <div>
                            <a href="?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="?action=delete&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($service['nom']); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($service['type']); ?></p>
                                <p><strong>Prix:</strong> <?php echo number_format($service['prix'], 2, ',', ' ') . ' €'; ?></p>
                                <p><strong>Duree:</strong> <?php echo $service['duree'] ? htmlspecialchars($service['duree']) . ' minutes' : 'Non specifiee'; ?></p>
                                <p><strong>Capacite maximale:</strong> <?php echo $service['capacite_max'] ? htmlspecialchars($service['capacite_max']) . ' personnes' : 'Non specifiee'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Categorie:</strong> <?php echo htmlspecialchars($service['categorie'] ?: 'Non specifiee'); ?></p>
                                <p><strong>Niveau de difficulte:</strong> <?php echo htmlspecialchars($service['niveau_difficulte'] ?: 'Non specifie'); ?></p>
                                <p><strong>Date de creation:</strong> <?php echo htmlspecialchars($service['created_at']); ?></p>
                                <p><strong>Derniere mise a jour:</strong> <?php echo htmlspecialchars($service['updated_at']); ?></p>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Description</h5>
                                <p><?php echo nl2br(htmlspecialchars($service['description'] ?: 'Aucune description disponible')); ?></p>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Materiel necessaire</h5>
                                <p><?php echo nl2br(htmlspecialchars($service['materiel_necessaire'] ?: 'Aucun materiel specifie')); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Prerequis</h5>
                                <p><?php echo nl2br(htmlspecialchars($service['prerequis'] ?: 'Aucun prerequis specifie')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- liste des rendez-vous associes -->
                <div class="mt-4">
                    <h3>Rendez-vous associes</h3>
                    <?php
                    $stmt = $pdo->prepare("SELECT r.*, p.nom as nom_personne, p.prenom as prenom_personne 
                                          FROM rendez_vous r 
                                          LEFT JOIN personnes p ON r.personne_id = p.id 
                                          WHERE r.prestation_id = ? 
                                          ORDER BY r.date_rdv DESC");
                    $stmt->execute([$id]);
                    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if ($appointments): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Duree</th>
                                    <th>Lieu</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($appointment['date_rdv'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['prenom_personne'] . ' ' . $appointment['nom_personne']); ?></td>
                                        <td><?php echo $appointment['duree'] . ' minutes'; ?></td>
                                        <td><?php echo htmlspecialchars($appointment['lieu'] ?: 'Non specifie'); ?></td>
                                        <td><?php echo getStatusBadge($appointment['statut']); ?></td>
                                        <td>
                                            <a href="?action=view&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucun rendez-vous associe a ce service
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- liste des evaluations associees -->
                <div class="mt-4">
                    <h3>Évaluations reçues</h3>
                    <?php
                    $stmt = $pdo->prepare("SELECT e.*, p.nom as nom_personne, p.prenom as prenom_personne 
                                          FROM evaluations e 
                                          LEFT JOIN personnes p ON e.personne_id = p.id 
                                          WHERE e.prestation_id = ? 
                                          ORDER BY e.date_evaluation DESC");
                    $stmt->execute([$id]);
                    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if ($evaluations): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <?php
                                $totalNotes = 0;
                                foreach ($evaluations as $evaluation) {
                                    $totalNotes += $evaluation['note'];
                                }
                                $moyenne = count($evaluations) > 0 ? round($totalNotes / count($evaluations), 1) : 0;
                                ?>
                                <h5>Note moyenne: <?php echo $moyenne; ?>/5 (<?php echo count($evaluations); ?> avis)</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <h6><?php echo htmlspecialchars($evaluation['prenom_personne'] . ' ' . $evaluation['nom_personne']); ?></h6>
                                            <div>
                                                <strong>Note:</strong> <?php echo htmlspecialchars($evaluation['note']); ?>/5 - 
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($evaluation['date_evaluation'])); ?></small>
                                            </div>
                                        </div>
                                        <p><?php echo nl2br(htmlspecialchars($evaluation['commentaire'] ?: 'Pas de commentaire')); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucune evaluation pour ce service
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- liste des services -->
                <div class="card mb-4">
                    <div class="card-header">
                        <form method="get" class="row g-3">
                            <div class="col-md-5">
                                <input type="text" class="form-control" id="search" name="search" placeholder="Rechercher par nom, description..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="type" name="type">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($serviceTypes as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($t)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Rechercher
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/services/" class="btn btn-secondary">Reinitialiser</a>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (count($services) > 0): ?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Prix</th>
                                        <th>Duree</th>
                                        <th>Categorie</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($service['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($service['type']); ?></td>
                                            <td><?php echo number_format($service['prix'], 2, ',', ' ') . ' €'; ?></td>
                                            <td><?php echo $service['duree'] ? htmlspecialchars($service['duree']) . ' min' : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($service['categorie'] ?: '-'); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?')">
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
                                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>">Precedent</a>
                                        </li>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>">Suivant</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Aucun service trouve. <a href="?action=add" class="alert-link">Ajouter un nouveau service</a>
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