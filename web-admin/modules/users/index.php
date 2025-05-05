<?php
require_once '../../includes/page_functions/modules/users.php';

// requireRole(ROLE_ADMIN)

$queryData = getQueryData(); 
$page = $queryData['page'] ?? 1; 
$search = $queryData['search'] ?? ''; 
$role = $queryData['role'] ?? 0; 
$statut = $queryData['statut'] ?? ''; 

$result = usersGetList($page, DEFAULT_ITEMS_PER_PAGE, $search, $role, 0, $statut);
$roles = usersGetRoles();

$users = $result['users'];
$totalPages = $result['totalPages'];
$totalUsers = $result['totalItems'];
$currentPage = $result['currentPage'];
$itemsPerPage = $result['perPage'];

$pageTitle = "Gestion des utilisateurs";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo "Gestion des utilisateurs ({$totalUsers})"; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/users/add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un utilisateur
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <form action="<?php echo WEBADMIN_URL; ?>/modules/users/index.php" method="get" class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher...">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="visually-hidden">Rôle</label>
                            <select name="role" id="role" class="form-select form-select-sm">
                                <option value="0">Tous les rôles</option>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo $role == $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($r['nom'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                             <label for="statut" class="visually-hidden">Statut</label>
                            <select name="statut" id="statut" class="form-select form-select-sm">
                                <option value="">Tous les statuts</option>
                                <?php foreach (USER_STATUSES as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $statut == $s ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/users/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                    <i class="fas fa-undo"></i>
                                </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (!empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Derniere connexion</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?php if ($user['photo_url']): ?>
                                        <img src="<?php echo htmlspecialchars(ROOT_URL . $user['photo_url']); ?>" alt="Profil" class="rounded-circle me-2" width="30" height="30" style="object-fit: cover;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_name'] ?? '-'); ?></td>
                                    <td><?php echo getStatusBadge($user['statut']); ?></td>
                                    <td><?php echo $user['derniere_connexion'] ? formatDate($user['derniere_connexion']) : 'Jamais'; ?></td>
                                    <td class="table-actions">
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/delete.php?id=<?php echo $user['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" 
                                           data-user-id="<?php echo $user['id']; ?>"
                                           data-user-name="<?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>"
                                           data-bs-toggle="tooltip" 
                                           title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php
                    $paginationInfo = [
                        'currentPage' => $currentPage,
                        'totalPages' => $totalPages,
                        'totalItems' => $totalUsers,
                        'itemsPerPage' => $itemsPerPage
                    ];
                    $urlParams = array_filter(['search' => $search, 'role' => $role, 'statut' => $statut]);
                    $urlPattern = WEBADMIN_URL . '/modules/users/index.php?' . http_build_query($urlParams) . (empty($urlParams) ? '' : '&') . 'page={page}';
                    ?>
                    <div class="d-flex justify-content-center">
                        <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                    </div>
                    <?php else: ?>
                        <?php
                        $isFiltering = !empty($search) || !empty($role) || !empty($statut);
                        $message = $isFiltering 
                            ? "Aucun utilisateur trouvé correspondant à vos critères de recherche."
                            : "Aucun utilisateur n'a été créé pour le moment. <a href=\"" . WEBADMIN_URL . "/modules/users/add.php\" class=\"alert-link\">Ajouter un utilisateur</a>";
                        ?>
                        <div class="alert alert-info mt-3" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div> 