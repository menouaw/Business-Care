<?php

require_once __DIR__ . '/../../../../shared/web-client/db.php';

/**
 * Récupère la liste des salariés actifs pour une entreprise donnée.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array La liste des salariés (ou un tableau vide si aucun).
 */
function getCompanyEmployees(int $entreprise_id): array
{
    if ($entreprise_id <= 0) {
        return [];
    }


    $sql = "SELECT 
                p.id, 
                p.nom, 
                p.prenom, 
                p.email, 
                p.telephone, 
                p.statut, 
                p.derniere_connexion, 
                s.nom as site_nom 
            FROM 
                personnes p
            LEFT JOIN 
                sites s ON p.site_id = s.id
            WHERE 
                p.entreprise_id = :entreprise_id 
            AND 
                p.role_id = :role_salarie
            -- AND 
            --    p.statut = 'actif' -- Décommenter pour ne voir que les actifs
            ORDER BY 
                p.nom ASC, p.prenom ASC";


    $stmt = executeQuery($sql, [
        ':entreprise_id' => $entreprise_id,
        ':role_salarie' => ROLE_SALARIE
    ]);

    return $stmt->fetchAll();
}

/**
 * Désactive (marque comme 'inactif') un salarié appartenant à une entreprise spécifique.
 *
 * @param int $employee_id L'ID du salarié à désactiver.
 * @param int $company_id L'ID de l'entreprise propriétaire du salarié (pour vérification).
 * @return bool Retourne true si la désactivation a réussi, false sinon.
 */
function deactivateEmployee(int $employee_id, int $company_id): bool
{
    if ($employee_id <= 0 || $company_id <= 0) {
        return false;
    }


    $employee = fetchOne('personnes', 'id = :id AND entreprise_id = :company_id AND role_id = :role_salarie', [
        ':id' => $employee_id,
        ':company_id' => $company_id,
        ':role_salarie' => ROLE_SALARIE
    ]);

    if (!$employee) {

        return false;
    }


    $updatedRows = updateRow(
        'personnes',
        ['statut' => 'inactif'],
        'id = :id',
        [':id' => $employee_id]
    );

    return $updatedRows > 0;
}

/**
 * Réactive (marque comme 'actif') un salarié appartenant à une entreprise spécifique.
 *
 * @param int $employee_id L'ID du salarié à réactiver.
 * @param int $company_id L'ID de l'entreprise propriétaire du salarié (pour vérification).
 * @return bool Retourne true si la réactivation a réussi, false sinon.
 */
function reactivateEmployee(int $employee_id, int $company_id): bool
{
    if ($employee_id <= 0 || $company_id <= 0) {
        return false;
    }


    $employee = fetchOne('personnes', 'id = :id AND entreprise_id = :company_id AND role_id = :role_salarie', [
        ':id' => $employee_id,
        ':company_id' => $company_id,
        ':role_salarie' => ROLE_SALARIE
    ]);

    if (!$employee) {

        return false;
    }


    $updatedRows = updateRow(
        'personnes',
        ['statut' => 'actif'],
        'id = :id',
        [':id' => $employee_id]
    );

    return $updatedRows > 0;
}

/**
 * Récupère les détails complets d'un salarié spécifique appartenant à une entreprise.
 *
 * @param int $employee_id L'ID du salarié.
 * @param int $company_id L'ID de l'entreprise pour vérification.
 * @return array|false Les détails du salarié ou false si non trouvé ou n'appartient pas à l'entreprise.
 */
function getEmployeeDetails(int $employee_id, int $company_id): array|false
{
    if ($employee_id <= 0 || $company_id <= 0) {
        return false;
    }

    return fetchOne(
        'personnes',
        'id = :id AND entreprise_id = :company_id AND role_id = :role_salarie',
        [
            ':id' => $employee_id,
            ':company_id' => $company_id,
            ':role_salarie' => ROLE_SALARIE
        ]
    );
}

/**
 * Récupère la liste des sites pour une entreprise donnée.
 *
 * @param int $company_id L'ID de l'entreprise.
 * @return array La liste des sites (id, nom).
 */
function getCompanySites(int $company_id): array
{
    if ($company_id <= 0) {
        return [];
    }

    $sql = "SELECT id, nom FROM sites WHERE entreprise_id = :company_id ORDER BY nom ASC";
    $stmt = executeQuery($sql, [':company_id' => $company_id]);
    return $stmt->fetchAll();
}

/**
 * Met à jour les détails d'un salarié.
 *
 * @param int $employee_id L'ID du salarié à mettre à jour.
 * @param int $company_id L'ID de l'entreprise (pour vérification).
 * @param array $data Les données à mettre à jour.
 * @return bool True si la mise à jour réussit, false sinon.
 */
function updateEmployeeDetails(int $employee_id, int $company_id, array $data): bool
{
    if ($employee_id <= 0 || $company_id <= 0 || empty($data)) {
        return false;
    }

    $employee = getEmployeeDetails($employee_id, $company_id);
    if (!$employee) {
        return false;
    }

    unset($data['statut'], $data['role_id'], $data['entreprise_id'], $data['mot_de_passe']);

    if (empty($data)) {
        return false;
    }

    $updatedRows = updateRow(
        'personnes',
        $data,
        'id = :id',
        [':id' => $employee_id]
    );

    return $updatedRows >= 0;
}
