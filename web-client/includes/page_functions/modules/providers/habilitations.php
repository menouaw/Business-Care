<?php

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Récupère toutes les habilitations enregistrées pour un prestataire donné.
 *
 * @param int $provider_id L'ID du prestataire (personnes.id).
 * @return array La liste des habilitations (peut être vide).
 */
function getProviderHabilitations(int $provider_id): array
{
    if ($provider_id <= 0) {
        return [];
    }


    $tableName = 'habilitations';

    return fetchAll(
        $tableName,
        'prestataire_id = :provider_id',
        'date_obtention DESC, nom_document ASC',
        0,
        0,
        [':provider_id' => $provider_id]
    );
}

/**
 * Retourne la classe CSS Bootstrap pour le badge de statut de l'habilitation.
 *
 * @param string $status Le statut de l'habilitation.
 * @return string La classe CSS du badge.
 */
function getHabilitationStatusBadgeClass(string $status): string
{
    $status = strtolower($status);
    return match ($status) {
        'verifiee' => 'success',
        'en_attente_validation' => 'info',
        'rejetee' => 'danger',
        'expiree' => 'warning',
        default => 'secondary',
    };
}

/**
 * Ajoute une nouvelle habilitation pour un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $data Données du formulaire (type, nom_document, organisme_emission, date_obtention, date_expiration).
 * @param array|null $fileData Données du fichier uploadé depuis $_FILES['document_file'] (peut être null).
 * @return bool True si l'ajout réussit, false sinon.
 */
function addProviderHabilitation(int $provider_id, array $data, ?array $fileData): bool
{
    if ($provider_id <= 0 || empty($data['type']) || empty($data['nom_document'])) {
        flashMessage("Le type et le nom du document sont obligatoires.", "danger");
        return false;
    }


    $date_obtention = !empty($data['date_obtention']) ? date('Y-m-d', strtotime($data['date_obtention'])) : null;
    $date_expiration = !empty($data['date_expiration']) ? date('Y-m-d', strtotime($data['date_expiration'])) : null;

    if (!empty($data['date_obtention']) && !$date_obtention) {
        flashMessage("Format de date d'obtention invalide.", "danger");
        return false;
    }
    if (!empty($data['date_expiration']) && !$date_expiration) {
        flashMessage("Format de date d'expiration invalide.", "danger");
        return false;
    }
    if ($date_obtention && $date_expiration && $date_expiration < $date_obtention) {
        flashMessage("La date d'expiration ne peut pas être antérieure à la date d'obtention.", "danger");
        return false;
    }

    $document_url = null;


    if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {

        $uploadDir = realpath(__DIR__ . '/../../../../../uploads/habilitations');
        if (!$uploadDir) {

            $baseUploadDir = realpath(__DIR__ . '/../../../../../uploads');
            if ($baseUploadDir && is_writable($baseUploadDir)) {
                $uploadDir = $baseUploadDir . '/habilitations';
                if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                    error_log("[ERROR] Impossible de créer le dossier d'upload: {$uploadDir}");
                    flashMessage("Erreur serveur lors de la préparation de l'upload.", "danger");
                    return false;
                }
            } else {
                error_log("[ERROR] Le dossier d'upload de base n'existe pas ou n'est pas accessible en écriture: " . (__DIR__ . '/../../../../../uploads'));
                flashMessage("Erreur de configuration serveur pour l'upload.", "danger");
                return false;
            }
        } elseif (!is_writable($uploadDir)) {
            error_log("[ERROR] Le dossier d'upload n'est pas accessible en écriture: {$uploadDir}");
            flashMessage("Erreur serveur d'écriture pour l'upload.", "danger");
            return false;
        }

        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $maxSize = 5 * 1024 * 1024;

        $fileMimeType = mime_content_type($fileData['tmp_name']);

        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            flashMessage("Type de fichier non autorisé. Formats acceptés: PDF, JPG, PNG, DOC, DOCX.", "danger");
            return false;
        }

        if ($fileData['size'] > $maxSize) {
            flashMessage("Le fichier est trop volumineux (Max 5 Mo).", "danger");
            return false;
        }


        $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

        $fileExtension = match ($fileMimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => $fileExtension
        };

        $safeFileName = 'provider_' . $provider_id . '_hab_' . time() . '.' . $fileExtension;
        $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;


        $relativePath = (defined('UPLOAD_URL') ? rtrim(parse_url(UPLOAD_URL, PHP_URL_PATH), '/') : '/uploads') . '/habilitations/' . $safeFileName;

        if (move_uploaded_file($fileData['tmp_name'], $destinationPath)) {
            $document_url = $relativePath;
            logSecurityEvent($provider_id, 'habilitation_upload', '[SUCCESS] Upload fichier: ' . $relativePath);
        } else {
            error_log("[ERROR] Échec du déplacement du fichier uploadé vers {$destinationPath} pour provider ID: {$provider_id}");
            flashMessage("Impossible de sauvegarder le fichier uploadé.", "danger");
            return false;
        }
    }


    $dataToInsert = [
        'prestataire_id' => $provider_id,
        'type' => $data['type'],
        'nom_document' => trim($data['nom_document'] ?? ''),
        'organisme_emission' => trim($data['organisme_emission'] ?? ''),
        'date_obtention' => $date_obtention,
        'date_expiration' => $date_expiration,
        'document_url' => $document_url,
        'statut' => 'en_attente_validation'
    ];

    $tableName = 'habilitations';
    $success = insertRow($tableName, $dataToInsert);

    if ($success) {
        logSecurityEvent($provider_id, 'habilitation_add', '[SUCCESS] Ajout habilitation: ' . $data['nom_document']);
        flashMessage("Habilitation ajoutée avec succès. Elle est en attente de validation.", "success");
        return true;
    } else {
        logSecurityEvent($provider_id, 'habilitation_add', '[FAILURE] Echec ajout habilitation: ' . $data['nom_document']);
        flashMessage("Erreur lors de l'enregistrement de l'habilitation.", "danger");

        if ($document_url && isset($destinationPath) && file_exists($destinationPath)) {
            unlink($destinationPath);
            error_log("[INFO] Fichier uploadé {$destinationPath} supprimé suite à échec d'insertion BDD.");
        }
        return false;
    }
}

/**
 * Gère la soumission du formulaire d'ajout d'habilitation.
 * Appelé depuis la page module lorsque la requête est POST.
 * Gère la validation CSRF, la récupération des données, l'appel à addProviderHabilitation
 * et la redirection.
 *
 * @param int $provider_id L'ID du prestataire soumettant la requête.
 * @return void Termine le script par une redirection.
 */
function handleHabilitationAddRequest(int $provider_id): void
{

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add_habilitation'])) {

        flashMessage("Requête invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/providers/habilitations.php');
        exit;
    }


    verifyPostedCsrfToken();


    $habilitationData = [
        'type' => filter_input(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS),
        'nom_document' => filter_input(INPUT_POST, 'nom_document', FILTER_SANITIZE_SPECIAL_CHARS),
        'organisme_emission' => filter_input(INPUT_POST, 'organisme_emission', FILTER_SANITIZE_SPECIAL_CHARS),
        'date_obtention' => filter_input(INPUT_POST, 'date_obtention'),
        'date_expiration' => filter_input(INPUT_POST, 'date_expiration')
    ];


    $fileData = null;
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileData = $_FILES['document_file'];
    }


    if (addProviderHabilitation($provider_id, $habilitationData, $fileData)) {
    } else {
    }


    redirectTo(WEBCLIENT_URL . '/modules/providers/habilitations.php');
    exit;
}

/**
 * Supprime une habilitation spécifique pour un prestataire.
 * La suppression n'est autorisée que si le statut est 'en_attente_validation'.
 * Supprime également le fichier associé s'il existe.
 *
 * @param int $habilitation_id L'ID de l'habilitation à supprimer.
 * @param int $provider_id L'ID du prestataire propriétaire.
 * @return bool True si la suppression réussit, false sinon.
 */
function deleteProviderHabilitation(int $habilitation_id, int $provider_id): bool
{
    if ($habilitation_id <= 0 || $provider_id <= 0) {
        flashMessage("ID invalide pour la suppression.", "danger");
        return false;
    }

    $tableName = 'habilitations'; 

    
    $habilitation = fetchOne(
        $tableName,
        'id = :id AND prestataire_id = :provider_id',
        [':id' => $habilitation_id, ':provider_id' => $provider_id]
    );

    if (!$habilitation) {
        flashMessage("Habilitation non trouvée ou accès refusé.", "warning");
        return false;
    }

    if ($habilitation['statut'] !== 'en_attente_validation') {
        flashMessage("Impossible de supprimer une habilitation qui n'est plus en attente de validation.", "warning");
        return false;
    }

    
    $fileDeleted = true; 
    if (!empty($habilitation['document_url'])) {
        
              
        
        
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/../../../../../'); 
        $filePath = $documentRoot . $habilitation['document_url'];
        $filePath = str_replace('//', '/', $filePath); 

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                logSecurityEvent($provider_id, 'habilitation_file_delete', '[SUCCESS] Fichier supprimé: ' . $filePath);
                $fileDeleted = true;
            } else {
                error_log("[ERROR] Échec de la suppression du fichier {$filePath} pour habilitation ID {$habilitation_id}");
                flashMessage("Erreur lors de la suppression du fichier associé. L'entrée BDD n'a pas été supprimée.", "danger");
                $fileDeleted = false;
                return false; 
            }
        } else {
            error_log("[WARNING] Fichier associé à l'habilitation ID {$habilitation_id} non trouvé pour suppression: {$filePath}");
            
        }
    }

    
    $rowsAffected = deleteRow($tableName, 'id = :id', [':id' => $habilitation_id]);

    if ($rowsAffected > 0) {
        logSecurityEvent($provider_id, 'habilitation_delete', '[SUCCESS] Habilitation ID supprimée: ' . $habilitation_id);
        flashMessage("Habilitation supprimée avec succès.", "success");
        return true;
    } else {
        logSecurityEvent($provider_id, 'habilitation_delete', '[FAILURE] Échec suppression BDD habilitation ID: ' . $habilitation_id);
        flashMessage("Erreur lors de la suppression de l'habilitation de la base de données.", "danger");
        
        return false;
    }
}


