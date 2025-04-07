<?php

header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../shared/web-client/db.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['signalement_message'] = ['type' => 'danger', 'text' => 'Accès non autorisé. Authentification requise.'];
    header('Location: /Business-Care/web-client/modules/employees/signalement.php'); // Rediriger vers le formulaire
    exit;
}
$current_user_id = $_SESSION['user_id'];
$entreprise_id = $_SESSION['entreprise_id'] ?? null;


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    // On pourrait aussi rediriger avec un message d'erreur
    $_SESSION['signalement_message'] = ['type' => 'danger', 'text' => 'Méthode non autorisée.'];
    header('Location: /Business-Care/web-client/modules/employees/signalement.php');
    exit;
}

$description = trim($_POST['description'] ?? '');
$categorie = !empty($_POST['categorie']) ? trim($_POST['categorie']) : null;
$anonyme = isset($_POST['anonyme']);

if (empty($description)) {
    $_SESSION['signalement_message'] = ['type' => 'danger', 'text' => 'La description est obligatoire et ne peut pas être vide.'];
    // Idéalement, on renverrait aussi les données saisies pour pré-remplir le formulaire
    header('Location: /Business-Care/web-client/modules/employees/signalement.php');
    exit;
}

$personne_id_to_insert = $anonyme ? null : $current_user_id;
$entreprise_id_to_insert = $entreprise_id; // Ou $anonyme ? null : $entreprise_id;

$message = null; // Pour stocker le message de succès/erreur

try {
    $pdo = getDbConnection();

    $sql = "INSERT INTO signalements (entreprise_id, personne_id, categorie, description, date_signalement, statut, priorite)
            VALUES (:entreprise_id, :personne_id, :categorie, :description, NOW(), :statut, :priorite)";

    $stmt = $pdo->prepare($sql);

    $default_statut = 'nouveau';
    $default_priorite = 'moyenne';

    $stmt->bindParam(':entreprise_id', $entreprise_id_to_insert, $entreprise_id_to_insert === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindParam(':personne_id', $personne_id_to_insert, $personne_id_to_insert === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindParam(':categorie', $categorie, $categorie === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':statut', $default_statut, PDO::PARAM_STR);
    $stmt->bindParam(':priorite', $default_priorite, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $message = ['type' => 'success', 'text' => 'Signalement enregistré avec succès.'];
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Erreur PDO lors de l'insertion du signalement: Code=" . $errorInfo[0] . ", DriverCode=" . $errorInfo[1] . ", Message=" . $errorInfo[2]);
        $message = ['type' => 'danger', 'text' => 'Erreur lors de l\'enregistrement du signalement.'];
    }
} catch (PDOException $e) {
    error_log("Erreur de base de données (PDO): " . $e->getMessage() . " dans " . $e->getFile() . ":" . $e->getLine());
    $message = ['type' => 'danger', 'text' => 'Erreur de base de données. Veuillez contacter l\'administrateur.'];
} catch (Exception $e) {
    error_log("Erreur générale: " . $e->getMessage() . " dans " . $e->getFile() . ":" . $e->getLine());
    $message = ['type' => 'danger', 'text' => 'Une erreur inattendue est survenue.'];
}

// Stocker le message dans la session et rediriger
$_SESSION['signalement_message'] = $message;
header('Location: /Business-Care/web-client/modules/employees/signalement.php');
exit;
