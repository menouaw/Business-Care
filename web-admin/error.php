<?php
$statusCode = isset($_GET['code']) ? (int)$_GET['code'] : 500; 

http_response_code($statusCode);

$errorMessages = [
    400 => ['title' => 'Mauvaise requête', 'message' => 'La requête ne peut pas être interprétée par le serveur en raison d\'une syntaxe incorrecte.'],
    401 => ['title' => 'Non autorisé', 'message' => 'L\'authentification est requise et a échoué ou n\'a pas encore été fournie.'],
    403 => ['title' => 'Interdit', 'message' => 'Vous n\'avez pas les permissions pour accéder à cette ressource.'],
    404 => ['title' => 'Non trouvé', 'message' => 'La page ou la ressource que vous avez demandée n\'a pas été trouvée.'],
    500 => ['title' => 'Erreur interne du serveur', 'message' => 'Une erreur inattendue est survenue sur le serveur. Veuillez réessayer plus tard.'],
    502 => ['title' => 'Mauvais portail', 'message' => 'Le serveur a reçu une réponse invalide d\'un serveur supérieur.'],
    503 => ['title' => 'Service indisponible', 'message' => 'Le serveur est actuellement incapable de traiter la requête en raison d\'une surcharge temporaire ou d\'une maintenance.'],
    504 => ['title' => 'Délai de dépassement', 'message' => 'Le serveur n\'a pas reçu de réponse à temps d\'un serveur supérieur.'],
];

$errorDetails = $errorMessages[$statusCode] ?? $errorMessages[500];
$pageTitle = "Erreur " . $statusCode . ": " . $errorDetails['title'];
$errorMessage = $errorDetails['message'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="container mt-5"> 
        <div class="card shadow">
            <div class="card-body text-center">
                <h1 class="card-title text-danger"><?php echo htmlspecialchars($errorDetails['title']) . ' (' . $statusCode . ')'; ?></h1>
                <p class="card-text"><?php echo htmlspecialchars($errorMessage); ?></p>
                <hr>
                <a href="/admin/index.php" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>
