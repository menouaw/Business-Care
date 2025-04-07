<?php
require_once 'config.php';
require_once 'db.php';

define('DEFAULT_ITEMS_PER_PAGE', 10);
define('DEFAULT_DATE_FORMAT', 'd/m/Y H:i');
define('DEFAULT_CURRENCY', '€');
define('INVOICE_PREFIX', 'F');
define('MAX_FLASH_MESSAGE_LENGTH', 1024);


function formatDate($date, $format = DEFAULT_DATE_FORMAT)
{
    if (!$date) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

function formatMoney($amount, $currency = DEFAULT_CURRENCY)
{
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

function sanitizeInput($input)
{
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else if ($input === null) {
        $input = '';
    } else {
        $input = (string)$input;
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

function generatePageTitle($title = '')
{
    if ($title) {
        return APP_NAME . ' - ' . $title;
    }
    return APP_NAME;
}


function redirectTo($url)
{
    session_write_close();

    header('Location: ' . $url);
    exit;
}


function getFormData()
{
    return sanitizeInput($_POST);
}


function getQueryData()
{
    return sanitizeInput($_GET);
}


function flashMessage($message, $type = 'success')
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }

    if (mb_strlen($message) > MAX_FLASH_MESSAGE_LENGTH) {
        $message = mb_substr($message, 0, MAX_FLASH_MESSAGE_LENGTH) . ' [...]';
    }

    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessages()
{
    $messages = $_SESSION['flash_messages'] ?? [];
    if (!empty($messages)) {
        unset($_SESSION['flash_messages']);
    }
    return $messages;
}

function displayFlashMessages()
{
    // Restaurer le code original pour afficher les messages
    $flashMessages = getFlashMessages(); // Utilise la nouvelle fonction
    if (empty($flashMessages)) return '';

    $output = '';
    foreach ($flashMessages as $flashMessage) {
        $type = $flashMessage['type'] ?? 'info';
        $message = $flashMessage['message'] ?? '';

        $alertTypes = [
            'success' => 'alert-success',
            'danger' => 'alert-danger',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];

        $alertClass = $alertTypes[$type] ?? 'alert-info';

        $output .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($message) // Sécurité: échapper le message ici
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            . '</div>';
    }
    return $output;
}


function generateToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function validateToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    return true;
}

function getStatusBadge($status)
{
    $badges = [
        'actif' => 'success',
        'inactif' => 'danger',
        'en_attente' => 'warning',
        'suspendu' => 'secondary',
        'expire' => 'danger',
        'resilie' => 'danger',
        'accepte' => 'success',
        'refuse' => 'danger',
        'confirme' => 'success',
        'annule' => 'danger',
        'termine' => 'info',
        'planifie' => 'primary',
        'no_show' => 'danger',
        'payee' => 'success',
        'impayee' => 'danger',
        'retard' => 'warning'
    ];

    $class = isset($badges[$status]) ? $badges[$status] : 'primary';
    return '<span class="badge bg-' . $class . '">' . ucfirst($status) . '</span>';
}

function paginateResults($table, $page, $perPage = 10, $where = '', $orderBy = '')
{
    $totalItems = countTableRows($table, $where);
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
    $offset = ($page - 1) * $perPage;

    $items = fetchAll($table, $where, $orderBy, $perPage, $offset);

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}


function renderPagination($pagination, $urlPattern)
{
    if ($pagination['totalPages'] <= 1) return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination">';

    $prevDisabled = $pagination['currentPage'] <= 1 ? ' disabled' : '';
    $prevUrl = str_replace('{page}', $pagination['currentPage'] - 1, $urlPattern);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">Précédent</a></li>';

    $startPage = max(1, $pagination['currentPage'] - 2);
    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $pagination['currentPage'] ? ' active' : '';
        $url = str_replace('{page}', $i, $urlPattern);
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }

    $nextDisabled = $pagination['currentPage'] >= $pagination['totalPages'] ? ' disabled' : '';
    $nextUrl = str_replace('{page}', $pagination['currentPage'] + 1, $urlPattern);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">Suivant</a></li>';

    $html .= '</ul></nav>';

    return $html;
}

function getPrestations($type = '', $categorie = '', $page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE)
{
    $where = [];
    $params = [];

    if ($type) {
        $where[] = "type = :type";
        $params['type'] = $type;
    }

    if ($categorie) {
        $where[] = "categorie = :categorie";
        $params['categorie'] = $categorie;
    }

    $whereClause = !empty($where) ? implode(' AND ', $where) : '';

    return paginateResults('prestations', $page, $perPage, $whereClause, 'nom ASC');
}

function isTimeSlotAvailable($dateHeure, $duree, $prestationId)
{
    $finRdv = date('Y-m-d H:i:s', strtotime($dateHeure) + ($duree * 60));

    $sql = "SELECT COUNT(id) FROM rendez_vous 
            WHERE prestation_id = :prestation_id
            AND statut NOT IN ('annule', 'termine')
            AND (
                (date_rdv <= :debut AND DATE_ADD(date_rdv, INTERVAL duree MINUTE) > :debut)
                OR
                (date_rdv < :fin AND DATE_ADD(date_rdv, INTERVAL duree MINUTE) >= :fin)
                OR
                (date_rdv >= :debut AND DATE_ADD(date_rdv, INTERVAL duree MINUTE) <= :fin)
            )";

    $params = [
        'prestation_id' => $prestationId,
        'debut' => $dateHeure,
        'fin' => $finRdv
    ];

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn() == 0;
}


function generateInvoiceNumber()
{
    $date = date('Ymd');

    $sql = "SELECT MAX(SUBSTRING_INDEX(numero_facture, '-', -1)) AS last_id
            FROM factures
            WHERE numero_facture LIKE :pattern";

    $stmt = executeQuery($sql, ['pattern' => "F-$date-%"]);
    $result = $stmt->fetch();

    $lastId = $result['last_id'] ?? 0;
    $nextId = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

    return INVOICE_PREFIX . "-$date-$nextId";
}


function getUserById($userId)
{
    $userId = filter_var(sanitizeInput($userId), FILTER_VALIDATE_INT);
    if (!$userId) {
        return false;
    }

    try {
        $user = fetchOne('personnes', 'id = :id', [':id' => $userId]);
        // Si fetchOne retourne false (non trouvÃ©), ce n'est pas une erreur systÃ¨me
        // S'il y a une exception PDO pendant fetchOne, elle sera attrapÃ©e ci-dessous
        return $user; // Retourne l'utilisateur ou false si non trouvÃ©
    } catch (PDOException $e) { // Capturer spÃ©cifiquement PDOException
        logSystemActivity('error', "Erreur BDD dans getUserById #$userId: " . $e->getMessage());
        // Ne pas montrer de message flash ici, car la fonction appelante gÃ¨rera peut-Ãªtre
        return false; // Indique une erreur critique
    } catch (Exception $e) { // Capturer d'autres exceptions potentielles
        logSystemActivity('error', "Erreur inattendue dans getUserById #$userId: " . $e->getMessage());
        return false;
    }
}

function updateUserProfile($userId, $data)
{
    $userId = filter_var(sanitizeInput($userId), FILTER_VALIDATE_INT);
    if (!$userId || empty($data)) {
        return false;
    }

    $allowedFields = ['nom_utilisateur', 'email', 'nom', 'prenom', 'telephone'];
    $updateData = sanitizeInput($data);
    $filteredData = array_intersect_key($updateData, array_flip($allowedFields));

    if (isset($filteredData['nom_utilisateur']) && !isset($filteredData['nom'])) {
        $filteredData['nom'] = $filteredData['nom_utilisateur'];
        unset($filteredData['nom_utilisateur']);
    }


    if (empty($filteredData)) {
        return false;
    }

    try {
        if (isset($filteredData['email'])) {
            $existingUser = fetchOne('personnes', 'email = :email AND id != :id', [
                ':email' => $filteredData['email'],
                ':id' => $userId
            ]);
            if ($existingUser) {
                flashMessage('Cette adresse email est déjà utilisée.', 'danger');
                return false;
            }
        }

        $affectedRows = updateRow('personnes', $filteredData, 'id = :id', [':id' => $userId]);

        // updateRow est supposÃ© retourner le nombre de lignes ou false en cas d'Ã©chec
        if ($affectedRows !== false) {
            if ($affectedRows > 0) {
                logBusinessOperation($userId, 'update_profile', "Mise Ã  jour profil utilisateur #$userId");
                flashMessage("Profil mis à jour avec succès.", 'success');
            } else {
                // Aucune ligne modifiÃ©e, mais la requÃªte a rÃ©ussi
                flashMessage("Aucune modification n'a été appliquée au profil (données identiques ?).", 'info');
            }
            return true; // L'opÃ©ration a rÃ©ussi (mÃªme si 0 ligne affectÃ©e)
        } else {
            // updateRow a retournÃ© false, indiquant une erreur d'exÃ©cution
            logSystemActivity('error', "updateRow a Ã©chouÃ© dans updateUserProfile pour user #$userId");
            flashMessage('Une erreur technique est survenue lors de la mise à jour du profil.', 'danger');
            return false;
        }
    } catch (PDOException $e) { // Capturer spÃ©cifiquement PDOException
        logSystemActivity('error', "Erreur BDD dans updateUserProfile #$userId: " . $e->getMessage());
        flashMessage('Une erreur de base de données est survenue lors de la mise à jour du profil.', 'danger');
        return false;
    } catch (Exception $e) { // Capturer d'autres exceptions
        logSystemActivity('error', "Erreur inattendue dans updateUserProfile #$userId: " . $e->getMessage());
        flashMessage('Une erreur technique est survenue lors de la mise à jour du profil.', 'danger');
        return false;
    }
}


function changeUserPassword($userId, $currentPassword, $newPassword)
{
    $userId = filter_var(sanitizeInput($userId), FILTER_VALIDATE_INT);
    if (!$userId || empty($currentPassword) || empty($newPassword)) {
        return false;
    }

    try {
        $user = getUserById($userId); // Utilise la fonction getUserById définie ci-dessus
        if (!$user || !isset($user['mot_de_passe'])) {
            return false;
        }
        if (!password_verify($currentPassword, $user['mot_de_passe'])) {
            flashMessage("Le mot de passe actuel fourni est incorrect.", "danger");
            return false;
        }

        /*Vérifier la complexité du nouveau mot de passe si nécessaire
        if (strlen($newPassword) < 8) {
            flashMessage("Le mot de passe doit contenir au moins 8 caractères.", "danger");
            return false;
        }
            */


        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!$newPasswordHash) {
            logSystemActivity('error', "Erreur hachage nouveau mot de passe pour utilisateur #$userId");
            flashMessage("Erreur technique lors de la préparation du nouveau mot de passe.", "danger"); // Ajout message flash
            return false;
        }

        $updateData = ['mot_de_passe' => $newPasswordHash];
        $affectedRows = updateRow('personnes', $updateData, 'id = :id', [':id' => $userId]);

        if ($affectedRows > 0) {
            logBusinessOperation($userId, 'change_password', "Changement mot de passe utilisateur #$userId");
            flashMessage("Mot de passe modifié avec succès.", "success");
            return true;
        } else {
            logSystemActivity('warning', "changeUserPassword: updateRow a retournÃ© '" . $affectedRows . "' (<= 0) pour user #$userId.");
            flashMessage("Le mot de passe n'a pas pu être mis à jour (aucune modification détectée ?).", "warning");
            return false;
        }
    } catch (PDOException $e) {
        logSystemActivity('error', "Erreur BDD dans changeUserPassword #$userId: " . $e->getMessage());
        flashMessage('Une erreur de base de données est survenue lors du changement de mot de passe.', 'danger');
        return false;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur inattendue dans changeUserPassword #$userId: " . $e->getMessage());
        flashMessage('Une erreur technique est survenue lors du changement de mot de passe.', 'danger');
        return false;
    }
}


function requireEmployeeLogin()
{
    // Vérifie si les clés de session existent et si le rôle est correct
    if (
        !isset($_SESSION['user_id']) ||
        !isset($_SESSION['user_role']) ||
        $_SESSION['user_role'] != ROLE_SALARIE
    ) {
        // Si la vérification échoue, définir un message et rediriger
        flashMessage('Vous devez être connecté en tant que salarié pour accéder à cette page.', 'danger');
        // Assurez-vous que la constante WEBCLIENT_URL est définie (normalement dans config.php)
        // et que la fonction redirectTo est disponible
        redirectTo(WEBCLIENT_URL . '/login.php');
        // La fonction redirectTo contient un exit, donc le script s'arrête ici en cas d'échec.
    }
    // Si la vérification réussit, la fonction ne fait rien et le script continue.
}
