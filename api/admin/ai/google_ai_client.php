<?php

function callGoogleAI($url, $accessToken, $requestBody) {
    $headers = [
        'Content-Type: application/json',
        "Authorization: Bearer $accessToken"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        http_response_code(500);
        echo json_encode(["error" => "Erreur cURL: " . $error_msg]);
        exit;
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($httpcode !== 200) {
        http_response_code($httpcode);
        error_log("Requête API échouée. Statut: " . $httpcode . " Réponse: " . $response);
        echo json_encode(["error" => "Requête API échouée", "status_code" => $httpcode, "réponse" => $responseData]);
        exit;
    }

    return $responseData;
}
