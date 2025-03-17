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
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// traitement du formulaire de creation/edition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'siret' => $_POST['siret'] ?? '',
        'adresse' => $_POST['adresse'] ?? '',
        'code_postal' => $_POST['code_postal'] ?? '',
        'ville' => $_POST['ville'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'site_web' => $_POST['site_web'] ?? '',
        'taille_entreprise' => $_POST['taille_entreprise'] ?? '',
        'secteur_activite' => $_POST['secteur_activite'] ?? '',
        'date_creation' => $_POST['date_creation'] ?? null
    ];

    // validation des donnees
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom de l'entreprise est obligatoire";
    }
    
    if (empty($errors)) {
if (empty($errors)) {
    $pdo = getDbConnection();
    
    try {
        // cas de mise a jour
        if ($id > 0) {
            $sql = "UPDATE entreprises SET 
                    nom = ?, siret = ?, adresse = ?, code_postal = ?, ville = ?, 
                    telephone = ?, email = ?, site_web = ?, taille_entreprise = ?, 
                    secteur_activite = ?, date_creation = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['siret'], 
                $data['adresse'], 
                $data['code_postal'],
                $data['ville'], 
                $data['telephone'], 
                $data['email'], 
                $data['site_web'],
                $data['taille_entreprise'], 
                $data['secteur_activite'], 
                $data['date_creation'], 
                $id
            ]);
            
            flashMessage("L'entreprise a ete mise a jour avec succes", "success");
        } 
        // cas de creation
        else {
            $sql = "INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, 
                    telephone, email, site_web, taille_entreprise, secteur_activite, date_creation) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['siret'], 
                $data['adresse'], 
                $data['code_postal'],
                $data['ville'], 
                $data['telephone'], 
                $data['email'], 
                $data['site_web'],
                $data['taille_entreprise'], 
                $data['secteur_activite'], 
                $data['date_creation']
            ]);
            
            flashMessage("L'entreprise a ete creee avec succes", "success");
        }
        
        // redirection vers la liste
        header('Location: ' . APP_URL . '/modules/companies/');
        exit;
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        // Log l'erreur pour l'administrateur
        error_log("Erreur DB dans companies/index.php : " . $e->getMessage());
    }
}
}

// traitement de la suppression
if ($action === 'delete' && $id > 0) {
    $pdo = getDbConnection();
    
    // verifie si l'entreprise a des personnes associees
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM personnes WHERE entreprise_id = ?");
    $stmt->execute([$id]);
    $personCount = $stmt->fetchColumn();
    
    // verifie si l'entreprise a des contrats associes
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM contrats WHERE entreprise_id = ?");
    $stmt->execute([$id]);
    $contractCount = $stmt->fetchColumn();
    
    if ($personCount > 0) {
        flashMessage("Impossible de supprimer cette entreprise car elle a des utilisateurs associes", "danger");
    } else if ($contractCount > 0) {
        flashMessage("Impossible de supprimer cette entreprise car elle a des contrats associes", "danger");
    } else {
        $stmt = $pdo->prepare("DELETE FROM entreprises WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage("L'entreprise a ete supprimee avec succes", "success");
    }
    
    header('Location: ' . APP_URL . '/modules/companies/');
    exit;
}

// recuperation des donnees pour l'edition
$company = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM entreprises WHERE id = ?");
    $stmt->execute([$id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        flashMessage("Entreprise non trouvee", "danger");
        header('Location: ' . APP_URL . '/modules/companies/');
        exit;
    }
}

// construit la clause WHERE pour le filtrage
$where = '';
$params = [];

if ($search) {
    $where .= " (nom LIKE ? OR siret LIKE ? OR ville LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// recupere les entreprises paginees
$perPage = 10;
$offset = ($page - 1) * $perPage;

$pdo = getDbConnection();
$countSql = "SELECT COUNT(id) FROM entreprises";
if ($where) {
    $countSql .= " WHERE $where";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCompanies = $countStmt->fetchColumn();
$totalPages = ceil($totalCompanies / $perPage);
$page = max(1, min($page, $totalPages));

$sql = "SELECT * FROM entreprises";
if ($where) {
    $sql .= " WHERE $where";
}
$sql .= " ORDER BY nom ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $perPage;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// inclusion du header
$pageTitle = "Gestion des entreprises";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des entreprises</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="?action=add" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i> Nouvelle entreprise
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
                        <?php echo $action === 'add' ? 'Ajouter une nouvelle entreprise' : 'Modifier l\'entreprise'; ?>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom de l'entreprise*</label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($company['nom']) ? htmlspecialchars($company['nom']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="siret" class="form-label">SIRET</label>
                                    <input type="text" class="form-control" id="siret" name="siret" value="<?php echo isset($company['siret']) ? htmlspecialchars($company['siret']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo isset($company['adresse']) ? htmlspecialchars($company['adresse']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="code_postal" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo isset($company['code_postal']) ? htmlspecialchars($company['code_postal']) : ''; ?>">
                                </div>
                                <div class="col-md-8">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville" value="<?php echo isset($company['ville']) ? htmlspecialchars($company['ville']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="telephone" class="form-label">Telephone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo isset($company['telephone']) ? htmlspecialchars($company['telephone']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($company['email']) ? htmlspecialchars($company['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="site_web" class="form-label">Site web</label>
                                    <input type="url" class="form-control" id="site_web" name="site_web" value="<?php echo isset($company['site_web']) ? htmlspecialchars($company['site_web']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_creation" class="form-label">Date de creation</label>
                                    <input type="date" class="form-control" id="date_creation" name="date_creation" value="<?php echo isset($company['date_creation']) ? htmlspecialchars($company['date_creation']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="taille_entreprise" class="form-label">Taille de l'entreprise</label>
                                    <select class="form-select" id="taille_entreprise" name="taille_entreprise">
                                        <option value="">Selectionnez...</option>
                                        <?php
                                        $sizes = ['1-10', '11-50', '51-200', '201-500', '500+'];
                                        foreach ($sizes as $size) {
                                            $selected = (isset($company['taille_entreprise']) && $company['taille_entreprise'] === $size) ? 'selected' : '';
                                            echo "<option value=\"$size\" $selected>$size</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="secteur_activite" class="form-label">Secteur d'activite</label>
                                    <input type="text" class="form-control" id="secteur_activite" name="secteur_activite" value="<?php echo isset($company['secteur_activite']) ? htmlspecialchars($company['secteur_activite']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    <a href="<?php echo APP_URL; ?>/modules/companies/" class="btn btn-secondary">Annuler</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($action === 'view' && $company): ?>
                <!-- affichage des details d'une entreprise -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Details de l'entreprise</span>
                        <div>
                            <a href="?action=edit&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="?action=delete&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($company['nom']); ?></p>
                                <p><strong>SIRET:</strong> <?php echo htmlspecialchars($company['siret'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Adresse:</strong> <?php echo htmlspecialchars($company['adresse'] ?: 'Non renseignee'); ?></p>
                                <p><strong>Code postal:</strong> <?php echo htmlspecialchars($company['code_postal'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Ville:</strong> <?php echo htmlspecialchars($company['ville'] ?: 'Non renseignee'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Telephone:</strong> <?php echo htmlspecialchars($company['telephone'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($company['email'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Site web:</strong> <?php echo htmlspecialchars($company['site_web'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Taille:</strong> <?php echo htmlspecialchars($company['taille_entreprise'] ?: 'Non renseignee'); ?></p>
                                <p><strong>Secteur d'activite:</strong> <?php echo htmlspecialchars($company['secteur_activite'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Date de creation:</strong> <?php echo htmlspecialchars($company['date_creation'] ?: 'Non renseignee'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- liste des contrats associes -->
                <div class="mt-4">
                    <h3>Contrats associes</h3>
                    <?php
                    $stmt = $pdo->prepare("SELECT c.* FROM contrats c WHERE c.entreprise_id = ? ORDER BY c.date_debut DESC");
                    $stmt->execute([$id]);
                    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if ($contracts): ?>
                        <table class="table table-striped table-hover">
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
                                        <td><?php echo htmlspecialchars($contract['date_debut']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['date_fin'] ?: '-'); ?></td>
                                        <td><?php echo number_format($contract['montant_mensuel'], 2, ',', ' ') . ' €'; ?></td>
                                        <td><?php echo getStatusBadge($contract['statut']); ?></td>
                                        <td>
                                            <a href="<?php echo APP_URL; ?>/modules/contracts/?action=view&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucun contrat associe a cette entreprise
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- liste des personnes associees -->
                <div class="mt-4">
                    <h3>Utilisateurs associes</h3>
                    <?php
                    $stmt = $pdo->prepare("SELECT p.*, r.nom as role_name FROM personnes p LEFT JOIN roles r ON p.role_id = r.id WHERE p.entreprise_id = ? ORDER BY p.nom, p.prenom");
                    $stmt->execute([$id]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if ($users): ?>
                        <table class="table table-striped table-hover">
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
                                            <a href="<?php echo APP_URL; ?>/modules/users/?action=view&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucun utilisateur associe a cette entreprise
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- liste des entreprises -->
                <div class="card mb-4">
                    <div class="card-header">
                        <form method="get" class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="search" name="search" placeholder="Rechercher par nom, SIRET, ville..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Rechercher
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/companies/" class="btn btn-secondary">Reinitialiser</a>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (count($companies) > 0): ?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Ville</th>
                                        <th>Telephone</th>
                                        <th>Email</th>
                                        <th>Taille</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($company['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($company['ville'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($company['telephone'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($company['email'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($company['taille_entreprise'] ?: '-'); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $company['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ?')">
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
                                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>">Precedent</a>
                                        </li>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>">Suivant</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Aucune entreprise trouvee. <a href="?action=add" class="alert-link">Ajouter une nouvelle entreprise</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
include_once '../../templates/footer.php';
?> 