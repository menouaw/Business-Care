<?php
// API principale pour Business Care

// definit les en-tetes pour permettre l'acces API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// verifie si c'est une requete OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// recupere le chemin de la requete
$requestPath = isset($_GET['path']) ? $_GET['path'] : '';
$pathSegments = explode('/', trim($requestPath, '/'));

// recupere le module demande
$module = isset($pathSegments[0]) ? $pathSegments[0] : '';
$action = isset($pathSegments[1]) ? $pathSegments[1] : '';
$id = isset($pathSegments[2]) ? $pathSegments[2] : null;

// charge le fichier de configuration de base de donnees
require_once __DIR__ . '/../web-admin/includes/config.php';
require_once __DIR__ . '/../web-admin/includes/db.php';
require_once __DIR__ . '/../web-admin/includes/functions.php';

// verifie l'authentification (si necessaire)
$isAuthenticated = false;
$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
    // TODO: implementer la verification du token
    $isAuthenticated = true; // temporaire: considere toute requete avec token comme authentifiee
}

// gere la requete en fonction du module demande
switch ($module) {
    case 'users':
        require_once __DIR__ . '/admin/users.php';
        break;
    case 'companies':
        require_once __DIR__ . '/admin/companies.php';
        break;
    case 'contracts':
        require_once __DIR__ . '/admin/contracts.php';
        break;
    case 'services':
        require_once __DIR__ . '/admin/services.php';
        break;
    case 'auth':
        require_once __DIR__ . '/admin/auth.php';
        break;
    default:
        // API non trouvee
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'API endpoint non trouve'
        ]);
        break;
} 