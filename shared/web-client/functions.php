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
 * Redirige l'utilisateur vers l'URL spécifiée
 *
 * @param string $url URL de destination
 * @return void
 */
function redirectTo($url)
{
    // S'assurer que toutes les données de session sont écrites avant de rediriger
    session_write_close();

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
 * Récupère et nettoie les paramètres de la requête GET
 *
 * @return array Les paramètres GET nettoyés
 */
function getQueryData()
{
    return sanitizeInput($_GET);
}

/**
 * Enregistre un ou plusieurs messages temporaires en session
 * 
 * @param string|array $message Contenu du message ou tableau de messages
 * @param string $type Type de message (success, danger, warning, info)
 * @return void
 */
function flashMessage($message, $type = 'success')
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = []; // Initialise comme tableau s'il n'existe pas
    }
    // Ajoute le nouveau message au tableau
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Récupère TOUS les messages flash enregistrés en session et les supprime
 * 
 * @return array Tableau des messages flash ou tableau vide si aucun message
 */
function getFlashMessages()
{ // Renommée en getFlashMessages (pluriel)
    $messages = $_SESSION['flash_messages'] ?? []; // Récupère le tableau ou un tableau vide
    if (!empty($messages)) {
        unset($_SESSION['flash_messages']); // Supprime la clé de session
    }
    return $messages;
}

/**
 * Affiche les messages flash sous forme d'alertes Bootstrap
 * NOTE: Cette fonction est maintenant redondante si header.php affiche déjà les messages.
 *       Elle est conservée pour compatibilité potentielle mais ne devrait plus être appelée directement.
 * 
 * @return string HTML des alertes ou chaîne vide si aucun message
 */
function displayFlashMessages()
{
    $flashMessages = $_SESSION['flash_messages'] ?? [];
    if (empty($flashMessages)) {
        return '';
    }

    // Effacer la variable de session MAINTENANT
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
            . htmlspecialchars($message) // Sécurité: échapper le message ici
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

/**
 * Vérifie si une date et heure sont disponibles pour une réservation
 * 
 * @param string $dateHeure Date et heure au format 'Y-m-d H:i:s'
 * @param int $duree Durée en minutes
 * @param int $prestationId ID de la prestation
 * @return bool True si disponible, false sinon
 */
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
