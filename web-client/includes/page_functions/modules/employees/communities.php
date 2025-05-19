<?php

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Vérifie une correspondance directe entre le type de communauté et les intérêts de l'utilisateur.
 */
function _checkDirectInterestTypeMatch(string $communityTypeLower, array $userInterestsLower): bool
{
    return !empty($communityTypeLower) && in_array($communityTypeLower, $userInterestsLower);
}

/**
 * Vérifie une correspondance entre les intérêts mappés de l'utilisateur et le type de communauté.
 */
function _checkMappedInterestTypeMatch(string $communityTypeLower, array $userInterestsLower): bool
{
    $interestToTypeMapping = [
        'sport' => 'sport',
        'santé mentale' => 'bien_etre',
        'bien-être mental' => 'bien_etre',
        'activité physique' => 'sport',
        'activite physique' => 'sport',
        'bien-être physique' => 'sport'

    ];
    foreach ($userInterestsLower as $interest) {
        if (isset($interestToTypeMapping[$interest]) && $interestToTypeMapping[$interest] === $communityTypeLower) {
            return true;
        }
    }
    return false;
}

/**
 * Vérifie si des mots-clés d'intérêt utilisateur se trouvent dans le nom ou la description de la communauté.
 */
function _checkKeywordInCommunityTextsMatch(string $communityNameLower, string $communityDescLower, array $userInterestsLower): bool
{
    foreach ($userInterestsLower as $interest) {
        if (str_contains($communityNameLower, $interest) || str_contains($communityDescLower, $interest)) {
            return true;
        }
    }
    return false;
}

/**
 * Détermine si une communauté doit être considérée comme "préférée" pour un utilisateur,
 * en fonction de ses intérêts enregistrés et du type/nom/description de la communauté.
 * Refactorisée pour utiliser des fonctions d'aide pour chaque type de vérification.
 *
 * @param array $community Les détails de la communauté.
 * @param array $userInterestsLower Tableau des intérêts de l'utilisateur en minuscules.
 * @return bool True si la communauté est considérée comme préférée.
 */
function _isCommunityPreferred(array $community, array $userInterestsLower): bool
{
    if (empty($userInterestsLower)) {
        return false;
    }

    $communityTypeLower = strtolower($community['type'] ?? '');
    $communityNameLower = strtolower($community['nom'] ?? '');
    $communityDescLower = strtolower($community['description'] ?? '');

    if (_checkDirectInterestTypeMatch($communityTypeLower, $userInterestsLower)) {
        return true;
    }

    if (_checkMappedInterestTypeMatch($communityTypeLower, $userInterestsLower)) {
        return true;
    }

    if (_checkKeywordInCommunityTextsMatch($communityNameLower, $communityDescLower, $userInterestsLower)) {
        return true;
    }

    return false;
}

/**
 * Récupère les données nécessaires pour la vue "liste" des communautés.
 *
 * @param int $salarie_id L'ID de l'employé.
 * @param string $csrfToken Le jeton CSRF actuel.
 * @return array Les données pour la vue liste.
 */
function _getCommunityListPageData(int $salarie_id, string $csrfToken): array
{
    $viewData = [
        'viewMode' => 'list',
        'pageTitle' => "Communautés",
        'preferredCommunities' => [],
        'otherCommunities' => [],
        'userMemberCommunityIds' => [],
        'csrf_token' => $csrfToken
    ];




    $userInterests = getUserInterests($salarie_id);
    $userInterestsLower = array_map('strtolower', $userInterests);

    $allCommunities = fetchAll('communautes', '', 'nom ASC');
    $userMemberCommunityIds = getUserCommunityIds($salarie_id);
    $viewData['userMemberCommunityIds'] = $userMemberCommunityIds;

    foreach ($allCommunities as $community) {
        if (_isCommunityPreferred($community, $userInterestsLower)) {
            $viewData['preferredCommunities'][] = $community;
        } else {
            $viewData['otherCommunities'][] = $community;
        }
    }

    return $viewData;
}

/**
 * Récupère les données nécessaires pour la vue "détail" d'une communauté.
 *
 * @param int $view_id L'ID de la communauté à afficher.
 * @param int $salarie_id L'ID de l'utilisateur actuel.
 * @param string $csrfToken Le jeton CSRF actuel.
 * @return array|null Les données pour la vue détail, ou null si la communauté n'est pas trouvée.
 */
function _getCommunityDetailPageData(int $view_id, int $salarie_id, string $csrfToken): ?array
{
    $communityDetails = getCommunityDetails($view_id);

    if (!$communityDetails) {
        flashMessage("Communauté non trouvée.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php');
        exit;
    }

    $viewData = [
        'viewMode' => 'detail',
        'pageTitle' => htmlspecialchars($communityDetails['nom']),
        'community' => $communityDetails,
        'messages' => getCommunityMessages($view_id),
        'isMember' => isUserMemberOfCommunity($salarie_id, $view_id),
        'memberCount' => getCommunityMemberCount($view_id),
        'members' => getCommunityMembers($view_id),
        'csrf_token' => $csrfToken
    ];

    return $viewData;
}

/**
 * Configure les données nécessaires pour la page des communautés.
 * Refactorisé pour utiliser des fonctions d'aide et un flux plus linéaire.
 *
 * @return array Un tableau contenant les données pour la vue.
 */
function setupCommunitiesPage(): array
{
    requireRole(ROLE_SALARIE);
    $salarie_id = $_SESSION['user_id'] ?? 0;
    if ($salarie_id <= 0) {
        flashMessage("Erreur critique: ID Salarié non trouvé en session.", "danger");
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }


    handleCommunityActions($salarie_id);

    $view_id = filter_input(INPUT_GET, 'view_id', FILTER_VALIDATE_INT);
    $csrfToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';


    $isDetailViewRequested = ($view_id !== null && $view_id > 0);

    if ($isDetailViewRequested) {

        $detailData = _getCommunityDetailPageData($view_id, $salarie_id, $csrfToken);

        return $detailData;
    }

    $listData = _getCommunityListPageData($salarie_id, $csrfToken);
    return $listData;
}

/**
 * Récupère les IDs de toutes les communautés dont un utilisateur spécifique est membre.
 *
 * @param int $salarie_id L'ID de l'utilisateur.
 * @return array Un tableau d'IDs de communautés.
 */
function getUserCommunityIds(int $salarie_id): array
{
    if ($salarie_id <= 0) return [];

    $sql = "SELECT communaute_id FROM communaute_membres WHERE personne_id = :salarie_id";
    $stmt = executeQuery($sql, [':salarie_id' => $salarie_id]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

/**
 * Gère la soumission d'un nouveau message dans une communauté.
 *
 * @param int $salarie_id L'ID de l'auteur.
 * @param int|null $community_id L'ID de la communauté cible.
 * @param string|null $csrf_token Le jeton CSRF reçu.
 * @param mixed $message_content Le contenu du message reçu.
 */
function _handlePostMessageAction(int $salarie_id, ?int $community_id, ?string $csrf_token, $message_content): void
{
    if (!$community_id || !$csrf_token) {
        flashMessage("Action invalide ou jeton de sécurité expiré.", "danger");
        $redirectUrl = $community_id
            ? WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community_id
            : WEBCLIENT_URL . '/modules/employees/communities.php';
        redirectTo($redirectUrl);
        exit;
    }

    $trimmed_content = is_string($message_content) ? trim($message_content) : '';
    if (empty($trimmed_content)) {
        flashMessage("Le contenu du message ne peut pas être vide.", "warning");
    } else {
        postCommunityMessage($salarie_id, $community_id, $trimmed_content);
    }

    redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community_id);
    exit;
}

/**
 * Exécute l'action de rejoindre une communauté.
 */
function _performJoinAction(int $salarie_id, ?int $community_id): void
{
    if (!$community_id) {
        flashMessage("ID de communauté manquant pour rejoindre.", "warning");
        return;
    }
    joinCommunity($salarie_id, $community_id);
}

/**
 * Exécute l'action de quitter une communauté.
 */
function _performLeaveAction(int $salarie_id, ?int $community_id): void
{
    if (!$community_id) {
        flashMessage("ID de communauté manquant pour quitter.", "warning");
        return;
    }
    leaveCommunity($salarie_id, $community_id);
}

/**
 * Exécute l'action de supprimer un message.
 */
function _performDeleteMessageAction(int $salarie_id, ?int $message_id): void
{
    if (!$message_id) {
        flashMessage("ID de message manquant pour la suppression.", "warning");
        return;
    }
    deleteCommunityMessage($salarie_id, $message_id);
}

/**
 * Gère les actions pour rejoindre, quitter une communauté ou supprimer un message.
 * Refactorisé pour utiliser des fonctions d'aide spécifiques à chaque action.
 *
 * @param string $action L'action demandée ('join', 'leave', 'delete_message').
 * @param int $salarie_id L'ID de l'utilisateur effectuant l'action.
 * @param int|null $community_id L'ID de la communauté concernée.
 * @param int|null $message_id L'ID du message concerné (si action=delete_message).
 * @param string|null $csrf_token Le jeton CSRF reçu.
 */
function _handleJoinLeaveDeleteAction(string $action, int $salarie_id, ?int $community_id, ?int $message_id, ?string $csrf_token): void
{




    $main_id_for_action = ($action === 'delete_message') ? $message_id : $community_id;
    if (!$main_id_for_action && ($action === 'join' || $action === 'leave' || $action === 'delete_message')) {
        flashMessage("ID requis manquant pour l'action demandée '" . htmlspecialchars($action) . "'.", "warning");

        redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php');
        exit;
    }

    switch ($action) {
        case 'join':
            _performJoinAction($salarie_id, $community_id);
            break;
        case 'leave':
            _performLeaveAction($salarie_id, $community_id);
            break;
        case 'delete_message':
            _performDeleteMessageAction($salarie_id, $message_id);
            break;
        default:
            flashMessage("Action non reconnue.", "warning");


            break;
    }



    $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php';


    if ($community_id && ($action === 'join' || $action === 'delete_message')) {
        $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community_id;
    }

    redirectTo($redirectUrl);
    exit;
}

/**
 * Vérifie si une action de publication de message est demandée via GET.
 */
function _isPostMessageActionRequested(): bool
{
    return isset($_GET['action']) && $_GET['action'] === 'post_message';
}

/**
 * Vérifie si une action d'interaction communautaire valide (join, leave, delete_message) est demandée via POST.
 */
function _isValidCommunityInteractionRequested(array $postData): bool
{
    return isset($postData['action']) && in_array($postData['action'], ['join', 'leave', 'delete_message']);
}

/**
 * Gère les actions utilisateur POST (poster, rejoindre, quitter, supprimer).
 * Refactorisé pour utiliser des fonctions d'aide.
 *
 * @param int $salarie_id L'ID de l'employé actuellement connecté.
 */
function handleCommunityActions(int $salarie_id): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }


    if (_isPostMessageActionRequested()) {
        $community_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);
        $message_content = filter_input(INPUT_POST, 'message_content', FILTER_DEFAULT);
        _handlePostMessageAction($salarie_id, $community_id, $csrf_token, $message_content);
        return;
    }


    if (_isValidCommunityInteractionRequested($_POST)) {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
        $community_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
        $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);
        _handleJoinLeaveDeleteAction($action, $salarie_id, $community_id, $message_id, $csrf_token);
        return;
    }
}

/**
 * Ajoute un employé à une communauté.
 *
 * @param int $salarie_id L'ID de l'employé.
 * @param int $community_id L'ID de la communauté.
 * @return bool True en cas de succès, false en cas d'échec.
 */
function joinCommunity(int $salarie_id, int $community_id): bool
{
    if ($salarie_id <= 0 || $community_id <= 0) return false;

    $community = fetchOne('communautes', 'id = :id', [':id' => $community_id]);
    if (!$community) {
        flashMessage("La communauté que vous essayez de rejoindre n'existe pas.", "warning");
        return false;
    }

    $existingMembership = fetchOne(
        'communaute_membres',
        'communaute_id = :comm_id AND personne_id = :pers_id',
        [':comm_id' => $community_id, ':pers_id' => $salarie_id]
    );

    if ($existingMembership) {
        flashMessage("Vous êtes déjà membre de cette communauté.", "info");
        return true;
    }

    $data = [
        'communaute_id' => $community_id,
        'personne_id' => $salarie_id
    ];

    if (insertRow('communaute_membres', $data)) {
        flashMessage("Vous avez rejoint la communauté \"" . htmlspecialchars($community['nom']) . "\" avec succès !", "success");
        return true;
    } else {
        flashMessage("Une erreur est survenue en essayant de rejoindre la communauté.", "danger");
        return false;
    }
}

/**
 * Récupère les détails d'une seule communauté.
 *
 * @param int $community_id L'ID de la communauté.
 * @return array|null Détails de la communauté ou null si non trouvée.
 */
function getCommunityDetails(int $community_id): ?array
{
    if ($community_id <= 0) {
        return null;
    }
    $details = fetchOne('communautes', 'id = :id', [':id' => $community_id]);
    return $details ?: null;
}

/**
 * Récupère les messages d'une communauté spécifique.
 *
 * @param int $community_id L'ID de la communauté.
 * @param int $page Numéro de page actuel (pour pagination éventuelle).
 * @param int $perPage Éléments par page (pour pagination éventuelle).
 * @return array Liste des messages (potentiellement avec info auteur).
 */
function getCommunityMessages(int $community_id, int $page = 1, int $perPage = 20): array
{
    if ($community_id <= 0) {
        return [];
    }

    $sql = "SELECT
                cm.id, cm.message, cm.created_at,
                cm.personne_id,
                CONCAT(p.prenom, ' ', p.nom) as auteur_nom
            FROM communaute_messages cm
            JOIN personnes p ON cm.personne_id = p.id
            WHERE cm.communaute_id = :community_id
            ORDER BY cm.created_at DESC";

    return executeQuery($sql, [':community_id' => $community_id])->fetchAll();
}

/**
 * Vérifie si un utilisateur est membre d'une communauté spécifique.
 *
 * @param int $salarie_id L'ID de l'utilisateur.
 * @param int $community_id L'ID de la communauté.
 * @return bool True si l'utilisateur est membre, false sinon.
 */
function isUserMemberOfCommunity(int $salarie_id, int $community_id): bool
{
    if ($salarie_id <= 0 || $community_id <= 0) {
        return false;
    }

    $membership = fetchOne(
        'communaute_membres',
        'communaute_id = :comm_id AND personne_id = :pers_id',
        [':comm_id' => $community_id, ':pers_id' => $salarie_id]
    );
    return !empty($membership);
}

/**
 * Retire un employé d'une communauté.
 *
 * @param int $salarie_id L'ID de l'employé.
 * @param int $community_id L'ID de la communauté.
 * @return bool True en cas de succès, false en cas d'échec.
 */
function leaveCommunity(int $salarie_id, int $community_id): bool
{
    if ($salarie_id <= 0 || $community_id <= 0) return false;

    $community = fetchOne('communautes', 'id = :id', [':id' => $community_id]);
    $communityName = $community ? $community['nom'] : 'inconnue';

    $whereClause = 'communaute_id = :comm_id AND personne_id = :pers_id';
    $params = [':comm_id' => $community_id, ':pers_id' => $salarie_id];

    $deletedRows = deleteRow('communaute_membres', $whereClause, $params);

    if ($deletedRows > 0) {
        flashMessage("Vous avez quitté la communauté \"" . htmlspecialchars($communityName) . "\".", "info");
        return true;
    } else {
        flashMessage("Impossible de quitter la communauté (peut-être n'étiez-vous plus membre ?).", "warning");
        return false;
    }
}

/**
 * Vérifie si les paramètres pour poster un message sont valides.
 */
function _arePostMessageParametersValid(int $salarie_id, int $community_id, string $content): bool
{

    return $salarie_id > 0 && $community_id > 0 && !empty(trim($content));
}

/**
 * Ajoute un message au mur d'une communauté.
 *
 * @param int $salarie_id L'ID de l'auteur.
 * @param int $community_id L'ID de la communauté.
 * @param string $content Le contenu du message.
 * @return bool True en cas de succès, false en cas d'échec.
 */
function postCommunityMessage(int $salarie_id, int $community_id, string $content): bool
{
    if (!_arePostMessageParametersValid($salarie_id, $community_id, $content)) {
        flashMessage("Données invalides pour poster le message.", "warning");
        return false;
    }

    if (!isUserMemberOfCommunity($salarie_id, $community_id)) {
        flashMessage("Vous devez être membre pour poster dans cette communauté.", "danger");
        return false;
    }

    $data = [
        'communaute_id' => $community_id,
        'personne_id' => $salarie_id,
        'message' => strip_tags(trim($content))
    ];

    $success = insertRow('communaute_messages', $data);
    if ($success) {
        return true;
    } else {
        flashMessage("Erreur lors de l'envoi du message (insertion échouée).", "danger");
        return false;
    }
}

/**
 * Supprime un message spécifique du mur d'une communauté.
 *
 * @param int $salarie_id L'ID de l'utilisateur tentant la suppression.
 * @param int $message_id L'ID du message à supprimer.
 * @return bool True en cas de succès, false en cas d'échec.
 */
function deleteCommunityMessage(int $salarie_id, int $message_id): bool
{
    if ($salarie_id <= 0 || $message_id <= 0) {
        flashMessage("Données invalides pour la suppression du message.", "warning");
        return false;
    }

    $message = fetchOne('communaute_messages', 'id = :id', [':id' => $message_id]);

    if (!$message) {
        flashMessage("Le message que vous essayez de supprimer n'existe pas.", "warning");
        return false;
    }

    if ($message['personne_id'] != $salarie_id) {
        flashMessage("Vous n'avez pas la permission de supprimer ce message.", "danger");
        return false;
    }

    $deletedRows = deleteRow('communaute_messages', 'id = :id', [':id' => $message_id]);

    if ($deletedRows > 0) {
        flashMessage("Message supprimé avec succès.", "success");
        return true;
    } else {
        flashMessage("Erreur lors de la suppression du message (aucune ligne affectée).", "warning");
        return false;
    }
}

/**
 * Récupère le nombre total de membres pour une communauté spécifique.
 *
 * @param int $community_id L'ID de la communauté.
 * @return int Le nombre de membres.
 */
function getCommunityMemberCount(int $community_id): int
{
    if ($community_id <= 0) {
        return 0;
    }

    return countTableRows('communaute_membres', 'communaute_id = :id', [':id' => $community_id]);
}

/**
 * Récupère les membres d'une communauté spécifique.
 *
 * @param int $community_id L'ID de la communauté.
 * @return array Liste des membres avec leur ID, prénom et nom.
 */
function getCommunityMembers(int $community_id): array
{
    if ($community_id <= 0) {
        return [];
    }

    $sql = "SELECT p.id, p.prenom, p.nom
            FROM personnes p
            JOIN communaute_membres cm ON p.id = cm.personne_id
            WHERE cm.communaute_id = :community_id
            ORDER BY p.nom ASC, p.prenom ASC";

    return executeQuery($sql, [':community_id' => $community_id])->fetchAll();
}
