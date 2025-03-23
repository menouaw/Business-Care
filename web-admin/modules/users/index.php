<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// verifie si l'utilsateur est connecte
requireAuthentication();
if (!hasPermission('admin')) {
    flashMessage('Vous n\'avez pas les permissions pour acceder a cette page.', 'danger');
    header('Location: ' . APP_URL);
    exit;
}

// recupere les param de la requete
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? (int)$_GET['role'] : 0;

$where = [];
$params = [];

if ($search) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role) {
    $where[] = "role_id = ?";
    $params[] = $role;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// recupere les utilisateurs pagines
$perPage = 10;
$offset = ($page - 1) * $perPage;

$pdo = getDbConnection();
$countSql = "SELECT COUNT(id) FROM personnes p $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);
$page = max(1, min($page, $totalPages));

$sql = "SELECT p.*, r.nom as role_name 
        FROM personnes p 
        LEFT JOIN roles r ON p.role_id = r.id
        $whereClause
        ORDER BY p.nom, p.prenom 
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// recupere tous les rôles pour le dropdown de filtrage
$rolesStmt = $pdo->query("SELECT id, nom FROM roles ORDER BY nom");
$roles = $rolesStmt->fetchAll();

// definit le titre de la page et inclut l'en-tete
$pageTitle = "Gestion des utilisateurs";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des utilisateurs</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un utilisateur
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form action="" method="get" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher des utilisateurs...">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="role" class="form-select">
                                <option value="0">Tous les rôles</option>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo $role == $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="index.php" class="btn btn-outline-secondary w-100">Reinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Derniere connexion</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun utilisateur trouve.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <?php if ($user['photo_url']): ?>
                                        <img src="<?php echo htmlspecialchars($user['photo_url']); ?>" alt="Profil" class="rounded-circle me-2" width="30" height="30">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    <td><?php echo getStatusBadge($user['statut']); ?></td>
                                    <td><?php echo $user['derniere_connexion'] ? formatDate($user['derniere_connexion']) : 'Never'; ?></td>
                                    <td class="table-actions">
                                        <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>">Precedent</a>
                            </li>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div> 