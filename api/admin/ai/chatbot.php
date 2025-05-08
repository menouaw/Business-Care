<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/request_builder.php';
require_once __DIR__ . '/google_ai_client.php';

$input = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? [];
$stream = $input['stream'] ?? false;


$requestBody = buildChatCompletionRequest($messages, $stream);


$url = "https://" . API_HOST . "/" . API_PATH;


$accessToken = getGoogleAccessToken();


$responseData = callGoogleAI($url, $accessToken, $requestBody);


header('Content-Type: application/json');
echo json_encode($responseData);