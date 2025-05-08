<?php


require_once __DIR__ . '/../../vendor/autoload.php';


use Psr\Cache\CacheItemPoolInterface;

use GuzzleHttp\Client;


use Google\Auth\ApplicationDefaultCredentials;



$projectId = "esgi-bc";
$locationId = "us-central1"; 
$modelId = "gemini-2.5-pro-preview-05-06"; 

$generateContentApi = "generateContent"; 

$apiEndpoint = "$locationId-aiplatform.googleapis.com";
$url = "https://$apiEndpoint/v1/projects/$projectId/locations/$locationId/publishers/google/models/$modelId:$generateContentApi";


$accessToken = null;
$headers = [];

try {
    
    $scopes = [
        'https://www.googleapis.com/auth/cloud-platform',
        'https://www.googleapis.com/auth/generative-language.inference', 
    ];

    
    
    
    
    
    $credentials = ApplicationDefaultCredentials::getCredentials($scopes);


    
    
    
    
    $authResponse = $credentials->fetchAuthToken();


    if (isset($authResponse['access_token'])) {
        $accessToken = $authResponse['access_token'];
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer $accessToken",
        ];
    } else {
         throw new \Exception("Access token not found in authentication response array.");
    }

} catch (\Exception $e) {
    
    error_log("Authentication failed: " . $e->getMessage());
    http_response_code(500); 
    echo json_encode(["error" => "Authentication failed. Could not obtain access token.", "details" => $e->getMessage()]);
    exit;
}

if (!$accessToken) {
     http_response_code(500);
     echo json_encode(["error" => "Authentication failed. Access token is missing."]);
     exit;
}






$requestBody = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => "What does Cymbal sell?"]
            ]
        ],
        [
            "role" => "model",
            "parts" => [
                ["text" => "Cymbal Direct is an online direct-to-consumer footwear and apparel retailer headquartered in Chicago."]
            ]
        ],
        [
            "role" => "user",
            "parts" => [
                ["text" => "When was the company founded?"]
            ]
        ],
        [
            "role" => "model",
            "parts" => [
                ["text" => "Founded in 2008, Cymbal Direct (originally 'Antern' is a fair trade and B Corp certified sustainability-focused company that works with cotton farmers to reinvest in their communities."]
            ]
        ],
        [
            "role" => "user",
            "parts" => [
                ["text" => "How much is the price of Cymbal clothes?"]
            ]
        ]
    ],
     
     
    "systemInstruction" => [
        "parts" => [
            ["text" => "Cymbal Direct is an online direct-to-consumer footwear and apparel retailer headquartered in Chicago. \n\nFounded in 2008, Cymbal Direct (originally 'Antern' is a fair trade and B Corp certified sustainability-focused company that works with cotton farmers to reinvest in their communities. The price range for Cymbal clothes is typically between $50 and $300.\n\nIn 2010, as Cymbal Group began focusing on digitally-savvy businesses that appealed to a younger demographic of shoppers, the holding company acquired Antern and renamed it Cymbal Direct. In 2019, Cymbal Direct reported an annual revenue of $7 million and employed a total of 32 employees. \n\nCymbal Direct is a digitally native retailer. \n\nYou are a personalized wiki of the company Cymbal."]
        ]
    ],
    "generationConfig" => [
        "responseModalities" => ["TEXT"],
        "temperature" => 0.2,
        
        "maxOutputTokens" => 1024,
        "topP" => 0.8
    ],
     
    "safetySettings" => [
        [
            "category" => "HARM_CATEGORY_HATE_SPEECH",
            "threshold" => "OFF"
        ],
        [
            "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
            "threshold" => "OFF"
        ],
        [
            "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
            "threshold" => "OFF"
        ],
        [
            "category" => "HARM_CATEGORY_HARASSMENT",
            "threshold" => "OFF"
        ]
    ]
];

$jsonRequestBody = json_encode($requestBody);


$ch = curl_init($url);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequestBody);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


$response = curl_exec($ch);


if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(["error" => "cURL error: " . $error_msg]);
    exit;
}


$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);


$responseData = json_decode($response, true);


if ($httpcode !== 200) {
    http_response_code($httpcode);
    
    error_log("API request failed. Status: " . $httpcode . " Response: " . $response);
    echo json_encode(["error" => "API request failed", "status_code" => $httpcode, "response" => $responseData]);
    exit;
}




if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    
    header('Content-Type: application/json');
    echo json_encode(["response" => $responseData['candidates'][0]['content']['parts'][0]['text']]);
} else {
    
    error_log("Unexpected API response format: " . $response);
    http_response_code(500);
    echo json_encode(["error" => "Unexpected API response format", "response" => $responseData]);
}

?>