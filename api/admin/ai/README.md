# API Chatbot Business Care

Cette API permet à votre application web PHP d'interagir avec les modèles Gemini de Google Vertex AI pour des interactions conversationnelles.

---

## Point d'accès (Endpoint)

```
POST /api/admin/ai/chatbot.php
```

---

## Requête

### En-têtes
- `Content-Type: application/json`

### Corps de la requête

#### Exemple basique
```json
{
  "messages": [
    {
      "role": "user",
      "content": [
        { "type": "text", "text": "Quel est le prix d'une pizza?" }
      ]
    }
  ],
  "stream": false
}
```

- **messages** : Tableau d'objets message, chacun avec un `role` (`user` ou `assistant`) et un tableau `content` (texte, images, etc.).
- **stream** : (optionnel, défaut : false) Si true, active les réponses en streaming (non implémenté dans cet exemple PHP).

---

## Réponse

- **Succès (200) :**
  ```json
  {
    "choices": [
      {
        "message": {
          "role": "assistant",
          "content": "Le prix d'une pizza dépend de la variété, mais en général, cela coûte entre 8 et 15 euros."
        }
      }
    ]
  }
  ```
- **Erreur (non-200) :**
  ```json
  {
    "error": "Requête API échouée",
    "status_code": 400,
    "réponse": { ... }
  }
  ```

---

## Fonctionnement

1. **Frontend** : Récupère l'entrée utilisateur et l'envoie en POST JSON à `/api/admin/ai/chatbot.php`.
2. **Backend** :
    - Reçoit la requête.
    - Construit le corps de la requête pour Vertex AI.
    - Authentifie via les Identifiants d'Application Google (Application Default Credentials).
    - Envoie la requête à Vertex AI.
    - Retourne la réponse de l'IA au format JSON.

---

## Exemple d'appel PHP côté client

```php
$data = [
    "messages" => [
        [
            "role" => "user",
            "content" => [
                ["type" => "text", "text" => "Quel est le prix d'une pizza?"]
            ]
        ]
    ],
    "stream" => false
];
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/api/admin/ai/chatbot.php', false, $context);
echo $result;
```

---

## Détails du corps de la requête

- **messages** :  
  - `role` : `"user"` pour l'utilisateur, `"assistant"` pour les réponses précédentes de l'IA (pour le contexte).
  - `content` : Tableau d'objets. Chaque objet doit avoir un `type` (ex : `"text"`) et la donnée correspondante (ex : `"text": "..."`).

- **stream** :  
  - Booléen. Si true, demande une réponse en streaming (non implémenté dans cet exemple PHP).

---

## Gestion des erreurs

- Si l'appel à l'API Vertex AI échoue, la réponse inclura un champ `error` et un `status_code`.
- Si l'authentification échoue, une erreur 500 est retournée avec les détails.

---

## Sécurité

- Le backend utilise les Identifiants d'Application Google. Assurez-vous que votre environnement serveur est configuré avec le bon compte de service et les permissions Vertex AI nécessaires.

---

## Références

- [Vertex AI Model Reference – Inference (REST)](https://cloud.google.com/vertex-ai/generative-ai/docs/model-reference/inference?hl=fr)

---

## Améliorations futures

- Ajouter le support des réponses en streaming.
- Ajouter la gestion du contexte utilisateur/session pour des conversations multi-tours.
- Implémenter un système de journalisation et de limitation de débit.
- Ajouter une gestion d'erreurs et une validation plus robustes.
