<?php
require_once __DIR__ . '/../../../../shared/web-client/db.php';

/**
 * Récupère les notifications pour un utilisateur donné.
 *
 * @param int $user_id L'ID de l'utilisateur.
 * @param int $limit Nombre maximum de notifications à récupérer.
 * @param bool $show_read Inclure aussi les notifications lues.
 * @return array Liste des notifications.
 */
function getNotificationsForUser(int $user_id, int $limit = 50, bool $show_read = true): array
{
    if ($user_id <= 0) {
        return [];
    }
    $where = 'personne_id = :user_id';
    if (!$show_read) {
        $where .= ' AND lu = 0';
    }
    $params = [':user_id' => $user_id];
    $orderBy = 'created_at DESC';

    return fetchAll('notifications', $where, $orderBy, $limit, 0, $params);
}

/**
 * Marque une notification spécifique comme lue pour un utilisateur.
 *
 * @param int $notification_id L'ID de la notification.
 * @param int $user_id L'ID de l'utilisateur (pour vérification).
 * @return bool True si la mise à jour a réussi, false sinon.
 */
function markNotificationAsRead(int $notification_id, int $user_id): bool
{
    if ($notification_id <= 0 || $user_id <= 0) {
        return false;
    }
    $updated = updateRow(
        'notifications',
        ['lu' => 1, 'date_lecture' => date('Y-m-d H:i:s')],
        'id = :id AND personne_id = :user_id AND lu = 0',
        [':id' => $notification_id, ':user_id' => $user_id]
    );
    return $updated > 0;
}

/**
 * Marque toutes les notifications non lues d'un utilisateur comme lues.
 *
 * @param int $user_id L'ID de l'utilisateur.
 * @return int Le nombre de notifications mises à jour.
 */
function markAllNotificationsAsRead(int $user_id): int
{
    if ($user_id <= 0) {
        return 0;
    }
    return updateRow(
        'notifications',
        ['lu' => 1, 'date_lecture' => date('Y-m-d H:i:s')],
        'personne_id = :user_id AND lu = 0',
        [':user_id' => $user_id]
    );
}

/**
 * Compte le nombre de notifications non lues pour un utilisateur.
 *
 * @param int $user_id L'ID de l'utilisateur.
 * @return int Le nombre de notifications non lues.
 */
function getUnreadNotificationCount(int $user_id): int
{
    if ($user_id <= 0) {
        return 0;
    }
    return countTableRows('notifications', 'personne_id = :user_id AND lu = 0', [':user_id' => $user_id]);
}
