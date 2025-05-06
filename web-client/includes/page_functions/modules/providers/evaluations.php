<?php

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Récupère les évaluations reçues par un prestataire pour ses prestations, avec pagination.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param int $limit Nombre d'éléments par page.
 * @param int $offset Décalage pour la pagination.
 * @return array Contient ['evaluations' => array, 'total' => int].
 */
function getProviderEvaluations(int $provider_id, int $limit = 10, int $offset = 0): array
{
    $result = ['evaluations' => [], 'total' => 0];
    if ($provider_id <= 0) {
        return $result;
    }

    
    $stmt_ids = executeQuery(
        "SELECT prestation_id FROM prestataires_prestations WHERE prestataire_id = :provider_id",
        [':provider_id' => $provider_id]
    );
    $prestation_ids = $stmt_ids ? $stmt_ids->fetchAll(PDO::FETCH_COLUMN) : [];

    if (empty($prestation_ids)) {
        return $result; 
    }

    $placeholders = implode(',', array_fill(0, count($prestation_ids), '?'));

    
    $sql_count = "SELECT COUNT(*) FROM evaluations e WHERE e.prestation_id IN ({$placeholders})";
    $stmt_count = executeQuery($sql_count, $prestation_ids);
    $total_evaluations = $stmt_count ? (int)$stmt_count->fetchColumn() : 0;
    $result['total'] = $total_evaluations;

    if ($total_evaluations === 0) {
        return $result; 
    }

    
    $sql_evaluations = "SELECT 
                e.id, 
                e.note, 
                e.commentaire, 
                e.date_evaluation, 
                p.nom as prestation_nom, 
                per.prenom as salarie_prenom, 
                per.nom as salarie_nom
            FROM evaluations e
            JOIN prestations p ON e.prestation_id = p.id
            LEFT JOIN personnes per ON e.personne_id = per.id
            WHERE e.prestation_id IN ({$placeholders})
            ORDER BY e.date_evaluation DESC
            LIMIT ? OFFSET ?";

    
    
    
    $params = $prestation_ids;
    $params[] = (int)$limit;
    $params[] = (int)$offset;

    
    $stmt_evaluations = executeQuery($sql_evaluations, $params);
    $result['evaluations'] = $stmt_evaluations ? $stmt_evaluations->fetchAll(PDO::FETCH_ASSOC) : [];

    return $result;
}

/**
 * Formate une note sous forme d'étoiles HTML.
 *
 * @param int $note La note (1 à 5).
 * @return string Le HTML des étoiles.
 */
function formatRatingStars(int $note): string
{
    
    $output = '';
    $maxStars = 5;
    for ($i = 1; $i <= $maxStars; $i++) {
        if ($i <= $note) {
            $output .= '<i class="fas fa-star text-warning"></i>'; 
        } else {
            $output .= '<i class="far fa-star text-muted"></i>'; 
        }
    }
    return $output;
}
