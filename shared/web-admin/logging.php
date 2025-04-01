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
function logActivity($userId, $action, $details = '') {
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
function logSystemActivity($action, $details = '') {
    return logActivity(null, $action, $details);
}

/**
 * Journalise un événement de sécurité.
 *
 * Enregistre une action de sécurité dans le système de journalisation en préfixant l'action avec "[SECURITY FAILURE]"
 * si l'opération a échoué (lorsque $isFailure est true), ou avec "[SECURITY]" sinon.
 *
 * @param int|null $userId Identifiant de l'utilisateur concerné.
 * @param string $action Type d'action de sécurité.
 * @param string $details Informations complémentaires sur l'événement.
 * @param bool $isFailure Indique si l'opération a échoué.
 * @return int|false Identifiant du log créé ou false en cas d'échec.
 */
function logSecurityEvent($userId, $action, $details = '', $isFailure = false) {
    $securityPrefix = $isFailure ? '[SECURITY FAILURE]' : '[SECURITY]';
    return logActivity($userId, $securityPrefix . ':' . $action, $details);
}

/**
 * Journalise une opération métier.
 *
 * Enregistre une opération métier en ajoutant le préfixe "[BUSINESS OPERATION]" au libellé de l'action fournie.
 *
 * @param int|null $userId Identifiant de l'utilisateur concerné (ou null si non applicable).
 * @param string $action Libellé de l'opération métier.
 * @param string $details Informations complémentaires sur l'opération.
 * @return int|false L'identifiant du log créé ou false en cas d'échec.
 */
function logBusinessOperation($userId, $action, $details = '') {
    return logActivity($userId, '[BUSINESS OPERATION]' . $action, $details);
} 