<?php
require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Récupère les statistiques principales pour le tableau de bord Salarié.
 *
 * @param int $salarie_id L'ID du salarié connecté.
 * @return array Tableau des statistiques ['prochains_rdv', 'notifications_non_lues', 'pack_info'].
 */
function getSalarieDashboardStats(int $salarie_id): array
{
    if ($salarie_id <= 0) {
        return [
            'prochains_rdv' => 0,
            'notifications_non_lues' => 0,
            'pack_info' => null
        ];
    }


    $employee = fetchOne('personnes', 'id = :id AND role_id = :role_id', [
        ':id' => $salarie_id,
        ':role_id' => ROLE_SALARIE
    ]);

    $pack_info = null;
    if ($employee && $employee['entreprise_id']) {

        $contract = fetchOne(
            'contrats',
            'entreprise_id = :entreprise_id AND statut = :statut AND (date_fin IS NULL OR date_fin >= CURDATE())',
            [
                ':entreprise_id' => $employee['entreprise_id'],
                ':statut' => 'actif'
            ],
            'date_debut DESC'
        );

        if ($contract) {

            $service = fetchOne('services', 'id = :id', [':id' => $contract['service_id']]);
            if ($service) {

                $usage_stats = [
                    'chatbot' => [
                        'used' => 0,
                        'total' => $service['chatbot_questions_limite']
                    ],
                    'consultations' => ['used' => 0, 'total' => $service['rdv_medicaux_inclus'] ?? 0],
                    'ateliers' => ['used' => 0, 'total' => $service['activites_incluses'] ?? 0]
                ];


                $sql_consultations = "SELECT COUNT(*) FROM " . TABLE_APPOINTMENTS . "
                                    WHERE personne_id = :salarie_id 
                                    AND prestation_id IN (
                                        SELECT id FROM " . TABLE_PRESTATIONS . "
                                        WHERE type = 'consultation'
                                    )
                                    AND statut IN ('termine', 'confirme')
                                    AND date_rdv >= :date_debut";
                $stmt_consultations = executeQuery($sql_consultations, [
                    ':salarie_id' => $salarie_id,
                    ':date_debut' => $contract['date_debut']
                ]);
                $usage_stats['consultations']['used'] = (int)$stmt_consultations->fetchColumn();


                $sql_ateliers = "SELECT (
                                    
                                    (SELECT COUNT(*) FROM " . TABLE_APPOINTMENTS . "
                                    WHERE personne_id = :salarie_id_rdv 
                                    AND prestation_id IN (
                                        SELECT id FROM " . TABLE_PRESTATIONS . "
                                        WHERE type = 'atelier'
                                    )
                                    AND statut IN ('termine', 'confirme')
                                    AND date_rdv >= :date_debut_rdv)
                                    +
                                    
                                    (SELECT COUNT(*) FROM evenement_inscriptions ei
                                    JOIN evenements e ON ei.evenement_id = e.id
                                    WHERE ei.personne_id = :salarie_id_event
                                    AND e.organise_par_bc = TRUE
                                    AND e.date_debut >= :date_debut_event
                                    AND ei.statut = 'inscrit')
                                ) as total_ateliers";
                $stmt_ateliers = executeQuery($sql_ateliers, [
                    ':salarie_id_rdv' => $salarie_id,
                    ':date_debut_rdv' => $contract['date_debut'],
                    ':salarie_id_event' => $salarie_id,
                    ':date_debut_event' => $contract['date_debut']
                ]);
                $usage_stats['ateliers']['used'] = (int)$stmt_ateliers->fetchColumn();

                $pack_info = [
                    'type' => $service['type'],
                    'date_debut' => $contract['date_debut'],
                    'date_fin' => $contract['date_fin'],
                    'usage_stats' => $usage_stats,
                    'chatbot_questions_limite' => $service['chatbot_questions_limite'] ?? null,
                    'conseils_hebdo_personnalises' => $service['conseils_hebdo_personnalises'] ?? false,
                    'rdv_medicaux_supplementaires_prix' => $service['rdv_medicaux_supplementaires_prix'] ?? null
                ];
            }
        }
    }


    $sqlRdv = "SELECT COUNT(id) FROM " . TABLE_APPOINTMENTS . "
               WHERE personne_id = :salarie_id
               AND date_rdv > NOW()
               AND statut IN ('planifie', 'confirme')";
    $stmtRdv = executeQuery($sqlRdv, [':salarie_id' => $salarie_id]);
    $prochains_rdv = (int) $stmtRdv->fetchColumn();


    $notifications_non_lues = getUnreadNotificationCount($salarie_id);

    return [
        'prochains_rdv' => $prochains_rdv,
        'notifications_non_lues' => $notifications_non_lues,
        'pack_info' => $pack_info
    ];
}

/**
 * Récupère les prochains rendez-vous d'un salarié.
 *
 * @param int $salarie_id L'ID du salarié.
 * @param int $limit Nombre maximum de rendez-vous à retourner.
 * @return array Tableau des prochains rendez-vous.
 */
function getUpcomingAppointments(int $salarie_id, int $limit = 5): array
{
    if ($salarie_id <= 0) {
        return [];
    }


    $sql = "SELECT rdv.id, rdv.date_rdv, rdv.statut,
                   pres.nom as prestation_nom,
                   CONCAT(prat.prenom, ' ', prat.nom) as praticien_nom
            FROM " . TABLE_APPOINTMENTS . " rdv
            LEFT JOIN " . TABLE_PRESTATIONS . " pres ON rdv.prestation_id = pres.id
            LEFT JOIN " . TABLE_USERS . " prat ON rdv.praticien_id = prat.id AND prat.role_id = :role_prestataire
            WHERE rdv.personne_id = :salarie_id
            AND rdv.date_rdv > NOW()
            AND rdv.statut IN ('planifie', 'confirme')
            ORDER BY rdv.date_rdv ASC
            LIMIT :limit";

    $params = [
        ':salarie_id' => $salarie_id,
        ':limit' => $limit,
        ':role_prestataire' => ROLE_PRESTATAIRE
    ];

    return executeQuery($sql, $params)->fetchAll();
}
