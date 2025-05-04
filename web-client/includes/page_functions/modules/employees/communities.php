<?php

/**
 * Fonctions spécifiques à la page des communautés pour les employés.
 */

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Configure les données nécessaires pour la page des communautés.
 *
 * Gère les modes d'affichage (liste ou détail), récupère les données appropriées
 * (liste des communautés, détails d'une communauté, messages, membres),
 * gère les actions utilisateur (rejoindre, quitter, poster, supprimer message)
 * et prépare le tableau final de données pour la vue.
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

    $view_id = filter_input(INPUT_GET, 'view_id', FILTER_VALIDATE_INT);
    $viewData = [];
    $csrfToken = generateToken();

    handleCommunityActions($salarie_id);

    if ($view_id && $view_id > 0) {
        $viewData['viewMode'] = 'detail';
        $viewData['pageTitle'] = "Détails de la Communauté";

        $communityDetails = getCommunityDetails($view_id);

        if (!$communityDetails) {
            flashMessage("Communauté non trouvée.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php');
            exit;
        }

        $viewData['pageTitle'] = htmlspecialchars($communityDetails['nom']);

        $communityMessages = getCommunityMessages($view_id);

        $isMember = isUserMemberOfCommunity($salarie_id, $view_id);

        $memberCount = getCommunityMemberCount($view_id);
        $members = getCommunityMembers($view_id);

        $viewData['community'] = $communityDetails;
        $viewData['messages'] = $communityMessages;
        $viewData['isMember'] = $isMember;
        $viewData['memberCount'] = $memberCount;
        $viewData['members'] = $members;

        $viewData['csrf_token'] = $csrfToken;
    } else {
        $viewData['viewMode'] = 'list';
        $viewData['pageTitle'] = "Communautés";

        $userInterests = getUserInterests($salarie_id);
        $userInterestsLower = array_map('strtolower', $userInterests);

        // DEBUG: Afficher les intérêts récupérés (décommenter pour voir)
        // echo '<pre>Intérêts Utilisateur: '; var_dump($userInterestsLower); echo '</pre>';

        $allCommunities = fetchAll('communautes', '', 'nom ASC');

        $userMemberCommunityIds = getUserCommunityIds($salarie_id);

        $preferredCommunities = [];
        $otherCommunities = [];

        foreach ($allCommunities as $community) {
            $communityTypeLower = strtolower($community['type'] ?? '');
            $isPreferred = false;

            if (!empty($communityTypeLower) && in_array($communityTypeLower, $userInterestsLower)) {
                $isPreferred = true;
            } else {
                $communityNameLower = strtolower($community['nom'] ?? '');
                $communityDescLower = strtolower($community['description'] ?? '');
                foreach ($userInterestsLower as $interest) {
                    if (str_contains($communityNameLower, $interest) || str_contains($communityDescLower, $interest)) {
                        $isPreferred = true;
                        break;
                    }
                    if ($interest === 'sport' && $communityTypeLower === 'sport') {
                        $isPreferred = true;
                        break;
                    }
                    if (($interest === 'santé mentale' || $interest === 'bien-être mental') && $communityTypeLower === 'bien_etre') {
                        $isPreferred = true;
                        break;
                    }
                    if (($interest === 'activité physique' || $interest === 'activite physique' || $interest === 'bien-être physique') && $communityTypeLower === 'sport') {
                        $isPreferred = true;
                        break;
                    }
                }
            }

            if ($isPreferred) {
                $preferredCommunities[] = $community;
            } else {
                $otherCommunities[] = $community;
            }
        }

        $viewData['preferredCommunities'] = $preferredCommunities;
        $viewData['otherCommunities'] = $otherCommunities;
        $viewData['userMemberCommunityIds'] = $userMemberCommunityIds;
        $viewData['csrf_token'] = $csrfToken;
    }

    return $viewData;
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
 * Gère les actions utilisateur (POST pour poster, GET pour rejoindre/quitter/supprimer).
 *
 * @param int $salarie_id L'ID de l'employé actuellement connecté.
 */
function handleCommunityActions(int $salarie_id): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'post_message') {
        $community_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);
        $message_content = filter_input(INPUT_POST, 'message_content', FILTER_DEFAULT);

        if (!$community_id || !$csrf_token || !validateToken($csrf_token)) {
            flashMessage("Action invalide ou jeton de sécurité expiré.", "danger");
        } elseif (empty(trim($message_content))) {
            flashMessage("Le contenu du message ne peut pas être vide.", "warning");
        } else {
            postCommunityMessage($salarie_id, $community_id, trim($message_content));
        }

        redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community_id);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['join', 'leave', 'delete_message'])) {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
        $community_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
        $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);

        $required_id = ($action === 'delete_message') ? $message_id : $community_id;

        if (!$required_id || !$csrf_token || !validateToken($csrf_token)) {
            flashMessage("Action invalide, ID manquant ou jeton de sécurité expiré.", "danger");
            $redirectUrl = $community_id
                ? WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community_id
                : WEBCLIENT_URL . '/modules/employees/communities.php';
            redirectTo($redirectUrl);
            exit;
        }

        $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php';

        if ($action === 'join') {
            if ($community_id) {
                joinCommunity($salarie_id, $community_id);
                $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community_id;
            }
        } elseif ($action === 'leave') {
            if ($community_id) {
                leaveCommunity($salarie_id, $community_id);
            }
        } elseif ($action === 'delete_message') {
            deleteCommunityMessage($salarie_id, $message_id);
            if ($community_id) {
                $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community_id;
            }
        } else {
            flashMessage("Action non reconnue.", "warning");
        }

        redirectTo($redirectUrl);
        exit;
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
 * Ajoute un message au mur d'une communauté.
 *
 * @param int $salarie_id L'ID de l'auteur.
 * @param int $community_id L'ID de la communauté.
 * @param string $content Le contenu du message.
 * @return bool True en cas de succès, false en cas d'échec.
 */
function postCommunityMessage(int $salarie_id, int $community_id, string $content): bool
{
    if ($salarie_id <= 0 || $community_id <= 0 || empty($content)) {
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
        'message' => strip_tags($content)
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

/**
 * Récupère les clés de préférence pour un utilisateur spécifique.
 *
 * @param int $salarie_id L'ID de l'utilisateur.
 * @return array Un tableau de clés de préférence (ex: ['sport', 'lecture']).
 */
function getUserPreferences(int $salarie_id): array
{
    if ($salarie_id <= 0) {
        return [];
    }

    $sql = "SELECT preference_key FROM personne_preferences WHERE personne_id = :salarie_id";
    $stmt = executeQuery($sql, [':salarie_id' => $salarie_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
