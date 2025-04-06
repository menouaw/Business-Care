<?php
require_once '../../includes/page_functions/modules/users.php';

requireRole(ROLE_ADMIN);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? (int)$_GET['role'] : 0;
$statut = isset($_GET['statut']) ? trim($_GET['statut']) : '';

$possibleStatuses = USER_STATUSES;
$result = usersGetList($page, 10, $search, $role, 0, $statut);
$roles = usersGetRoles();

$users = $result['users'];
$totalPages = $result['totalPages'];
$totalUsers = $result['totalItems'];
$page = $result['currentPage'];

$pageTitle = "Gestion des utilisateurs";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des utilisateurs</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un utilisateur
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <form action="" method="get" class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher...">
                        </div>
                        <div class="col-md-3">
                            <select name="role" class="form-select form-select-sm">
                                <option value="0">Tous les rôles</option>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo $role == $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="statut" class="form-select form-select-sm">
                                <option value="">Tous les statuts</option>
                                <?php foreach (USER_STATUSES as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $statut == $s ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </form>
                </div>
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
                                    <td><?php echo htmlspecialchars($user['role_name'] ?? ''); ?></td>
                                    <td><?php echo getStatusBadge($user['statut']); ?></td>
                                    <td><?php echo $user['derniere_connexion'] ? formatDate($user['derniere_connexion']) : 'Jamais'; ?></td>
                                    <td class="table-actions">
                                        <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $user['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" 
                                           data-bs-toggle="tooltip" 
                                           title="Supprimer"> 
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>&statut=<?php echo urlencode($statut); ?>">Precedent</a>
                            </li>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>&statut=<?php echo urlencode($statut); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role; ?>&statut=<?php echo urlencode($statut); ?>">Suivant</a>
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