<?php

/**
 * Service centralisé pour la journalisation des activités dans l'application
 */
require_once 'db.php';

/**
 * Journalise une activité dans le système
 * 
 * @param int|null $userId ID de l'utilisateur concerné
 * @param string $action Type d'action réalisée
 * @param string $details Informations supplémentaires
 * @return int|false ID du journal créé ou false en cas d'échec
 */
function logActivity($userId, $action, $details = '')
{
    $data = [
        'personne_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    return insertRow('logs', $data);
}

/**
 * Journalise une activité système sans utilisateur spécifique
 * 
 * @param string $action Type d'action système
 * @param string $details Informations supplémentaires
 * @return int|false ID du journal créé ou false en cas d'échec
 */
function logSystemActivity($action, $details = '')
{
    return logActivity(null, $action, $details);
}

/**
 * Journalise un événement de sécurité
 *
 * @param int|null $userId Identifiant de l'utilisateur concerné
 * @param string $action Type d'action de sécurité
 * @param string $details Informations supplémentaires
 * @param bool $isFailure Indique si l'opération a échoué
 * @return int|false Identifiant du journal créé ou false en cas d'échec
 */
function logSecurityEvent($userId, $action, $details = '', $isFailure = false)
{
    $securityPrefix = $isFailure ? '[SECURITY FAILURE]' : '[SECURITY]';
    return logActivity($userId, $securityPrefix . ':' . $action, $details);
}

/**
 * Enregistre une opération métier dans le journal central.
 *
 * Cette fonction logue une opération métier en préfixant automatiquement le type d'opération avec "[BUSINESS OPERATION]" pour standardiser les entrées.
 *
 * @param int|null $userId Identifiant de l'utilisateur concerné (peut être null si non applicable).
 * @param string $action Description ou type de l'opération métier.
 * @param string $details Informations complémentaires sur l'opération.
 * @return int|false L'ID de l'entrée de journal créée ou false en cas d'échec.
 */
function logBusinessOperation($userId, $action, $details = '')
{
    return logActivity($userId, '[BUSINESS OPERATION]' . $action, $details);
}

/**
 * Journalise une activité de réservation
 * 
 * @param int $userId ID de l'utilisateur qui réserve
 * @param int $prestationId ID de la prestation réservée
 * @param string $action Type d'action (creation, modification, annulation)
 * @param string $details Détails supplémentaires
 * @return int|false ID du journal créé ou false en cas d'échec
 */
function logReservationActivity($userId, $prestationId, $action, $details = '')
{
    $actionPrefix = 'reservation:' . $action;
    $fullDetails = "prestation_id: $prestationId, " . $details;
    return logActivity($userId, $actionPrefix, $fullDetails);
}

/**
 * Journalise une activité de paiement
 * 
 * @param int $userId ID de l'utilisateur qui effectue le paiement
 * @param string $refTransaction Référence de la transaction
 * @param float $montant Montant du paiement
 * @param string $statut Statut du paiement
 * @return int|false ID du journal créé ou false en cas d'échec
 */
function logPaymentActivity($userId, $refTransaction, $montant, $statut)
{
    $details = "reference: $refTransaction, montant: $montant, statut: $statut";
    return logActivity($userId, '[PAYMENT]' . $details);
}
