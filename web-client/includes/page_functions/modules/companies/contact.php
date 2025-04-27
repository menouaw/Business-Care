<?php
require_once __DIR__ . '/../../../../../shared/web-client/db.php';

/**
 * Enregistre un nouveau message de contact/support dans la base de données.
 *
 * @param int|null $entreprise_id L'ID de l'entreprise (si applicable).
 * @param int|null $personne_id L'ID de la personne envoyant le message.
 * @param string $sujet Le sujet du message.
 * @param string $message Le contenu du message.
 * @return int|false L'ID du nouveau ticket créé ou false en cas d'échec.
 */
function saveContactMessage(?int $entreprise_id, ?int $personne_id, string $sujet, string $message): int|false
{
    if (empty($sujet) || empty($message)) {
        return false;
    }

    $insertData = [
        'entreprise_id' => $entreprise_id,
        'personne_id' => $personne_id,
        'sujet' => $sujet,
        'message' => $message,
        'statut' => 'nouveau'
    ];

    return insertRow('support_tickets', $insertData);
}
