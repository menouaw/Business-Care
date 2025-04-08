<?php
require_once 'config.php';
require_once 'db.php';

define('DEFAULT_ITEMS_PER_PAGE', 10);
define('DEFAULT_DATE_FORMAT', 'd/m/Y H:i');
define('DEFAULT_CURRENCY', '€');
define('INVOICE_PREFIX', 'F');

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
    $flashMessages = $_SESSION['flash_messages'] ?? [];
    if (empty($flashMessages)) {
        return '';
    }

    unset($_SESSION['flash_messages']);

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
            . htmlspecialchars($message)
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

/**
 * Valide un jeton CSRF
 * 
 * @param string $token Jeton à valider
 * @return bool Indique si le jeton est valide
 */
function validateToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    return true;
}

/**
 * Génère un badge HTML stylisé avec Bootstrap pour représenter un statut
 *
 * @param string $status Statut à représenter
 * @return string HTML du badge Bootstrap
 */
function getStatusBadge($status)
{
    $statusLower = strtolower($status);
    switch ($statusLower) {
        case 'actif':
            return '<span class="badge bg-success">Actif</span>';
        case 'en_attente':
            return '<span class="badge bg-warning text-dark">En attente</span>';
        case 'suspendu':
            return '<span class="badge bg-danger">Suspendu</span>';
        case 'inactif':
            return '<span class="badge bg-secondary">Inactif</span>';

        case 'expire':
            return '<span class="badge bg-secondary">Expiré</span>';
        case 'resilie':
            return '<span class="badge bg-danger">Résilie</span>';
        case 'accepte':
            return '<span class="badge bg-success">Accepté</span>';
        case 'refuse':
            return '<span class="badge bg-danger">Refusé</span>';

        case 'confirme':
            return '<span class="badge bg-primary">Confirmé</span>';
        case 'termine':
            return '<span class="badge bg-info text-dark">Terminé</span>';
        case 'planifie':
            return '<span class="badge bg-info text-dark">Planifié</span>';
        case 'annule':
            return '<span class="badge bg-danger">Annulé</span>';
        case 'no_show':
            return '<span class="badge bg-warning text-dark">Non Présenté</span>';

        case 'payee':
            return '<span class="badge bg-success">Payée</span>';
        case 'impayee':
            return '<span class="badge bg-danger">Impayée</span>';
        case 'retard':
            return '<span class="badge bg-warning text-dark">En Retard</span>';
        case 'en_attente_paiement':
            return '<span class="badge bg-warning text-dark">En attente</span>';

        case 'valide':
            return '<span class="badge bg-success">Validé</span>';
        case 'nouveau':
            return '<span class="badge bg-primary">Nouveau</span>';
        case 'en_cours':
            return '<span class="badge bg-info text-dark">En cours</span>';
        case 'resolu':
            return '<span class="badge bg-success">Résolu</span>';
        case 'clos':
            return '<span class="badge bg-secondary">Clos</span>';


        default:
            return '<span class="badge bg-light text-dark">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}


function paginateResults($table, $page, $perPage = DEFAULT_ITEMS_PER_PAGE, $where = '', $orderBy = '', $params = [])
{
    $totalItems = countTableRows($table, $where, $params);
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
    $offset = ($page - 1) * $perPage;

    $items = fetchAll($table, $where, $orderBy, $perPage, $offset, $params);

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
            WHERE prestation_id = ?
            AND statut NOT IN ('annule', 'termine')
            AND (
                (date_rdv <= ? AND DATE_ADD(date_rdv, INTERVAL duree MINUTE) > ?)
                OR
                (date_rdv < ? AND DATE_ADD(date_rdv, INTERVAL duree MINUTE) >= ?)
                OR
                (date_rdv >= ? AND DATE_ADD(date_rdv, INTERVAL duree MINUTE) <= ?)
            )";

    $params = [
        $prestationId,
        $dateHeure,
        $dateHeure,
        $finRdv,
        $finRdv,
        $dateHeure,
        $finRdv
    ];

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn() == 0;
}

/**
 * Génère un numéro de facture unique basé sur la date courante.
 *
 * Le numéro est composé du préfixe configuré (défini par la constante INVOICE_PREFIX),
 * de la date au format YYYYMMDD et d'un identifiant séquentiel sur 4 chiffres. La fonction
 * interroge la base de données pour récupérer le dernier identifiant utilisé pour la date
 * actuelle, puis incrémente cet identifiant pour garantir l'unicité du numéro généré.
 *
 * @return string Le numéro de facture au format INVOICE_PREFIX-YYYYMMDD-XXXX.
 */
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

/**
 * Formate un nombre en devise (euros).
 *
 * @param float|null $amount Le montant.
 * @param string $currencySymbol Le symbole de la devise.
 * @return string Le montant formaté ou 'N/A' si null.
 */
function formatCurrency($amount, $currencySymbol = '€')
{
    if ($amount === null || !is_numeric($amount)) {
        return 'N/A';
    }
    return number_format($amount, 2, ',', ' ') . ' ' . $currencySymbol;
}

/**
 * Formate un objet DateInterval en une chaîne de caractères représentant le nombre total de mois.
 *
 * @param DateInterval|null $interval L'intervalle de temps.
 * @return string La durée formatée en mois (ex: "36 mois") ou "-" si null/invalide.
 */
function formatDuration($interval)
{
    if (!$interval instanceof DateInterval) {
        return '-';
    }
    $totalMonths = ($interval->y * 12) + $interval->m;
    return $totalMonths . ' mois';
}

/**
 * Gère la redirection en cas d'échec de validation CSRF pour le client.
 * Log l'échec, affiche un message flash et redirige vers une page par défaut (ex: index).
 *
 * @param string $actionDescription Description de l'action tentée (ex: 'soumission formulaire').
 * @param string $redirectUrl URL de redirection par défaut.
 * @return void
 */
function handleClientCsrfFailureRedirect($actionDescription = 'action', $redirectUrl = null)
{
    $redirectUrl = $redirectUrl ?? WEBCLIENT_URL . '/index.php';
    flashMessage('Jeton de sécurité invalide ou expiré. Veuillez réessayer.', 'danger');
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    logSecurityEvent(
        $_SESSION['user_id'] ?? null,
        'csrf_failure',
        "[SECURITY FAILURE] Tentative de {$actionDescription} avec jeton invalide via {$requestMethod} sur web-client"
    );
    redirectTo($redirectUrl);
}
