<?php
require_once __DIR__ . '/../../shared/web-client/config.php';

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

/**
 * Formate une unité de temps écoulé pour l'affichage (gère le pluriel).
 *
 * @param int $value La valeur numérique (ex: 5).
 * @param string $unit L'unité de temps singulière (ex: 'minute', 'mois').
 * @return string La chaîne formatée (ex: '5 minutes', '1 mois').
 */
function _formatTimeDifferenceUnit(int $value, string $unit): string
{
    
    $plural = ($value > 1 && $unit !== 'mois') ? 's' : '';
    return 'il y a ' . $value . ' ' . $unit . $plural;
}

function timeAgo($time)
{
    if (!is_numeric($time)) {
        $time = strtotime($time);
        if ($time === false) {
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
            
            return _formatTimeDifferenceUnit($t, $str);
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
    $totalPages = $totalItems > 0 ? ceil($totalItems / $perPage) : 0;
    $page = $totalItems > 0 ? max(1, min($page, $totalPages)) : 0;
    $offset = ($page > 0) ? ($page - 1) * $perPage : 0;

    $items = [];
    if ($page > 0) {
        $items = fetchAll($table, $where, $orderBy, $perPage, $offset, $params);
    }

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

/**
 * Fonction utilitaire pour générer le HTML d'un élément de pagination.
 *
 * @param string $label Texte du lien.
 * @param string $url URL du lien.
 * @param bool $active Si l'élément est actif.
 * @param bool $disabled Si l'élément est désactivé.
 * @param string $ariaLabel Attribut aria-label optionnel.
 * @return string Code HTML de l'élément <li>.
 */
function _renderPaginationLink(string $label, string $url = '#', bool $active = false, bool $disabled = false, string $ariaLabel = ''): string
{
    $liClass = 'page-item' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '');
    $linkClass = 'page-link';
    $ariaCurrent = $active ? ' aria-current="page"' : '';
    $ariaDisabled = $disabled ? ' aria-disabled="true" tabindex="-1"' : '';
    $ariaLabelAttr = $ariaLabel ? ' aria-label="' . htmlspecialchars($ariaLabel) . '"' : '';

    if ($disabled && !$active) {
        return '<li class="' . $liClass . '"><span class="' . $linkClass . '">' . htmlspecialchars($label) . '</span></li>';
    } else {
        return '<li class="' . $liClass . '"' . $ariaCurrent . $ariaDisabled . $ariaLabelAttr . '><a class="' . $linkClass . '" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a></li>';
    }
}

/**
 * Génère le lien "Précédent" de la pagination.
 *
 * @param int $currentPage Page actuelle.
 * @param string $urlPattern Modèle d'URL.
 * @return string Code HTML du lien.
 */
function _renderPaginationPreviousLink(int $currentPage, string $urlPattern): string
{
    $disabled = $currentPage <= 1;
    $url = $disabled ? '#' : str_replace('{page}', $currentPage - 1, $urlPattern);
    return _renderPaginationLink('Précédent', $url, false, $disabled, 'Page précédente');
}

/**
 * Génère le lien "Suivant" de la pagination.
 *
 * @param int $currentPage Page actuelle.
 * @param int $totalPages Nombre total de pages.
 * @param string $urlPattern Modèle d'URL.
 * @return string Code HTML du lien.
 */
function _renderPaginationNextLink(int $currentPage, int $totalPages, string $urlPattern): string
{
    $disabled = $currentPage >= $totalPages;
    $url = $disabled ? '#' : str_replace('{page}', $currentPage + 1, $urlPattern);
    return _renderPaginationLink('Suivant', $url, false, $disabled, 'Page suivante');
}

/**
 * Génère les liens pour les pages du milieu de la pagination.
 *
 * @param int $currentPage Page actuelle.
 * @param int $totalPages Nombre total de pages.
 * @param string $urlPattern Modèle d'URL.
 * @return string Code HTML des liens.
 */
function _renderPaginationMiddlePages(int $currentPage, int $totalPages, string $urlPattern): string
{
    $html = '';
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($currentPage <= 3) {
        $endPage = min($totalPages, 5);
    }
    if ($currentPage >= $totalPages - 2) {
        $startPage = max(1, $totalPages - 4);
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        $url = str_replace('{page}', $i, $urlPattern);
        $html .= _renderPaginationLink((string)$i, $url, $i == $currentPage, false, 'Page ' . $i);
    }
    return $html;
}

/**
 * Génère le lien vers la première page et les points de suspension si nécessaire.
 *
 * @param int $currentPage Page actuelle.
 * @param string $urlPattern Modèle d'URL.
 * @return string Code HTML.
 */
function _renderPaginationFirstPages(int $currentPage, string $urlPattern): string
{
    $html = '';
    $startPage = max(1, $currentPage - 2);
    if ($currentPage <= 3) {
        $startPage = 1;
    }

    if ($startPage > 1) {
        $html .= _renderPaginationLink('1', str_replace('{page}', 1, $urlPattern), false, false, 'Page 1');
        if ($startPage > 2) {
            $html .= _renderPaginationLink('...', '#', false, true);
        }
    }
    return $html;
}

/**
 * Génère les points de suspension et le lien vers la dernière page si nécessaire.
 *
 * @param int $currentPage Page actuelle.
 * @param int $totalPages Nombre total de pages.
 * @param string $urlPattern Modèle d'URL.
 * @return string Code HTML.
 */
function _renderPaginationLastPages(int $currentPage, int $totalPages, string $urlPattern): string
{
    $html = '';
    $endPage = min($totalPages, $currentPage + 2);
    if ($currentPage >= $totalPages - 2) {
        $endPage = $totalPages;
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= _renderPaginationLink('...', '#', false, true);
        }
        $html .= _renderPaginationLink((string)$totalPages, str_replace('{page}', $totalPages, $urlPattern), false, false, 'Page ' . $totalPages);
    }
    return $html;
}

/**
 * Génère le code HTML complet pour la pagination.
 * Refactorisé pour utiliser des fonctions d'aide.
 *
 * @param array $pagination Tableau contenant les clés 'totalPages' et 'currentPage'.
 * @param string $urlPattern Modèle d'URL avec {page} comme placeholder.
 * @return string Code HTML de la pagination.
 */
function renderPagination($pagination, $urlPattern)
{
    if (!is_array($pagination) || !isset($pagination['totalPages'], $pagination['currentPage'])) {
        
        return '';
    }

    $totalPages = (int)$pagination['totalPages'];
    $currentPage = (int)$pagination['currentPage'];

    if ($totalPages <= 1) {
        return '';
    }
    if ($currentPage === 0) {
        return '';
    }

    $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-center">';

    $html .= _renderPaginationPreviousLink($currentPage, $urlPattern);
    $html .= _renderPaginationFirstPages($currentPage, $urlPattern);
    $html .= _renderPaginationMiddlePages($currentPage, $totalPages, $urlPattern);
    $html .= _renderPaginationLastPages($currentPage, $totalPages, $urlPattern);
    $html .= _renderPaginationNextLink($currentPage, $totalPages, $urlPattern);

    $html .= '</ul></nav>';

    return $html;
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

    $base_url = parse_url(WEBCLIENT_URL, PHP_URL_PATH) ?? '';
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
        return 'light';
    }

    $status = strtolower($status);

    $statusMap = [
        'actif' => 'success',
        'expire' => 'secondary',
        'resilie' => 'danger',
        'payee' => 'success',
        'annulee' => 'secondary',
        'retard' => 'danger',
        'impayee' => 'danger',
        'accepte' => 'success',
        'refuse' => 'danger',
        'en_attente' => 'info',
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

    return $statusMap[$status] ?? 'light';
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
        'type' => in_array($type, ['info', 'success', 'warning', 'error', 'danger']) ? $type : 'info',
        'lien' => $link
    ];

    $success = insertRow('notifications', $data);

    if (!$success) {
        return false;
    } else {
        $pdo = getDbConnection();
        $lastId = $pdo->lastInsertId();
        if ($lastId) {
            return (int)$lastId;
        } else {
            
            return false;
        }
    }
}

function getUnreadNotificationCount(int $userId): int
{
    if ($userId <= 0) return 0;

    $sql = "SELECT COUNT(*) FROM notifications WHERE personne_id = :user_id AND lu = 0";
    $stmt = executeQuery($sql, [':user_id' => $userId]);

    return (int)$stmt->fetchColumn();
}

/**
 * Marque toutes les notifications non lues d'un utilisateur comme lues.
 *
 * @param int $userId L'ID de l'utilisateur.
 * @return int Le nombre de notifications mises à jour.
 */
function markNotificationsAsRead(int $userId): int
{
    if ($userId <= 0) return 0;

    $updateData = [
        'lu' => 1,
        'date_lecture' => date('Y-m-d H:i:s')
    ];

    return updateRow('notifications', $updateData, 'personne_id = :user_id AND lu = 0', [':user_id' => $userId]);
}

/**
 * Tronque une chaîne de caractères si elle dépasse une longueur maximale.
 * Ajoute '...' à la fin si la chaîne est tronquée.
 *
 * @param string $text Le texte à tronquer.
 * @param int $maxLength La longueur maximale autorisée.
 * @return string Le texte tronqué ou original.
 */
function truncateText(string $text, int $maxLength): string
{
    if (mb_strlen($text) > $maxLength) {
        return mb_substr($text, 0, $maxLength) . '...';
    }
    return $text;
}

/**
 * Récupère les noms des intérêts d'un utilisateur donné.
 *
 * @param int $userId L'ID de l'utilisateur.
 * @return array La liste des noms des intérêts de l'utilisateur, ou un tableau vide si aucun intérêt ou erreur.
 */
function getUserInterests(int $userId): array
{
    if ($userId <= 0) return [];

    $userInterestsRaw = fetchAll('personne_interets', 'personne_id = :user_id', '', null, null, [':user_id' => $userId]);
    $userInterestIds = array_column($userInterestsRaw, 'interet_id');
    if (empty($userInterestIds)) return [];

    $placeholders = implode(',', array_fill(0, count($userInterestIds), '?'));
    $sqlInterests = "SELECT nom FROM interets_utilisateurs WHERE id IN ($placeholders)";

    $stmt = executeQuery($sqlInterests, $userInterestIds);
    $userInterestsData = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $userInterestsData ?: [];
}
