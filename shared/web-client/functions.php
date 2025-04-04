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
 * La fonction convertit la chaîne de caractères représentant une date en timestamp, puis renvoie la date formatée selon le format indiqué.
 * Si la date d'entrée est vide, elle renvoie une chaîne vide.
 *
 * @param string $date Chaîne représentant la date à formater.
 * @param string $format Format de la date souhaité. Si omis, le format par défaut est défini par DEFAULT_DATE_FORMAT.
 * @return string La date formatée ou une chaîne vide si l'entrée est vide.
 */
function formatDate($date, $format = DEFAULT_DATE_FORMAT) {
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
function formatMoney($amount, $currency = DEFAULT_CURRENCY) {
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
function sanitizeInput($input) {
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
 * Génère le titre de la page en ajoutant optionnellement un titre spécifique
 * 
 * @param string $title Titre spécifique de la page
 * @return string Titre complet formaté
 */
function generatePageTitle($title = '') {
    if ($title) {
        return APP_NAME . ' - ' . $title;
    }
    return APP_NAME;
}

/**
 * Redirige l'utilisateur vers l'URL spécifiée
 *
 * @param string $url URL de destination
 * @return void
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
 * Récupère et nettoie les paramètres de la requête GET
 *
 * @return array Les paramètres GET nettoyés
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
 * Génère un jeton CSRF pour la protection des formulaires
 * 
 * @return string Jeton CSRF
 */
function generateToken() {
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
function validateToken($token) {
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
function getStatusBadge($status) {
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

/**
 * Récupère et pagine les résultats d'une requête sur une table
 *
 * @param string $table Nom de la table à interroger
 * @param int $page Numéro de la page courante
 * @param int $perPage Nombre d'éléments à afficher par page
 * @param string $where Clause WHERE pour filtrer les résultats
 * @param string $orderBy Clause ORDER BY pour trier les résultats
 * @return array Tableau associatif contenant les éléments paginés et les métadonnées
 */
function paginateResults($table, $page, $perPage = 10, $where = '', $orderBy = '') {
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

/**
 * Génère et renvoie une interface de pagination au format Bootstrap
 *
 * @param array $pagination Tableau associatif issu de paginateResults()
 * @param string $urlPattern Motif d'URL incluant le placeholder "{page}"
 * @return string Le code HTML de l'interface de pagination
 */
function renderPagination($pagination, $urlPattern) {
    if ($pagination['totalPages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Bouton précédent
    $prevDisabled = $pagination['currentPage'] <= 1 ? ' disabled' : '';
    $prevUrl = str_replace('{page}', $pagination['currentPage'] - 1, $urlPattern);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">Précédent</a></li>';
    
    // Numéros de page
    $startPage = max(1, $pagination['currentPage'] - 2);
    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $pagination['currentPage'] ? ' active' : '';
        $url = str_replace('{page}', $i, $urlPattern);
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }
    
    // Bouton suivant
    $nextDisabled = $pagination['currentPage'] >= $pagination['totalPages'] ? ' disabled' : '';
    $nextUrl = str_replace('{page}', $pagination['currentPage'] + 1, $urlPattern);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">Suivant</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Récupère et pagine les prestations disponibles.
 *
 * Cette fonction interroge la table "prestations" en appliquant des filtres optionnels sur le type et la catégorie,
 * puis retourne les résultats paginés triés par ordre alphabétique sur le nom.
 *
 * @param string $type Filtre optionnel pour le type de prestation.
 * @param string $categorie Filtre optionnel pour la catégorie de prestation.
 * @param int $page Numéro de la page à récupérer.
 * @param int $perPage Nombre d'éléments par page, par défaut défini par la constante DEFAUT_ITEMS_PER_PAGE.
 * @return array Tableau contenant les prestations paginées ainsi que les informations de pagination.
 */
function getPrestations($type = '', $categorie = '', $page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE) {
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

/**
 * Vérifie si une date et heure sont disponibles pour une réservation
 * 
 * @param string $dateHeure Date et heure au format 'Y-m-d H:i:s'
 * @param int $duree Durée en minutes
 * @param int $prestationId ID de la prestation
 * @return bool True si disponible, false sinon
 */
function isTimeSlotAvailable($dateHeure, $duree, $prestationId) {
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
function generateInvoiceNumber() {
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