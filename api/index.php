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

// charge les fichiers partagés
require_once __DIR__ . '/../shared/web-admin/config.php';
require_once __DIR__ . '/../shared/web-admin/db.php';
require_once __DIR__ . '/../shared/web-admin/functions.php';

// verifie l'authentification (si necessaire)
$isAuthenticated = false;
$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
    $isAuthenticated = true; // temporaire: considere toute requete avec jeton comme authentifiee
}

// gere la requete en fonction du module demande
switch ($module) {
    // routes pour l'API admin
    case 'admin':
        switch ($action) {
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
                // API admin non trouvee
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'API admin endpoint non trouve'
                ]);
                break;
        }
        break;
        
    // routes pour l'API client
    case 'client':
        switch ($action) {
            case 'auth':
                require_once __DIR__ . '/client/auth.php';
                break;
            case 'profile':
                require_once __DIR__ . '/client/profile.php';
                break;
            case 'services':
                require_once __DIR__ . '/client/services.php';
                break;
            case 'contracts':
                require_once __DIR__ . '/client/contracts.php';
                break;
            case 'appointments':
                require_once __DIR__ . '/client/appointments.php';
                break;
            default:
                // API client non trouvee
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'API client endpoint non trouve'
                ]);
                break;
        }
        break;
        
    // compatibilite avec l'ancien code
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