<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère les prestations (services) assignées à un prestataire spécifique.
 *
 * Cette fonction interroge la table `prestataires_prestations` pour trouver les ID des prestations
 * liées au prestataire, puis récupère les détails complets de ces prestations depuis la table `prestations`.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return array Un tableau contenant la liste des prestations assignées, chacune étant un tableau associatif
 *               avec les détails de la prestation. Retourne un tableau vide si le prestataire n'a
 *               aucun service assigné ou si l'ID est invalide.
 */
function getProviderAssignedServices(int $provider_id): array
{
    if ($provider_id <= 0) {
        return [];
    }


    $sql_assigned_ids = "SELECT prestation_id FROM prestataires_prestations WHERE prestataire_id = :provider_id";
    $stmt_ids = executeQuery($sql_assigned_ids, [':provider_id' => $provider_id]);
    $prestation_ids = $stmt_ids ? $stmt_ids->fetchAll(PDO::FETCH_COLUMN) : [];

    if (empty($prestation_ids)) {

        return [];
    }



    $placeholders = implode(',', array_fill(0, count($prestation_ids), '?'));

    $sql_services = "SELECT
                        p.id,
                        p.nom,
                        p.description,
                        p.duree,
                        p.type,
                        p.categorie,
                        p.niveau_difficulte,
                        p.capacite_max,
                        p.materiel_necessaire,
                        p.prerequis,
                        p.prix -- Note: Le prix ici est indicatif, la facturation prestataire est gérée ailleurs
                    FROM prestations p
                    WHERE p.id IN ({$placeholders})
                    ORDER BY p.categorie, p.nom";


    $stmt_services = executeQuery($sql_services, $prestation_ids);

    return $stmt_services ? $stmt_services->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Formate le type de prestation pour un affichage plus lisible.
 *
 * @param string|null $type Le type brut de la base de données.
 * @return string Le type formaté.
 */
function formatServiceType(?string $type): string
{
    return match (strtolower($type ?? '')) {
        'consultation' => 'Consultation individuelle',
        'atelier' => 'Atelier en groupe',
        'webinar' => 'Webinar / Visioconférence',
        'conference' => 'Conférence',

        default => ucfirst($type ?? 'Autre'),
    };
}
