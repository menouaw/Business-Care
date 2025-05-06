<?php

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Récupère la liste des prestataires accessibles à un employé donné.
 * Un prestataire est considéré comme accessible si :
 * 1. Il a le rôle 'prestataire' et est 'actif'.
 * 2. Il propose au moins une prestation ('prestations') qui est incluse 
 *    dans le contrat ('contrats') actif de l'entreprise ('entreprises') de l'employé.
 *
 * @param int $employee_id L'ID de l'employé (personnes.id).
 * @return array Liste des prestataires accessibles, chacun contenant au moins [id, nom, prenom, photo_url]. Peut être vide.
 */
function getAvailableProvidersForEmployee(int $employee_id): array
{
    if ($employee_id <= 0) {
        return [];
    }


    $employee = fetchOne('personnes', 'id = :id AND role_id = :role_id', [':id' => $employee_id, ':role_id' => ROLE_SALARIE]);
    if (!$employee || !$employee['entreprise_id']) {

        return [];
    }
    $entreprise_id = $employee['entreprise_id'];




    $contract = fetchOne(
        'contrats',
        'entreprise_id = :entreprise_id AND statut = :statut AND (date_fin IS NULL OR date_fin >= CURDATE())',
        [':entreprise_id' => $entreprise_id, ':statut' => 'actif'],
        'date_debut DESC'
    );

    if (!$contract) {

        return [];
    }
    $contract_id = $contract['id'];


    $allowed_prestation_ids_stmt = executeQuery(
        "SELECT prestation_id FROM contrats_prestations WHERE contrat_id = :contract_id",
        [':contract_id' => $contract_id]
    );
    $allowed_prestation_ids = $allowed_prestation_ids_stmt ? $allowed_prestation_ids_stmt->fetchAll(PDO::FETCH_COLUMN) : [];

    if (empty($allowed_prestation_ids)) {

        return [];
    }


    $placeholders = implode(',', array_fill(0, count($allowed_prestation_ids), '?'));

    $sql = "SELECT DISTINCT
                p.id,
                p.nom,
                p.prenom,
                p.photo_url
            FROM personnes p
            JOIN prestataires_prestations pp ON p.id = pp.prestataire_id
            WHERE p.role_id = ? 
              AND p.statut = ?
              AND pp.prestation_id IN ({$placeholders})
            ORDER BY p.nom ASC, p.prenom ASC";

    $final_params = array_merge(
        [ROLE_PRESTATAIRE, 'actif'],
        $allowed_prestation_ids
    );

    $stmt_providers = executeQuery($sql, $final_params);

    return $stmt_providers ? $stmt_providers->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Récupère les détails complets d'un profil prestataire spécifique pour affichage à un employé.
 * Inclut les informations de base, les spécialités, les habilitations vérifiées et la note moyenne.
 * Vérifie également si le prestataire est bien accessible à l'employé.
 *
 * @param int $provider_id L'ID du prestataire dont on veut le profil.
 * @param int $employee_id L'ID de l'employé qui consulte le profil.
 * @return array|null Les détails du profil ou null si non trouvé ou accès non autorisé.
 */
function getProviderProfileDetailsForEmployee(int $provider_id, int $employee_id): ?array
{
    if ($provider_id <= 0 || $employee_id <= 0) {
        return null;
    }



    $available_providers = getAvailableProvidersForEmployee($employee_id);
    $is_accessible = false;
    foreach ($available_providers as $prov) {
        if ($prov['id'] == $provider_id) {
            $is_accessible = true;
            break;
        }
    }

    if (!$is_accessible) {


        return null;
    }


    $provider_details = fetchOne('personnes', 'id = :id AND role_id = :role_id AND statut = :statut', [
        ':id' => $provider_id,
        ':role_id' => ROLE_PRESTATAIRE,
        ':statut' => 'actif'
    ]);

    if (!$provider_details) {

        return null;
    }


    $sql_specialties = "SELECT pr.nom, pr.description 
                        FROM prestations pr
                        JOIN prestataires_prestations pp ON pr.id = pp.prestation_id
                        WHERE pp.prestataire_id = :provider_id
                        ORDER BY pr.nom ASC";
    $stmt_specialties = executeQuery($sql_specialties, [':provider_id' => $provider_id]);
    $provider_details['specialties'] = $stmt_specialties ? $stmt_specialties->fetchAll(PDO::FETCH_ASSOC) : [];


    $sql_habilitations = "SELECT type, nom_document, organisme_emission, date_obtention, date_expiration
                          FROM habilitations
                          WHERE prestataire_id = :provider_id AND statut = :statut_verifiee
                          ORDER BY date_obtention DESC";
    $stmt_habilitations = executeQuery($sql_habilitations, [
        ':provider_id' => $provider_id,
        ':statut_verifiee' => 'verifiee'
    ]);
    $provider_details['habilitations'] = $stmt_habilitations ? $stmt_habilitations->fetchAll(PDO::FETCH_ASSOC) : [];



    $sql_avg_rating = "SELECT AVG(e.note) as average_rating, COUNT(e.id) as total_ratings
                       FROM evaluations e
                       WHERE e.prestation_id IN (
                           SELECT DISTINCT pp.prestation_id
                           FROM prestataires_prestations pp
                           WHERE pp.prestataire_id = :provider_id
                       )";
    $stmt_avg_rating = executeQuery($sql_avg_rating, [':provider_id' => $provider_id]);
    $rating_data = $stmt_avg_rating ? $stmt_avg_rating->fetch(PDO::FETCH_ASSOC) : ['average_rating' => null, 'total_ratings' => 0];
    $provider_details['average_rating'] = $rating_data['average_rating'] ? round($rating_data['average_rating'], 1) : null;
    $provider_details['total_ratings'] = (int)$rating_data['total_ratings'];



    return $provider_details;
}

/**
 * Récupère la liste des prestations qu'un employé spécifique peut réserver avec un prestataire donné.
 * Conditions :
 * 1. La prestation est proposée par le prestataire (prestataires_prestations).
 * 2. La prestation est incluse dans le contrat actif de l'entreprise de l'employé (contrats_prestations).
 * 3. La prestation est de type 'consultation' ou autre type réservable individuellement (à définir).
 *
 * @param int $employee_id L'ID de l'employé.
 * @param int $provider_id L'ID du prestataire.
 * @return array Liste des prestations réservables [id, nom, description, duree, prix].
 */
function getEmployeeBookablePrestationsForProvider(int $employee_id, int $provider_id): array
{
    if ($employee_id <= 0 || $provider_id <= 0) {
        return [];
    }


    $employee = fetchOne('personnes', 'id = :id AND role_id = :role_id', [':id' => $employee_id, ':role_id' => ROLE_SALARIE]);
    if (!$employee || !$employee['entreprise_id']) {
        return [];
    }
    $entreprise_id = $employee['entreprise_id'];


    $contract = fetchOne(
        'contrats',
        'entreprise_id = :entreprise_id AND statut = :statut AND (date_fin IS NULL OR date_fin >= CURDATE())',
        [':entreprise_id' => $entreprise_id, ':statut' => 'actif'],
        'date_debut DESC'
    );
    if (!$contract) {
        return [];
    }
    $contract_id = $contract['id'];


    $allowed_prestation_ids_stmt = executeQuery(
        "SELECT prestation_id FROM contrats_prestations WHERE contrat_id = :contract_id",
        [':contract_id' => $contract_id]
    );
    $allowed_prestation_ids = $allowed_prestation_ids_stmt ? $allowed_prestation_ids_stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    if (empty($allowed_prestation_ids)) {
        return [];
    }


    $allowed_placeholders = implode(',', array_fill(0, count($allowed_prestation_ids), '?'));




    $bookable_types = ['consultation'];
    $type_placeholders = implode(',', array_fill(0, count($bookable_types), '?'));

    $sql = "SELECT DISTINCT pr.id, pr.nom, pr.description, pr.duree, pr.prix
            FROM prestations pr
            JOIN prestataires_prestations pp ON pr.id = pp.prestation_id
            WHERE pp.prestataire_id = ? 
              AND pr.id IN ({$allowed_placeholders})
              AND pr.type IN ({$type_placeholders})
            ORDER BY pr.nom ASC";


    $final_params = array_merge(
        [$provider_id],
        $allowed_prestation_ids,
        $bookable_types
    );

    $stmt = executeQuery($sql, $final_params);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}
