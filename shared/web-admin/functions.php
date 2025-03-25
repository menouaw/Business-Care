<?php
require_once 'config.php';
require_once 'db.php';

/**
 * Formate une date donnée selon un format personnalisé.
 *
 * Cette fonction convertit une date sous forme de chaîne en un timestamp, puis la formate
 * selon le modèle spécifié. Si la date est vide ou invalide, elle renvoie une chaîne vide.
 *
 * @param string $date La date à formater.
 * @param string $format Le format de date souhaité. Par défaut, 'd/m/Y H:i'.
 * @return string La date formatée ou une chaîne vide en cas de date invalide.
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formate un montant selon le format monétaire spécifié
 * 
 * @param float $amount Montant à formater
 * @param string $currency Symbole de la devise (par défaut '€')
 * @return string Montant formaté avec devise
 */
function formatMoney($amount, $currency = '€') {
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

/**
 * Nettoie les données entrées par l'utilisateur pour éviter les injections
 * 
 * @param mixed $input Données à nettoyer (chaîne ou tableau)
 * @return mixed Données nettoyées
 */
function sanitizeInput($input) {
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
function getRecentActivities($limit = 10) {
    $sql = "SELECT l.*, CONCAT(p.prenom, ' ', p.nom) as user_name
            FROM logs l
            LEFT JOIN personnes p ON l.personne_id = p.id
            ORDER BY l.created_at DESC
            LIMIT ?";
    
    $stmt = executeQuery($sql, [$limit]);
    return $stmt->fetchAll();
}

/**
 * Construit le titre complet de la page en combinant le nom de l'application et un titre spécifique optionnel.
 *
 * Si un titre est fourni, il est concaténé à APP_NAME avec un tiret comme séparateur. Sinon, seule la valeur de APP_NAME est retournée.
 *
 * @param string $title Titre spécifique à ajouter au nom de l'application.
 * @return string Le titre complet de la page.
 */
function generatePageTitle($title = '') {
    if ($title) {
        return APP_NAME . ' - ' . $title;
    }
    return APP_NAME;
}

/**
 * Redirige l'utilisateur vers une URL donnée et termine l'exécution du script.
 *
 * @param string $url L'adresse vers laquelle rediriger l'utilisateur.
 */
function redirectTo($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Récupère les données du formulaire POST avec nettoyage
 * 
 * @return array Données du formulaire nettoyées
 */
function getFormData() {
    return sanitizeInput($_POST);
}

/**
 * Récupère les paramètres de requête GET avec nettoyage
 * 
 * @return array Paramètres de requête nettoyés
 */
function getQueryData() {
    return sanitizeInput($_GET);
}

/**
 * Enregistre un message temporaire en session
 * 
 * @param string $message Contenu du message
 * @param string $type Type de message (success, danger, warning, info)
 * @return void
 */
function flashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Récupère le message flash enregistré en session et le supprime
 * 
 * @return array|null Message flash ou null si aucun message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Affiche les messages flash sous forme d'alertes Bootstrap
 * 
 * @return string HTML des alertes ou chaîne vide si aucun message
 */
function displayFlashMessages() {
    $flashMessage = getFlashMessage();
    if (!$flashMessage) return '';
    
    $type = $flashMessage['type'];
    $message = $flashMessage['message'];
    
    $alertTypes = [
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $alertClass = $alertTypes[$type] ?? 'alert-info';
    
    return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">'
         . $message
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
         . '</div>';
}

/**
 * Génère et retourne un jeton CSRF.
 *
 * Si aucun jeton CSRF n'existe dans la session, la fonction en crée un nouveau en utilisant 32 octets
 * de données aléatoires converties en une chaîne hexadécimale, puis le stocke dans la session.
 * Ce jeton est utilisé pour sécuriser les formulaires contre les attaques CSRF.
 *
 * @return string Le jeton CSRF.
 */
function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité d'un jeton CSRF.
 *
 * Compare le jeton fourni avec celui stocké dans la session. Renvoie true si les jetons concordent,
 * indiquant que la requête est authentique, ou false sinon.
 *
 * @param string $token Le jeton CSRF à valider.
 * @return bool True si le jeton est valide, sinon false.
 */
function validateToken($token) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    return true;
}

/**
 * Génère un badge HTML avec un style Bootstrap pour afficher un statut.
 *
 * Le badge affiche le texte du statut avec la première lettre en majuscule.
 * Si le statut fourni n'est pas défini dans la liste interne, le style par défaut "primary" est utilisé.
 *
 * @param string $status Statut à représenter.
 * @return string HTML du badge généré.
 */
function getStatusBadge($status) {
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
 * Paginer les résultats d'une requête SQL sur une table.
 *
 * Calcule le nombre total d'éléments en appliquant une clause de filtrage optionnelle,
 * ajuste le numéro de page pour qu'il reste dans des limites valides, et retourne les éléments
 * de la page ainsi que les informations de pagination associées.
 *
 * @param string $table Nom de la table sur laquelle exécuter la pagination.
 * @param int $page Numéro de la page à récupérer (sera ajusté si nécessaire).
 * @param int $perPage Nombre d'éléments par page.
 * @param string $where Clause SQL optionnelle pour filtrer les résultats.
 * @param string $orderBy Clause SQL optionnelle pour trier les résultats.
 * @return array Tableau associatif contenant :
 *               - 'items': la liste des éléments de la page actuelle,
 *               - 'currentPage': le numéro de la page actuelle,
 *               - 'totalPages': le nombre total de pages disponibles,
 *               - 'totalItems': le nombre total d'éléments correspondant à la requête,
 *               - 'perPage': le nombre d'éléments par page.
 */
function paginateResults($table, $page, $perPage = 20, $where = '', $orderBy = '') {
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
 * Génère une interface de pagination Bootstrap
 * 
 * @param array $pagination Informations de pagination issues de paginateResults()
 * @param string $urlPattern Motif d'URL avec {page} comme placeholder
 * @return string HTML de la pagination
 */
function renderPagination($pagination, $urlPattern) {
    if ($pagination['totalPages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    $prevDisabled = $pagination['currentPage'] <= 1 ? ' disabled' : '';
    $prevUrl = str_replace('{page}', $pagination['currentPage'] - 1, $urlPattern);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
    
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
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
} 