# API Chatbot Business Care

Cette API sert de backend PHP pour interagir avec les modèles Gemini de Google Vertex AI, permettant des fonctionnalités conversationnelles avancées pour votre application.

---

## Point d'accès (Endpoint)

L'API est accessible via le point d'accès local suivant :
```
POST /api/admin/ai/chatbot.php
```

---

## Requête (vers `chatbot.php`)

### En-têtes
- `Content-Type: application/json`

### Corps de la requête

Votre application frontend doit envoyer une requête POST JSON à `chatbot.php` avec la structure suivante :

```json
{
  "messages": [
    { "role": "user", "text_content": "Bonjour, comment allez-vous ?" },
    { "role": "model", "text_content": "Je vais bien, merci ! Et vous ?" }
  ],
  "stream": false
}
```

- **messages** : `array` (obligatoire)
  - Un tableau d'objets message représentant l'historique de la conversation.
  - Chaque objet message doit contenir :
    - `role` : `string` (obligatoire) - Peut être `"user"` pour les messages de l'utilisateur ou `"model"` pour les réponses précédentes de l'IA. Si vous utilisiez `"assistant"`, il sera automatiquement mappé en `"model"`.
    - `text_content` : `string` (obligatoire) - Le contenu textuel du message. Les messages avec un `text_content` vide ou ne contenant que des espaces seront ignorés.
- **stream** : `boolean` (optionnel, défaut : `false`)
  - Si `true`, l'API tentera d'utiliser la méthode de streaming (`streamGenerateContent`) de l'API Google AI.
  - Si `false` (ou non fourni), la méthode non-streaming (`generateContent`) sera utilisée.

---

## Transformation Interne de la Requête (par `request_builder.php`)

Le script `chatbot.php` utilise `request_builder.php` pour transformer la requête ci-dessus en un format compatible avec l'API Google Gemini. Voici un exemple de la requête envoyée à Google AI :

```json
{
    "contents": [
        {
            "role": "user",
            "parts": [{"text": "Bonjour, comment allez-vous ?"}]
        },
        {
            "role": "model",
            "parts": [{"text": "Je vais bien, merci ! Et vous ?"}]
        }
    ],
    "systemInstruction": {
        "parts": [{
            "text": "Tu es un assistant chaleureux et attentionné, chatbot dédié à l'assistance des clients de Business Care..." // (Instruction système complète comme configurée)
        }]
    },
    "generationConfig": {
        "responseMimeType": "text/plain",
        "temperature": 0.5,
        "maxOutputTokens": 256,
        "topP": 0.5,
        "seed": 0
        // "thinkingConfig" a été retiré car non standard pour cet endpoint Gemini
    },
    "safetySettings": [
        {"category": "HARM_CATEGORY_HATE_SPEECH", "threshold": "BLOCK_NONE"},
        {"category": "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold": "BLOCK_NONE"},
        {"category": "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold": "BLOCK_NONE"},
        {"category": "HARM_CATEGORY_HARASSMENT", "threshold": "BLOCK_NONE"}
    ]
}
```
*Note : Le champ `model` (ex: `gemini-2.5-flash-preview-04-17`) était précédemment inclus ici mais est maintenant géré directement dans l'URL de l'endpoint lors de l'appel à Google AI par `chatbot.php`.*

---

## Réponse (de `chatbot.php`)

La réponse de `chatbot.php` est directement la réponse JSON de l'API Google AI.

- **Succès (200 OK) :**
  Un exemple de réponse réussie de l'API Gemini :
  ```json
  {
    "candidates": [
      {
        "content": {
          "role": "model",
          "parts": [
            {
              "text": "Je suis un grand modèle linguistique, entraîné par Google."
            }
          ]
        },
        "finishReason": "STOP", 
        "safetyRatings": [
          { "category": "HARM_CATEGORY_HATE_SPEECH", "probability": "NEGLIGIBLE" },
          // ... autres safetyRatings
        ],
        "tokenCount": 10 // Exemple, peut varier
        // Le champ "citationMetadata" peut aussi être présent
      }
    ]
    // Le champ "promptFeedback" peut aussi être présent en fonction de la réponse du modèle
  }
  ```
  Votre application devra parser cette structure pour extraire la réponse textuelle de `candidates[0].content.parts[0].text`.

- **Erreur (non-200) :**
  Si `chatbot.php` ou `google_ai_client.php` rencontrent une erreur (ex: cURL, authentification, ou une erreur de l'API Google AI elle-même), la réponse sera structurée comme suit :
  ```json
  {
    "error": "Description de l'erreur (ex: Requête API échouée, Authentification échouée)",
    "status_code": 500, // ou autre code HTTP d'erreur
    "details": "Message d'erreur plus détaillé ou corps de la réponse d'erreur de l'API Google" 
    // "réponse" peut aussi être utilisé au lieu de "details" dans certains cas d'erreur de l'API Google.
  }
  ```

---

## Fonctionnement

1.  **Frontend / Service Appelant** : Prépare la conversation (un tableau d'objets `messages` avec `role` et `text_content`) et l'envoie en POST JSON au point d'accès `/api/admin/ai/chatbot.php`.
2.  **`chatbot.php` (Backend)** :
    a.  Reçoit la requête JSON et extrait les `messages` et le drapeau `stream`.
    b.  Appelle `buildChatCompletionRequest()` (de `request_builder.php`) pour formater les `messages` utilisateurs en une structure `contents` et pour ajouter `systemInstruction`, `generationConfig`, et `safetySettings` pour l'API Google Gemini.
    c.  Détermine l'URL de l'API Google AI dynamiquement en utilisant les constantes de `config.php` (PROJECT_ID, LOCATION_ID, API_ENDPOINT_HOST, PUBLISHER, MODEL_ID) et la méthode appropriée (`generateContent` ou `streamGenerateContent` basée sur le drapeau `stream`).
        L'URL ressemble à : `https://{API_ENDPOINT_HOST}/v1/projects/{PROJECT_ID}/locations/{LOCATION_ID}/publishers/{PUBLISHER}/models/{MODEL_ID}:{apiMethod}`.
    d.  Récupère un jeton d'accès Google via `getGoogleAccessToken()` (de `auth.php`) en utilisant les Identifiants d'Application par Défaut (ADC).
    e.  Appelle `callGoogleAI()` (de `google_ai_client.php`) avec l'URL, le jeton d'accès et le corps de la requête formaté pour envoyer la requête à l'API Vertex AI.
    f.  Retourne la réponse JSON brute de l'API Google AI au client.

---

## Exemple d'appel PHP côté client (vers `chatbot.php`)

```php
<?php
$apiUrl = 'http://192.168.213.22/api/admin/ai/chatbot.php';

$conversationHistory = [
    [
        "role" => "user",
        "text_content" => "Bonjour, quels services proposez-vous ?"
    ]
    // Ajoutez d'autres messages ici pour l'historique, par exemple :
    // , [ "role" => "model", "text_content" => "Nous proposons divers services pour le bien-être en entreprise." ]
    // , [ "role" => "user", "text_content" => "Parlez-moi des tarifs." ]
];

$data = [
    "messages" => $conversationHistory,
    "stream" => false // Mettre à true pour tester le streaming (si supporté par votre client)
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'timeout' => 60 // Augmenter le timeout si nécessaire pour les réponses longues
    ],
];

$context  = stream_context_create($options);
$resultJson = file_get_contents($apiUrl, false, $context);

if ($resultJson === FALSE) {
    echo "Erreur lors de l'appel à l'API locale.";
    // Gérer l'erreur (ex: vérifier les logs du serveur web)
} else {
    $responseData = json_decode($resultJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Erreur de décodage JSON de la réponse locale: " . json_last_error_msg();
        echo "<pre>Réponse brute : " . htmlspecialchars($resultJson) . "</pre>";
    } elseif (isset($responseData['error'])) {
        echo "Erreur de l'API : " . htmlspecialchars($responseData['error']);
        if (isset($responseData['details'])) {
            echo "<br>Détails : <pre>" . htmlspecialchars(is_array($responseData['details']) ? json_encode($responseData['details'], JSON_PRETTY_PRINT) : $responseData['details']) . "</pre>";
        }
    } elseif (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        echo "Réponse de l'assistant : <pre>" . htmlspecialchars($responseData['candidates'][0]['content']['parts'][0]['text']) . "</pre>";
    } else {
        echo "Réponse de l'assistant non trouvée ou structure inattendue.";
        echo "<pre>Réponse complète : " . htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . "</pre>";
    }
}
?>
```

---

## Détails du corps de la requête (envoyé à `chatbot.php`)

- **messages** : Tableau d'objets.
  - `role` : `string`. Doit être `"user"` pour les entrées de l'utilisateur ou `"model"` pour les réponses précédentes de l'IA (pour maintenir le contexte de la conversation).
  - `text_content` : `string`. Le texte du message.
- **stream** : `boolean`.
  - `false` (par défaut) : L'API Google AI est appelée avec `:generateContent`.
  - `true` : L'API Google AI est appelée avec `:streamGenerateContent`. Le script `google_ai_client.php` actuel ne gère pas la lecture en continu de la réponse, mais il enverra la requête à l'endpoint de streaming. Une gestion côté client de la réponse streamée serait nécessaire si cette option est utilisée de manière intensive.

---

## Gestion des erreurs

- Si l'appel à l'API Google Vertex AI échoue depuis `google_ai_client.php`, la réponse JSON de `chatbot.php` inclura un champ `error` et potentiellement `status_code` et `details` (ou `réponse`).
- Si l'authentification Google (ADC) échoue dans `auth.php`, une erreur HTTP 500 est retournée avec des détails dans le corps JSON.
- Assurez-vous de vérifier les logs PHP et les logs de votre serveur web (Nginx/Apache) en cas de problèmes.

---

## Sécurité

- Le backend utilise les Identifiants d'Application par Défaut (ADC) de Google Cloud pour l'authentification. Assurez-vous que l'environnement de votre serveur (où PHP s'exécute) est correctement configuré :
    - Soit en s'exécutant sur une ressource Google Cloud (VM, Cloud Run, App Engine) avec un compte de service attaché ayant les permissions `Vertex AI User`.
    - Soit en définissant la variable d'environnement `GOOGLE_APPLICATION_CREDENTIALS` pour pointer vers un fichier JSON de clé de compte de service valide, disposant des permissions nécessaires.
- Les permissions requises incluent généralement le rôle `roles/aiplatform.user` (Utilisateur de Vertex AI) ou des permissions plus granulaires pour appeler les modèles.

---

## Références

- [Documentation de référence de l'API Vertex AI Gemini (REST)](https://cloud.google.com/vertex-ai/generative-ai/docs/model-reference/inference?hl=fr)
- [Authentification Google Cloud pour les applications PHP](https://cloud.google.com/php/docs/reference/google-auth/latest/ApplicationDefaultCredentials)

---

## Améliorations futures possibles

- Implémenter une gestion complète des réponses en streaming dans `google_ai_client.php` et côté client si nécessaire.
- Améliorer la validation des entrées dans `chatbot.php`.
- Ajouter une journalisation plus détaillée des requêtes et réponses (tout en faisant attention aux données sensibles).
