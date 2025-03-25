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
 * Enregistre un événement de sécurité dans le système de journalisation en préfixant l'action
 * avec "securite_echec" si l'opération a échoué, ou "securite" sinon.
 *
 * @param int|null $userId Identifiant de l'utilisateur concerné.
 * @param string $action Type d'action de sécurité.
 * @param string $details Informations supplémentaires.
 * @param bool $isFailure Indique si l'opération a échoué.
 * @return int|false Identifiant du journal créé ou false en cas d'échec.
 */
function logSecurityEvent($userId, $action, $details = '', $isFailure = false) {
    $securityPrefix = $isFailure ? 'securite_echec' : 'securite';
    return logActivity($userId, $securityPrefix . ':' . $action, $details);
}

/**
 * Journalise une opération métier.
 *
 * La fonction enregistre une opération en ajoutant automatiquement le préfixe "operation:" au type d'action fourni.
 *
 * @param int|null $userId Identifiant de l'utilisateur concerné.
 * @param string $action Type d'opération métier.
 * @param string $details Informations supplémentaires sur l'opération.
 * @return int|false ID du journal créé ou false en cas d'échec.
 */
function logBusinessOperation($userId, $action, $details = '') {
    return logActivity($userId, 'operation:' . $action, $details);
} 