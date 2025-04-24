<?php
require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/db.php';

/**
 * Formate une date selon un format spécifié.
 *
 * La fonction convertit la chaîne de caractères représentant une date en timestamp, puis renvoie la date formatée selon le format indiqué.
 * Si la date d'entrée est vide, elle renvoie une chaîne vide.
 *
 * @param string $date Chaîne représentant la date à formater.
 * @param string $format Format de la date souhaité. Si omis, le format par défaut est défini par DEFAULT_DATE_FORMAT.
 * @return string La date formatée ou une chaîne vide si l'entrée est vide.
 */
function formatDate($date, $format = DEFAULT_DATE_FORMAT)
{
    if (!$date) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formate un montant en chaîne de caractères selon le format monétaire français.
 *
 * Le montant est présenté avec deux décimales, en utilisant la virgule pour le séparateur décimal et l'espace pour le séparateur des milliers,
 * suivi d'un espace et du symbole de la devise.
 *
 * @param float $amount Le montant à formater.
 * @param string $currency Le symbole de la devise, par défaut celui défini par DEFAULT_CURRENCY.
 * @return string Le montant formaté avec la devise.
 */
function formatMoney($amount, $currency = DEFAULT_CURRENCY)
{
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

/**
 * Nettoie et sécurise les données utilisateur pour prévenir les injections et assurer un affichage correct.
 *
 * La fonction transforme toute entrée en chaîne de caractères sécurisée : les espaces superflus sont supprimés,
 * les antislashes retirés, et les caractères spéciaux convertis en entités HTML. Si une valeur nulle est fournie,
 * elle est convertie en chaîne vide. Pour un tableau, le nettoyage s'applique récursivement à chaque élément.
 *
 * @param mixed $input Donnée ou tableau de données à nettoyer.
 * @return mixed Données nettoyées, sous forme de chaîne ou de tableau selon l'entrée.
 */
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


function timeAgo($time)
{
    if (!is_numeric($time)) {
        $time = strtotime($time);
        if ($time === false) {
            error_log("timeAgo: Impossible de convertir l'entrée en timestamp.");
            return 'date invalide';
        }
    }

    $time_difference = time() - $time;

    if ($time_difference < 1) {
        return 'à l\'instant';
    }
    $condition = array(
        12 * 30 * 24 * 60 * 60 =>  'an',
        30 * 24 * 60 * 60       =>  'mois',
        24 * 60 * 60            =>  'jour',
        60 * 60                 =>  'heure',
        60                      =>  'minute',
        1                       =>  'seconde'
    );

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;

        if ($d >= 1) {
            $t = round($d);
            $plural = ($t > 1 && $str !== 'mois') ? 's' : '';
            return 'il y a ' . $t . ' ' . $str . $plural;
        }
    }
    return 'à l\'instant';
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
    $flashMessages = getFlashMessages();

    if (empty($flashMessages)) {
        return '';
    }

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

/**
 * Génère un jeton CSRF pour la protection des formulaires
 * 
 * @return string Jeton CSRF
 */
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


function getServiceIcon($type)
{
    switch (strtolower($type)) {
        case 'conference':
            return 'fas fa-chalkboard-teacher';
        case 'webinar':
            return 'fas fa-desktop';
        case 'atelier':
            return 'fas fa-tools';
        case 'consultation':
            return 'fas fa-user-md';
        case 'evenement':
            return 'fas fa-calendar-alt';
        case 'autre':
        default:
            return 'fas fa-concierge-bell';
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

    return paginateResults('prestations', $page, $perPage, $whereClause, 'nom ASC', $params);
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

/**
 * Vérifie si l'URL fournie correspond à la page actuelle pour les liens de navigation.
 *
 * @param string $url L'URL du lien de navigation.
 * @return bool True si l'URL correspond à la page actuelle, false sinon.
 */
function isActivePage(string $url): bool
{


    $current_uri = $_SERVER['REQUEST_URI'] ?? '';
    $base_url = defined('WEBCLIENT_URL') ? parse_url(WEBCLIENT_URL, PHP_URL_PATH) : '';
    $link_uri = parse_url($url, PHP_URL_PATH) ?? '';


    if ($base_url && str_starts_with($current_uri, $base_url)) {
        $current_uri = substr($current_uri, strlen($base_url));
    }
    if ($base_url && str_starts_with($link_uri, $base_url)) {
        $link_uri = substr($link_uri, strlen($base_url));
    }

    return $current_uri === $link_uri;
}

/**
 * Retourne la classe CSS Bootstrap pour le badge de statut.
 * Utilise une map pour les statuts communs (contrats, factures, etc.)
 *
 * @param string|null $status Le statut.
 * @return string La classe CSS du badge (ex: 'success', 'warning', 'danger').
 */
function getStatusBadgeClass(?string $status): string
{
    if ($status === null) {
        return 'light'; // Ou une autre classe par défaut pour le null
    }

    $status = strtolower($status);

    // Map des statuts vers les classes de badge
    $statusMap = [
        // Statuts Contrat
        'actif' => 'success',
        'expire' => 'secondary',
        'resilie' => 'danger',
        'en_attente' => 'warning', // Aussi pour devis/factures

        // Statuts Facture Client
        'payee' => 'success',
        'annulee' => 'secondary',
        'retard' => 'danger',
        'impayee' => 'danger',

        // Statuts Devis
        'accepte' => 'success',
        'refuse' => 'danger',
        // 'expire' déjà défini
        'demande_en_cours' => 'info',

        // Autres statuts possibles
        'inactif' => 'secondary',
        'suspendu' => 'secondary',
        'planifie' => 'primary',
        'termine' => 'info',
        'confirme' => 'success',
        'nouveau' => 'primary',
        'en_cours' => 'info',
        'resolu' => 'success',
        'clos' => 'secondary'
    ];

    return $statusMap[$status] ?? 'light'; // Retourne 'light' si statut inconnu
}

/**
 * Crée une notification pour un utilisateur spécifique.
 *
 * @param int $user_id ID de l'utilisateur destinataire.
 * @param string $title Titre de la notification.
 * @param string $message Message de la notification.
 * @param string $type Type de notification (info, success, warning, error).
 * @param string|null $link Lien optionnel associé.
 * @return int|false L'ID de la notification créée ou false en cas d'erreur.
 */
function createNotification(int $user_id, string $title, string $message, string $type = 'info', ?string $link = null): int|false
{
    if ($user_id <= 0 || empty($title) || empty($message)) {
        return false;
    }

    $data = [
        'personne_id' => $user_id,
        'titre' => $title,
        'message' => $message,
        'type' => in_array($type, ['info', 'success', 'warning', 'error']) ? $type : 'info',
        'lien' => $link
    ];

    return insertRow('notifications', $data);
}
