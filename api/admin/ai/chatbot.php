<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/request_builder.php';
require_once __DIR__ . '/google_ai_client.php';

$input = json_decode(file_get_contents('php://input'), true);
$userMessages = $input['messages'] ?? [];
$stream = $input['stream'] ?? false;

$requestBody = buildChatCompletionRequest($userMessages, $stream);

$apiMethod = $stream ? GENERATE_CONTENT_API_METHOD : str_replace('stream', '', GENERATE_CONTENT_API_METHOD);
if (!$stream && GENERATE_CONTENT_API_METHOD === 'streamGenerateContent') {
    $apiMethod = 'generateContent';
} elseif ($stream && GENERATE_CONTENT_API_METHOD === 'generateContent') {
    $apiMethod = 'streamGenerateContent';
}

$url = "https://" . API_ENDPOINT_HOST . "/" . API_PATH_PREFIX . MODEL_ID . ":" . $apiMethod;

$accessToken = getGoogleAccessToken();

$responseData = callGoogleAI($url, $accessToken, $requestBody);

header('Content-Type: application/json');
echo json_encode($responseData);