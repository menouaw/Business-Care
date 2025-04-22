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

    // Vérification : le salarié appartient-il bien à l'entreprise ?
    $employee = fetchOne('personnes', 'id = :id AND entreprise_id = :company_id AND role_id = :role_salarie', [
        ':id' => $employee_id,
        ':company_id' => $company_id,
        ':role_salarie' => ROLE_SALARIE
    ]);

    if (!$employee) {
        // Salarié non trouvé ou n'appartient pas à cette entreprise
        return false;
    }

    // Mettre à jour le statut en 'actif'
    $updatedRows = updateRow(
        'personnes',
        ['statut' => 'actif'], // La donnée à mettre à jour
        'id = :id',            // La condition WHERE
        [':id' => $employee_id] // Le paramètre pour la condition WHERE
    );

    return $updatedRows > 0;
}
