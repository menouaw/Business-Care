<?php

require_once __DIR__ . '/../../init.php';

/**
 * Formate une chaîne de caractères en un tableau de messages de conversation pour l'endpoint de l'API du chatbot.
 * Le tableau d'entrée doit contenir des messages avec 'role' et 'text_content'.
 * Exemple d'entrée: [['role' => 'user', 'text_content' => 'Hello there!']]
 *
 * @param array $conversationMessages Tableau de messages, chaque élément est un tableau associatif avec 'role' et 'text_content'.
 * @return array Tableau de messages formatés pour l'API du chatbot.
 */
function formatMessagesForChatbotApi(array $conversationMessages): array
{
    $formattedMessages = [];
    foreach ($conversationMessages as $message) {
        if (isset($message['role']) && isset($message['text_content'])) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => [
                    ['type' => 'text', 'text' => $message['text_content']]
                ]
            ];
        }
    }
    return $formattedMessages;
}

/**
 * Extrait la réponse de l'assistant à partir de la réponse JSON de l'API du chatbot.
 *
 * @param mixed $apiResponseBody Le corps de la réponse JSON décodée de l'API.
 * @return string|null Le contenu du message de l'assistant, ou null si non trouvé ou en cas d'erreur.
 */
function extractAssistantReply($apiResponseBody): ?string
{
    if (isset($apiResponseBody['error'])) {
        error_log('Erreur de l\'API du chatbot: ' . json_encode($apiResponseBody['error']));
        return null;
    }

    if (isset($apiResponseBody['choices'][0]['message']['content'])) {
        return $apiResponseBody['choices'][0]['message']['content'];
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

    $formattedApiMessages = formatMessagesForChatbotApi($conversationMessages);

    if (empty($formattedApiMessages)) {
        error_log('Aucun message valide à envoyer à l\'API du chatbot.');
        return null;
    }

    $payload = [
        'messages' => $formattedApiMessages,
        'stream' => false
    ];

    $response = makeApiPostRequest($apiUrl, $payload);

    if (!empty($response['error'])) {
        error_log('Erreur cURL lors de l\'appel à l\'API du chatbot: ' . $response['error']);
        return null;
    }

    if ($response['http_code'] !== 200) {
        error_log('API du chatbot retourne le statut HTTP ' . $response['http_code'] . '. Corps: ' . json_encode($response['body']));
        return "Erreur: Reçu le statut " . $response['http_code'];
    }

    return extractAssistantReply($response['body']);
}
