<?php



header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

header('Referrer-Policy: no-referrer-when-downgrade');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


$requestPath = isset($_GET['path']) ? $_GET['path'] : '';
$pathSegments = explode('/', trim($requestPath, '/'));


$module = isset($pathSegments[0]) ? $pathSegments[0] : '';
$action = isset($pathSegments[1]) ? $pathSegments[1] : '';
$id = isset($pathSegments[2]) ? $pathSegments[2] : null;


require_once __DIR__ . '/../shared/web-admin/config.php';
require_once __DIR__ . '/../shared/web-admin/db.php';
require_once __DIR__ . '/../shared/web-admin/functions.php';


$isAuthenticated = false;
$adminUserId = null;
$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
$token = null;

if (preg_match('/Bearer\\s(\\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
    
    
    
    
    
    
    
    list($isAuthenticated, $adminUserId) = validateApiAdminToken($token);
}



/*
function validateAdminToken($token) {
    
    
    
    
    
    
    
    
    if (!empty($token)) { 
         
         return [true, 1]; 
    }
    return [false, null];
}
*/


switch ($module) {
    
    case 'admin':
        
        if ($action !== 'auth' && !$isAuthenticated) {
             http_response_code(401);
             echo json_encode(['error' => true, 'message' => 'Authentification requise']);
             exit;
        }

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
            case 'events': 
                require_once __DIR__ . '/admin/events.php';
                break;
            case 'invoices': 
                 require_once __DIR__ . '/admin/invoices.php';
                 break;
            case 'quotes': 
                 require_once __DIR__ . '/admin/quotes.php';
                break;
            case 'auth':
                require_once __DIR__ . '/admin/auth.php';
                break;
            default:
                
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'API admin endpoint non trouve'
                ]);
                break;
        }
        break;
        
    
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
                
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'API client endpoint non trouve'
                ]);
                break;
        }
        break;
        
    default:
        
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'API endpoint non trouve'
        ]);
        break;
} 