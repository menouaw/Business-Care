<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Formatte une conversation en un tableau d'objets de message approprié pour l'API du chatbot locale.
 * L'API du chatbot locale (chatbot.php) attend désormais un tableau simple de messages,
 * où chaque message est un tableau associatif avec 'role' et 'text_content'.
 * Exemple d'entrée/sortie: [['role' => 'user', 'text_content' => 'Hello there!']]
 *
 * @param array $conversationMessages Tableau de messages, chaque élément est un tableau associatif avec 'role' et 'text_content'.
 * @return array Tableau de messages directement utilisable par chatbot.php.
 */
function formatMessagesForChatbotApi(array $conversationMessages): array
{
    $formattedMessages = [];
    foreach ($conversationMessages as $message) {
        if (isset($message['role']) && isset($message['text_content'])) {
            $role = $message['role'];
            if ($role === 'assistant') {
                $role = 'model';
            }
            $formattedMessages[] = [
                'role' => $role,
                'text_content' => $message['text_content']
            ];
        }
    }
    return $formattedMessages;
}

/**
 * Extrait la réponse de l'assistant à partir de la réponse JSON de l'API du chatbot (Gemini format).
 *
 * @param mixed $apiResponseBody Le corps de la réponse JSON décodée de l'API.
 * @return string|null Le contenu du message de l'assistant, ou null si non trouvé ou en cas d'erreur.
 */
function extractAssistantReply($apiResponseBody): ?string
{
    if (isset($apiResponseBody['error'])) {
        
        error_log('Erreur de l\'API du chatbot (wrapper): ' . json_encode($apiResponseBody));
        return "Erreur de communication avec le service IA: " . ($apiResponseBody['details'] ?? $apiResponseBody['error']);
    }

    
    if (isset($apiResponseBody['candidates'][0]['finishReason']) && $apiResponseBody['candidates'][0]['finishReason'] === 'ERROR') {
        error_log('Erreur de l\'API Google AI: ' . json_encode($apiResponseBody));
        return "Erreur de l'API Google AI."; 
    }
    
    if (isset($apiResponseBody['candidates'][0]['content']['parts'][0]['text'])) {
        return $apiResponseBody['candidates'][0]['content']['parts'][0]['text'];
    }
    
    error_log('Structure de réponse de l\'API du chatbot inattendue: ' . json_encode($apiResponseBody));
    return null;
}

/**
 * Envoie le message de l'utilisateur (avec l'historique de la conversation) à l'endpoint local de l'API du chatbot
 * et retourne la réponse de l'assistant.
 *
 * @param array $conversationMessages Tableau de messages représentant l'historique de la conversation,
 *                                    chaque élément est un tableau associatif avec 'role' et 'text_content'.
 * @return string|null La réponse de l'assistant, ou null en cas d'échec.
 */
function sendUserMessageToChatbot(array $conversationMessages): ?string
{
    $apiUrl = 'http://nginx/api/admin/ai/chatbot.php'; 

    
    $apiMessages = formatMessagesForChatbotApi($conversationMessages);

    if (empty($apiMessages)) {
        error_log('Aucun message valide à envoyer à l\'API du chatbot.');
        return null;
    }

    $payload = [
        'messages' => $apiMessages, 
        'stream' => false 
    ];

    $response = makeApiPostRequest($apiUrl, $payload); 

    if (!empty($response['error'])) {
        error_log('Erreur cURL lors de l\'appel à l\'API du chatbot: ' . $response['error']);
        return "Erreur de communication: " . $response['error'];
    }

    if ($response['http_code'] !== 200) {
        error_log('API du chatbot retourne le statut HTTP ' . $response['http_code'] . '. Corps: ' . json_encode($response['body']));
        
        $errorDetail = "Erreur: Reçu le statut " . $response['http_code'];
        if (isset($response['body']['error'])) {
            $errorDetail .= ": " . (is_array($response['body']['error']) ? json_encode($response['body']['error']) : $response['body']['error']);
            if(isset($response['body']['details'])) {
                 $errorDetail .= " - " . $response['body']['details'];   
            }
        }
        return $errorDetail;
    }

    return extractAssistantReply($response['body']);
}
