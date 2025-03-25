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
 * Journalise un événement de sécurité
 * 
 * @param int|null $userId ID de l'utilisateur concerné
 * @param string $action Type d'action de sécurité
 * @param string $details Informations supplémentaires
 * @param bool $isFailure Indique si l'opération a échoué
 * @return int|false ID du journal créé ou false en cas d'échec
 */
function logSecurityEvent($userId, $action, $details = '', $isFailure = false) {
    $securityPrefix = $isFailure ? 'securite_echec' : 'securite';
    return logActivity($userId, $securityPrefix . ':' . $action, $details);
}

/**
 * Enregistre une opération métier en ajoutant automatiquement le préfixe "operation:" au type d'opération fourni.
 * 
 * Le paramètre $userId représente l'identifiant de l'utilisateur concerné et peut être null si l'opération n'est pas associée à un utilisateur.
 * Le paramètre $action doit être fourni sans le préfixe, celui-ci étant ajouté automatiquement.
 *
 * @param int|null $userId Identifiant de l'utilisateur concerné (ou null)
 * @param string $action Type d'opération métier (sans le préfixe "operation:")
 * @param string $details Informations supplémentaires concernant l'opération
 * @return int|false ID du journal créé ou false en cas d'échec
 */
function logBusinessOperation($userId, $action, $details = '') {
    return logActivity($userId, 'operation:' . $action, $details);
} 