<?php
require_once '../../includes/page_functions/modules/providers.php';

requireRole(ROLE_ADMIN);

$action = $_POST['action'] ?? '';
$provider_id = isset($_POST['provider_id']) ? (int)$_POST['provider_id'] : 0;
$habilitation_id = isset($_POST['habilitation_id']) || isset($_POST['id']) ? (int)($_POST['habilitation_id'] ?? $_POST['id'] ?? 0) : 0;
$prestation_id = isset($_POST['prestation_id']) ? (int)$_POST['prestation_id'] : 0;
$csrf_token = $_POST['csrf_token'] ?? '';

$redirectUrl = WEBADMIN_URL . '/modules/providers/view.php?id=' . $provider_id;
$success = false;
$message = 'Action non reconnue ou données manquantes.';

if (!validateToken($csrf_token)) {
    flashMessage('Erreur de sécurité ou session expirée. Veuillez réessayer.', 'danger');
    redirectTo($redirectUrl);
}

if (empty($action)) {
    flashMessage('Aucune action spécifiée.', 'warning');
    redirectTo($redirectUrl);
}

try {
    switch ($action) {
        case 'add_habilitation':
            if ($provider_id > 0) {
                $habData = $_POST; 
                unset($habData['action'], $habData['provider_id'], $habData['csrf_token']); 
                $newId = addProviderHabilitation($provider_id, $habData);
                if ($newId) {
                    $success = true;
                    $message = 'Habilitation ajoutée avec succès.';
                    $redirectUrl .= '&tab=habilitations'; 
                } else {
                    $message = 'Échec de l\'ajout de l\'habilitation.';
                }
            } else {
                $message = 'ID de prestataire invalide pour ajouter une habilitation.';
            }
            break;

        case 'edit_habilitation':
            if ($habilitation_id > 0 && $provider_id > 0) {
                $habData = $_POST;
                unset($habData['action'], $habData['provider_id'], $habData['habilitation_id'], $habData['csrf_token']);
                $affectedRows = updateProviderHabilitation($habilitation_id, $habData);
                if ($affectedRows > 0) {
                    $success = true;
                    $message = 'Habilitation mise à jour avec succès.';
                } else {
                    $message = 'Aucune modification détectée ou erreur lors de la mise à jour.';
                    $success = true; 
                }
                $redirectUrl .= '&tab=habilitations';
            } else {
                $message = 'ID d\'habilitation ou de prestataire invalide pour la modification.';
            }
            break;

        case 'delete_habilitation':
             if ($habilitation_id > 0 && $provider_id > 0) {
                $affectedRows = deleteProviderHabilitation($habilitation_id);
                if ($affectedRows > 0) {
                    $success = true;
                    $message = 'Habilitation supprimée avec succès.';
                } else {
                    $message = 'Impossible de supprimer l\'habilitation.';
                }
                 $redirectUrl .= '&tab=habilitations';
            } else {
                $message = 'ID d\'habilitation invalide pour la suppression.';
            }
            break;
            
        case 'verify_habilitation':
            if ($habilitation_id > 0 && $provider_id > 0) {
                 $affectedRows = updateHabilitationStatus($habilitation_id, HABILITATION_STATUS_VERIFIED);
                 if ($affectedRows > 0) {
                     $success = true;
                     $message = 'Habilitation validée.';
                 } else {
                     $message = 'Impossible de valider l\'habilitation.';
                 }
                  $redirectUrl .= '&tab=habilitations';
             } else {
                 $message = 'ID d\'habilitation invalide pour la validation.';
             }
             break;

        case 'reject_habilitation':
             if ($habilitation_id > 0 && $provider_id > 0) {
                 $affectedRows = updateHabilitationStatus($habilitation_id, HABILITATION_STATUS_REJECTED);
                 if ($affectedRows > 0) {
                     $success = true;
                     $message = 'Habilitation rejetée.';
                 } else {
                     $message = 'Impossible de rejeter l\'habilitation.';
                 }
                  $redirectUrl .= '&tab=habilitations';
             } else {
                 $message = 'ID d\'habilitation invalide pour le rejet.';
             }
             break;

        case 'assign_prestation':
            if ($provider_id > 0 && $prestation_id > 0) {
                $result = assignPrestationToProvider($provider_id, $prestation_id);
                if ($result === true) { 
                    $success = true;
                    $message = 'Prestation déjà assignée.';
                } elseif ($result !== false) { 
                    $success = true;
                    $message = 'Prestation assignée avec succès.';
                } else { 
                     $message = 'Impossible d\'assigner la prestation.';
                }
                 $redirectUrl .= '&tab=prestations';
            } else {
                $message = 'ID de prestataire ou de prestation invalide.';
            }
            break;

        case 'remove_prestation':
             if ($provider_id > 0 && $prestation_id > 0) {
                 $affectedRows = removePrestationFromProvider($provider_id, $prestation_id);
                 if ($affectedRows > 0) {
                     $success = true;
                     $message = 'Assignation de la prestation retirée.';
                 } else {
                     $message = 'Impossible de retirer l\'assignation de la prestation.';
                 }
                  $redirectUrl .= '&tab=prestations';
             } else {
                 $message = 'ID de prestataire ou de prestation invalide pour le retrait.';
             }
             break;

        default:
            $message = 'Action non supportée: ' . htmlspecialchars($action);
            break;
    }
} catch (Exception $e) {
    $message = 'Erreur technique: ' . $e->getMessage();
    logSecurityEvent($_SESSION['user_id'] ?? 0, 'provider_actions_error', 
        "[ERROR] Action: {$action}, Provider: {$provider_id}, Error: " . $e->getMessage());
}

flashMessage($message, $success ? 'success' : 'danger');
redirectTo($redirectUrl);
?>
