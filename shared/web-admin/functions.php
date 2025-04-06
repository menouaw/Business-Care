<?php
require_once 'config.php';
require_once 'db.php';

define('DEFAULT_ITEMS_PER_PAGE', 10);
define('DEFAULT_DATE_FORMAT', 'd/m/Y H:i');
define('DEFAULT_CURRENCY', '€');
define('INVOICE_PREFIX', 'F');

/**
 * Formate une date selon un format spécifié.
 *
 * Si la chaîne représentant la date est vide ou évaluée à false, la fonction retourne une chaîne vide.
 * En cas de conversion échouée (via strtotime), le timestamp 0 (l'époque Unix) est utilisé.
 *
 * @param string $date Chaîne représentant la date à formater.
 * @param string $format Format de date souhaité (par défaut la valeur de DEFAULT_DATE_FORMAT).
 * @return string La date formatée ou une chaîne vide si l'entrée est invalide.
 */
function formatDate($date, $format = DEFAULT_DATE_FORMAT)
{
    if (!$date) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formate un montant en une chaîne représentant une valeur monétaire.
 *
 * Le montant est affiché avec deux décimales, utilisant une virgule comme séparateur décimal et un espace pour les milliers,
 * suivi du symbole de la devise.
 *
 * @param float $amount Montant à formater.
 * @param string $currency Symbole de la devise (par défaut DEFAULT_CURRENCY).
 * @return string Montant formaté avec la devise.
 */
function formatMoney($amount, $currency = DEFAULT_CURRENCY)
{
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

/**
 * Nettoie et sécurise les données fournies par l'utilisateur afin de prévenir les injections.
 *
 * Si l'entrée est un tableau, le nettoyage est appliqué récursivement à chacune de ses valeurs.
 * Pour une chaîne, la fonction supprime les espaces superflus, enlève les barres obliques inverses,
 * et convertit les caractères spéciaux en entités HTML sécurisées.
 *
 * @param mixed $input Donnée ou tableau de données à nettoyer.
 * @return mixed Données nettoyées.
 */
function sanitizeInput($input)
{
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

/**
 * Récupère les activités récentes du système
 * 
 * @param int $limit Nombre maximum d'activités à récupérer
 * @return array Liste des activités récentes
 */
function getRecentActivities($limit = 10)
{
    $sql = "SELECT l.*, CONCAT(p.prenom, ' ', p.nom) as user_name
            FROM logs l
            LEFT JOIN personnes p ON l.personne_id = p.id
            ORDER BY l.created_at DESC
            LIMIT ?";

    $stmt = executeQuery($sql, [$limit]);
    return $stmt->fetchAll();
}

/**
 * Génère le titre de la page en ajoutant optionnellement un titre spécifique
 * 
 * @param string $title Titre spécifique de la page
 * @return string Titre complet formaté
 */
function generatePageTitle($title = '')
{
    if ($title) {
        return APP_NAME . ' - ' . $title;
    }
    return APP_NAME;
}

/**
 * Redirige l'utilisateur vers l'URL spécifiée et termine l'exécution du script.
 *
 * Cette fonction envoie un en-tête HTTP de redirection vers l'URL fournie et arrête immédiatement l'exécution du script.
 *
 * @param string $url URL de destination.
 * @return void
 */
function redirectTo($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Récupère les données du formulaire POST avec nettoyage
 * 
 * @return array Données du formulaire nettoyées
 */
function getFormData()
{
    return sanitizeInput($_POST);
}

/**
 * Récupère et nettoie les paramètres de la requête GET.
 *
 * Cette fonction retourne un tableau contenant les paramètres de la requête GET après avoir
 * appliqué un nettoyage afin d'assurer la sécurité des données.
 *
 * @return array Les paramètres GET nettoyés.
 */
function getQueryData()
{
    return sanitizeInput($_GET);
}

/**
 * Enregistre un message temporaire en session
 * 
 * @param string $message Contenu du message
 * @param string $type Type de message (success, danger, warning, info)
 * @return void
 */
function flashMessage($message, $type = 'success')
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['message' => $message, 'type' => $type];
}

/**
 * Récupère tous les messages flash enregistrés en session et les supprime
 * 
 * @return array Tableau de messages flash ou tableau vide si aucun message
 */
function getFlashMessages()
{
    if (isset($_SESSION['flash_messages']) && is_array($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']); // Clear all messages after retrieval
        return $messages;
    }
    return []; 
}

/**
 * Affiche les messages flash sous forme d'alertes Bootstrap
 * 
 * @return string HTML des alertes ou chaîne vide si aucun message
 */
function displayFlashMessages()
{
    $flashMessages = getFlashMessages();
    if (empty($flashMessages)) return '';

    $htmlOutput = '';

    static $alertTypes = [
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];

    foreach ($flashMessages as $flashMessage) {
        if (!isset($flashMessage['type']) || !isset($flashMessage['message'])) {
            continue;
        }
        $type = $flashMessage['type'];
        $message = $flashMessage['message'];

        $alertClass = $alertTypes[$type] ?? 'alert-info';

        $htmlOutput .= '<div class="alert ' . htmlspecialchars($alertClass) . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($message)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            . '</div>';
    }

    return $htmlOutput;
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
 * Valide un jeton CSRF pour la protection contre les attaques CSRF
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
 * Génère un badge HTML stylisé avec Bootstrap pour représenter un statut.
 *
 * Le badge affiche le statut fourni et adopte une couleur spécifique en fonction du statut.
 * Si le statut n'est pas reconnu parmi les valeurs prédéfinies, la classe "primary" est utilisée par défaut.
 *
 * @param string $status Statut à représenter.
 * @return string HTML du badge Bootstrap.
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
        'refuse' => 'danger'
    ];

    $class = isset($badges[$status]) ? $badges[$status] : 'primary';
    return '<span class="badge bg-' . $class . '">' . ucfirst($status) . '</span>';
}

/**
 * Récupère et pagine les résultats d'une requête sur une table.
 *
 * Cette fonction calcule le nombre total d'enregistrements correspondant aux critères optionnels,
 * détermine le nombre total de pages, ajuste le numéro de page pour qu'il soit valide, et récupère
 * ensuite les enregistrements de la page courante en appliquant les clauses facultatives de filtrage et de tri.
 *
 * @param string $table Nom de la table à interroger.
 * @param int $page Numéro de la page courante.
 * @param int $perPage Nombre d'éléments à afficher par page.
 * @param string $where (Facultatif) Clause WHERE pour filtrer les résultats.
 * @param string $orderBy (Facultatif) Clause ORDER BY pour trier les résultats.
 * @return array Un tableau associatif contenant :
 *         - 'items' (array) : Les enregistrements de la page courante.
 *         - 'currentPage' (int) : Le numéro de page ajusté.
 *         - 'totalPages' (int) : Le nombre total de pages.
 *         - 'totalItems' (int) : Le nombre total d'enregistrements répondant aux critères.
 *         - 'perPage' (int) : Le nombre d'éléments par page.
 */
function paginateResults($table, $page, $perPage = DEFAULT_ITEMS_PER_PAGE, $where = '', $orderBy = '')
{
    $totalItems = countTableRows($table, $where);
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages));
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

/**
 * Génère et renvoie une interface de pagination au format Bootstrap.
 *
 * Cette fonction crée le code HTML d'une pagination basée sur les informations contenues dans
 * le tableau $pagination. Si le nombre total de pages est inférieur ou égal à 1, elle retourne
 * une chaîne vide.
 *
 * @param array $pagination Tableau associatif issu de paginateResults() contenant les clés
 *                          'currentPage' et 'totalPages'.
 * @param string $urlPattern Motif d'URL incluant le placeholder "{page}" qui sera remplacé par
 *                           le numéro de page.
 * @return string Le code HTML de l'interface de pagination ou une chaîne vide si aucune pagination
 *                n'est nécessaire.
 */
function renderPagination($pagination, $urlPattern)
{
    if ($pagination['totalPages'] <= 1) return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination">';

    // Previous button
    $prevDisabled = $pagination['currentPage'] <= 1 ? ' disabled' : '';
    $prevUrl = str_replace('{page}', $pagination['currentPage'] - 1, $urlPattern);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">Précédent</a></li>';

    // numeros de page
    $startPage = max(1, $pagination['currentPage'] - 2);
    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $pagination['currentPage'] ? ' active' : '';
        $url = str_replace('{page}', $i, $urlPattern);
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }

    // bouton suivant
    $nextDisabled = $pagination['currentPage'] >= $pagination['totalPages'] ? ' disabled' : '';
    $nextUrl = str_replace('{page}', $pagination['currentPage'] + 1, $urlPattern);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">Suivant</a></li>';

    $html .= '</ul></nav>';

    return $html;
}
