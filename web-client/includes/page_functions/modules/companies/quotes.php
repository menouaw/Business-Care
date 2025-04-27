<?php
require_once __DIR__ . '/../../../init.php';


/**
 * Récupère la liste des devis pour une entreprise donnée.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array La liste des devis (ou un tableau vide si aucun).
 */
function getCompanyQuotes(int $entreprise_id): array
{
    if ($entreprise_id <= 0) {
        return [];
    }


    $sql = "SELECT
                d.id,
                d.date_creation,
                d.date_validite,
                d.montant_total,
                d.statut,
                s.type as service_nom, 
                d.updated_at
            FROM
                devis d
            LEFT JOIN
                services s ON d.service_id = s.id 
            WHERE
                d.entreprise_id = :entreprise_id
            ORDER BY
                d.date_creation DESC";

    $stmt = executeQuery($sql, [':entreprise_id' => $entreprise_id]);
    return $stmt->fetchAll();
}

/**
 * Récupère les détails complets d'un devis spécifique appartenant à une entreprise.
 * Inclut les prestations associées au devis.
 *
 * @param int $quote_id L'ID du devis.
 * @param int $company_id L'ID de l'entreprise pour vérification.
 * @return array|false Les détails du devis (incluant un tableau 'prestations') ou false si non trouvé ou n'appartient pas à l'entreprise.
 */
function getQuoteDetails(int $quote_id, int $company_id): array|false
{
    if ($quote_id <= 0 || $company_id <= 0) {
        return false;
    }


    $sql_quote = "SELECT
                    d.*,
                    s.type as service_nom
                  FROM
                    devis d
                  LEFT JOIN
                    services s ON d.service_id = s.id
                  WHERE
                    d.id = :quote_id
                  AND
                    d.entreprise_id = :company_id";

    $stmt_quote = executeQuery($sql_quote, [
        ':quote_id' => $quote_id,
        ':company_id' => $company_id
    ]);
    $quote = $stmt_quote->fetch();

    if (!$quote) {
        return false;
    }


    $sql_prestations = "SELECT
                            p.id as prestation_id,
                            p.nom as prestation_nom,
                            p.description as prestation_description,
                            dp.quantite,
                            dp.prix_unitaire_devis,
                            dp.description_specifique
                        FROM
                            devis_prestations dp
                        JOIN
                            prestations p ON dp.prestation_id = p.id
                        WHERE
                            dp.devis_id = :quote_id";

    $stmt_prestations = executeQuery($sql_prestations, [':quote_id' => $quote_id]);
    $quote['prestations'] = $stmt_prestations->fetchAll();

    return $quote;
}

/**
 * Récupère la liste des services/packs actifs disponibles pour les devis.
 *
 * @return array La liste des services (id, type).
 */
function getAvailableServicePacks(): array
{

    $sql = "SELECT id, type FROM services WHERE actif = 1 ORDER BY ordre ASC, type ASC";
    $stmt = executeQuery($sql);
    return $stmt->fetchAll();
}

/**
 * Enregistre une nouvelle demande de devis dans la base de données.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @param array $data Données de la demande (ex: ['notes' => '...', 'service_id' => 1 ou null]).
 * @return int|false L'ID du nouveau devis créé ou false en cas d'échec.
 */
function saveQuoteRequest(int $entreprise_id, array $data): int|false
{
    if ($entreprise_id <= 0 || empty($data['notes'])) {
        return false;
    }

    $service_id = $data['service_id'] ?? null;
    $montant_total = 0;
    $montant_ht = 0;
    $statut = QUOTE_STATUS_CUSTOM_REQUEST;
    $tva = defined('TVA_RATE') ? TVA_RATE * 100 : 20.0;

    if ($service_id) {

        $service = fetchOne('services', 'id = :id', [':id' => $service_id]);
        if ($service && isset($service['prix_base_indicatif']) && $service['prix_base_indicatif'] !== null) {
            $montant_total = (float)$service['prix_base_indicatif'];

            $montant_ht = $montant_total / (1 + (float)($tva / 100));
            $statut = QUOTE_STATUS_PENDING;
        } else {

            error_log("[WARNING] saveQuoteRequest: Service ID {$service_id} sélectionné mais non trouvé ou prix indicatif manquant.");
            $service_id = null;
        }
    }

    $insertData = [
        'entreprise_id' => $entreprise_id,
        'service_id' => $service_id,
        'notes_negociation' => $data['notes'],
        'date_creation' => date('Y-m-d'),
        'statut' => $statut,
        'montant_total' => $montant_total,
        'montant_ht' => $montant_ht,
        'tva' => $tva,

    ];

    $newQuoteId = insertRow('devis', $insertData);

    if ($newQuoteId) {
        $user_id = $_SESSION['user_id'] ?? null;
        if ($user_id) {
            $quote_link = WEBCLIENT_URL . '/modules/companies/quotes.php?action=view&id=' . $newQuoteId;
            $notif_title = 'Demande de devis enregistrée';
            $notif_message = "Votre demande de devis (ID: {$newQuoteId}) a bien été enregistrée.";
            if ($insertData['statut'] === QUOTE_STATUS_CUSTOM_REQUEST) {
                $notif_message .= " Elle est en cours de traitement par nos équipes.";
            } else {
                $notif_message .= " Vous pouvez le consulter.";
            }

            createNotification(
                $user_id,
                $notif_title,
                $notif_message,
                'success',
                $quote_link
            );
        }
    }

    return $newQuoteId;
}

function getQuoteStatusBadgeClass($status)
{
    return match (strtolower($status)) {
        QUOTE_STATUS_PENDING => 'warning',
        QUOTE_STATUS_ACCEPTED => 'success',
        QUOTE_STATUS_REFUSED => 'danger',
        QUOTE_STATUS_EXPIRED => 'secondary',
        default => 'light',
    };
}
