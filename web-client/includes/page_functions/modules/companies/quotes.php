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
 * Récupère la liste de toutes les prestations disponibles avec leur prix.
 * TODO: Ajouter potentiellement un filtre pour les prestations "actives" ou pertinentes pour les devis.
 *
 * @return array La liste des prestations (id, nom, description, prix, type, categorie).
 */
function getAvailablePrestationsWithPrices(): array
{

    $sql = "SELECT
                id,
                nom,
                description,
                prix,
                type,
                categorie  
            FROM
                prestations
            ORDER BY
                categorie ASC, nom ASC";

    $stmt = executeQuery($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

/**
 * Traite la soumission du formulaire de demande de devis.
 * Gère la sélection d'un pack standard OU d'un devis personnalisé basé sur les quantités,
 * OU une combinaison des deux (Pack de base + Prestations supplémentaires).
 */
function handleQuoteRequestPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_quote_request'])) {
        return;
    }

    $entreprise_id = $_SESSION['user_entreprise'] ?? 0;
    if ($entreprise_id <= 0) {
        flashMessage('Impossible d\'identifier votre entreprise.', 'danger');
        redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
        exit;
    }

    $selected_pack_id = filter_input(INPUT_POST, 'selected_pack_id', FILTER_VALIDATE_INT);
    $quantities_input = $_POST['quantities'] ?? [];
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_SPECIAL_CHARS);

    $extras = [];
    if (is_array($quantities_input)) {
        foreach ($quantities_input as $prestation_id => $qty) {
            $prestation_id_filtered = filter_var($prestation_id, FILTER_VALIDATE_INT);
            $qty_filtered = filter_var($qty, FILTER_VALIDATE_INT);

            if ($prestation_id_filtered && $qty_filtered && $qty_filtered > 0) {
                $extras[$prestation_id_filtered] = $qty_filtered;
            }
        }
    }

    if (empty($selected_pack_id) && empty($extras)) {
        flashMessage('Veuillez sélectionner un pack ou ajouter au moins une prestation supplémentaire.', 'warning');
        redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php?action=request');
        exit;
    }

    $pdo = getDBConnection();
    $pdo->beginTransaction();

    $pack_montant_ht = 0;
    $pack_service_id = null;
    if ($selected_pack_id > 0) {
        $sql_pack = "SELECT tarif_annuel_par_salarie FROM services WHERE id = :id AND actif = 1";
        $stmt_pack = executeQuery($sql_pack, [':id' => $selected_pack_id], $pdo);
        $pack_data = $stmt_pack->fetch();

        if ($pack_data) {
            $pack_montant_ht = (float)($pack_data['tarif_annuel_par_salarie'] ?? 0);
            $pack_service_id = $selected_pack_id;
        } else {
            $pdo->rollBack();
            flashMessage("Le pack sélectionné n'est pas valide ou n'est plus disponible.", 'danger');
            redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php?action=request');
            exit;
        }
    }

    $extras_montant_ht = 0;
    $extras_details_for_db = [];
    if (!empty($extras)) {
        $prestation_ids = array_keys($extras);
        $placeholders = rtrim(str_repeat('?,', count($prestation_ids)), ',');
        $sql_prices = "SELECT id, prix FROM prestations WHERE id IN ($placeholders)";
        $stmt_prices = executeQuery($sql_prices, $prestation_ids, $pdo);
        $prestation_prices = $stmt_prices->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($extras as $prestation_id => $qty) {
            if (isset($prestation_prices[$prestation_id])) {
                $prix_unitaire = (float)$prestation_prices[$prestation_id];
                $extras_montant_ht += $prix_unitaire * $qty;
                $extras_details_for_db[] = [
                    'prestation_id' => $prestation_id,
                    'quantite' => $qty,
                    'prix_unitaire_devis' => $prix_unitaire
                ];
            } else {
                error_log("[WARNING] handleQuoteRequestPost: Prestation ID {$prestation_id} (extra) demandée mais non trouvée.");
            }
        }
    }

    $total_montant_ht = $pack_montant_ht + $extras_montant_ht;
    $tva_rate = defined('TVA_RATE') ? TVA_RATE : 0.20;
    $tva_percent = $tva_rate * 100;
    $total_montant_ttc = $total_montant_ht * (1 + $tva_rate);
    $statut = QUOTE_STATUS_PENDING;

    $insertDataDevis = [
        'entreprise_id' => $entreprise_id,
        'service_id' => $pack_service_id,
        'date_creation' => date('Y-m-d H:i:s'),
        'statut' => $statut,
        'montant_total' => $total_montant_ttc,
        'montant_ht' => $total_montant_ht,
        'tva' => $tva_percent,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $insertSuccess = insertRow('devis', $insertDataDevis);

    if (!$insertSuccess) {
        $pdo->rollBack();
        flashMessage("Échec de la création de l'enregistrement principal du devis.", 'danger');
        redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php?action=request');
        exit;
    }

    $newQuoteId = $pdo->lastInsertId();

    if (!$newQuoteId || $newQuoteId <= 0) {
        $pdo->rollBack();
        error_log("[ERROR] handleQuoteRequestPost: Impossible de récupérer l'ID du devis après insertion réussie.");
        flashMessage("Impossible de récupérer l'ID du devis nouvellement créé.", 'danger');
        redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php?action=request');
        exit;
    }

    if (!empty($extras_details_for_db)) {
        foreach ($extras_details_for_db as $ligne) {
            $insertDataLigne = [
                'devis_id' => $newQuoteId,
                'prestation_id' => $ligne['prestation_id'],
                'quantite' => $ligne['quantite'],
                'prix_unitaire_devis' => $ligne['prix_unitaire_devis'],
            ];

            $insertLineSuccess = insertRow('devis_prestations', $insertDataLigne);
            if (!$insertLineSuccess) {
                $pdo->rollBack();
                flashMessage("Échec de l'insertion d'une ligne de prestation supplémentaire pour le devis ID {$newQuoteId}.", 'danger');
                redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php?action=request');
                exit;
            }
        }
    }

    $pdo->commit();

    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id) {
        $quote_link = WEBCLIENT_URL . '/modules/companies/quotes.php?action=view&id=' . $newQuoteId;
        $notif_title = 'Demande de devis enregistrée';
        $notif_message = "Votre demande de devis (N°{$newQuoteId}) a bien été enregistrée.";
        if ($pack_service_id && !empty($extras_details_for_db)) {
            $notif_message .= " Elle inclut un pack de base et des prestations supplémentaires.";
        } elseif ($pack_service_id) {
            $notif_message .= " Elle est basée sur un pack standard.";
        } else {
            $notif_message .= " Elle est basée sur des prestations personnalisées.";
        }
        $notif_message .= " Elle est en attente de validation.";

        createNotification(
            $user_id,
            $notif_title,
            $notif_message,
            'info',
            $quote_link
        );
    }

    flashMessage('Votre demande de devis (N°' . $newQuoteId . ') a été envoyée avec succès et est en cours de traitement.', 'success');
    redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php');
    exit;
}

/**
 * Récupère les packs de services disponibles avec leurs détails et les prestations incluses.
 *
 * @return array Liste des packs avec leurs détails.
 *               Chaque pack est un tableau associatif contenant 'id', 'type', 'description', etc.,
 *               et 'prestations' (une liste de tableaux associatifs ['nom' => ..., 'quantite' => ..., 'description' => ...]).
 */
function getDetailedServicePacks(): array
{

    $sql_packs = "SELECT 
                    id, 
                    type, 
                    description, 
                    max_effectif_inferieur_egal, 
                    activites_incluses, 
                    rdv_medicaux_inclus, 
                    chatbot_questions_limite, 
                    conseils_hebdo_personnalises, 
                    tarif_annuel_par_salarie
                FROM 
                    services 
                WHERE 
                    actif = 1
                ORDER BY 
                    ordre ASC, id ASC";

    $stmt_packs = executeQuery($sql_packs);
    $packs = $stmt_packs->fetchAll(PDO::FETCH_ASSOC);


    if (!$packs) {
        return [];
    }

    return $packs;
}
