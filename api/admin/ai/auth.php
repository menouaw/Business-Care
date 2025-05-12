<?php
require_once __DIR__ . '/config.php';

use Google\Auth\ApplicationDefaultCredentials;

function getGoogleAccessToken(array $scopes = [
    'https://www.googleapis.com/auth/cloud-platform',
    'https://www.googleapis.com/auth/generative-language.inference'
]) {
    try {
        $credentials = ApplicationDefaultCredentials::getCredentials($scopes);
        $authResponse = $credentials->fetchAuthToken();
        if (isset($authResponse['access_token'])) {
            return $authResponse['access_token'];
        }
        throw new Exception("Jeton d'accès non trouvé dans la réponse d'authentification.");
    } catch (Exception $e) {
        error_log("Authentification échouée: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Authentification échouée.", "details" => $e->getMessage()]);
        exit;
    }
}
