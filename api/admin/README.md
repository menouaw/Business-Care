# Business Care - API Admin

Cette API REST est conçue pour fournir un accès programmable aux données de Business Care pour les applications externes, notamment l'application Java de reporting (`java-app/`). Elle est implémentée avec PHP et sert de backend pour les opérations CRUD sur les entités principales du système.

## Structure de l'API

```
api/admin/
├── auth.php         # Points de terminaison d'authentification
├── companies.php    # Gestion des entreprises clientes
├── contracts.php    # Gestion des contrats
├── services.php     # Gestion des services/prestations
├── users.php        # Gestion des utilisateurs
└── index.php        # Point d'entrée principal (documentation/redirection)
```

## Authentification

Toutes les requêtes vers les points de terminaison protégés nécessitent une authentification via un Jeton Bearer (Bearer Token). L'API utilise JWT (JSON Web Tokens) pour l'authentification.

### Obtention d'un Token

**Endpoint:** `POST /api/admin/auth.php`

**Corps de la Requête:**
```json
{
  "email": "admin@example.com",
  "password": "your_password"
}
```

**Réponse de Succès (200 OK):**
```json
{
  "error": false,
  "message": "Authentification réussie",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "nom": "Admin",
    "prenom": "Super",
    "email": "admin@example.com",
    "role_id": 1
  }
}
```

### Utilisation du Token

Pour les requêtes suivantes, incluez le token dans l'en-tête HTTP:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Déconnexion

**Endpoint:** `DELETE /api/admin/auth.php`

**En-tête:** Token Bearer requis

**Réponse de Succès (200 OK):**
```json
{
  "error": false,
  "message": "Déconnexion réussie"
}
```

## Ressources Disponibles

### 1. Utilisateurs

**Endpoint Base:** `/api/admin/users.php`

| Méthode | Endpoint                | Description                     |
|---------|-------------------------|---------------------------------|
| GET     | `/api/admin/users.php`  | Liste tous les utilisateurs     |
| GET     | `/api/admin/users.php?id={id}` | Détails d'un utilisateur |
| POST    | `/api/admin/users.php`  | Crée un nouvel utilisateur      |
| PUT     | `/api/admin/users.php?id={id}` | Modifie un utilisateur   |
| DELETE  | `/api/admin/users.php?id={id}` | Supprime un utilisateur  |

**Exemple de Réponse (GET liste):**
```json
{
  "error": false,
  "data": [
    {
      "id": 1,
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "jean.dupont@example.com",
      "role_id": 2,
      "role_name": "Prestataire",
      "est_actif": 1
    },
    // ... autres utilisateurs
  ]
}
```

### 2. Entreprises

**Endpoint Base:** `/api/admin/companies.php`

| Méthode | Endpoint                   | Description                     |
|---------|----------------------------|---------------------------------|
| GET     | `/api/admin/companies.php` | Liste toutes les entreprises    |
| GET     | `/api/admin/companies.php?id={id}` | Détails d'une entreprise|
| POST    | `/api/admin/companies.php` | Crée une nouvelle entreprise    |
| PUT     | `/api/admin/companies.php?id={id}` | Modifie une entreprise  |
| DELETE  | `/api/admin/companies.php?id={id}` | Supprime une entreprise |

**Exemple de Réponse (GET détails):**
```json
{
  "error": false,
  "data": {
    "id": 1,
    "nom": "Entreprise ABC",
    "siret": "12345678901234",
    "adresse": "123 Rue de l'Innovation",
    "code_postal": "75000",
    "ville": "Paris",
    "email": "contact@entrepriseabc.com",
    "telephone": "0123456789",
    "taille_entreprise": "51-200",
    "secteur_activite": "Tech",
    "contracts": [1, 3, 5],
    "employees_count": 150
  }
}
```

### 3. Contrats

**Endpoint Base:** `/api/admin/contracts.php`

| Méthode | Endpoint                   | Description                    |
|---------|----------------------------|--------------------------------|
| GET     | `/api/admin/contracts.php` | Liste tous les contrats        |
| GET     | `/api/admin/contracts.php?id={id}` | Détails d'un contrat   |
| GET     | `/api/admin/contracts.php?company_id={id}` | Contrats d'une entreprise |
| POST    | `/api/admin/contracts.php` | Crée un nouveau contrat        |
| PUT     | `/api/admin/contracts.php?id={id}` | Modifie un contrat     |
| DELETE  | `/api/admin/contracts.php?id={id}` | Supprime un contrat    |

**Exemple de Réponse (GET liste):**
```json
{
  "error": false,
  "data": [
    {
      "id": 1,
      "entreprise_id": 1,
      "service_id": 2,
      "date_debut": "2023-01-15",
      "date_fin": "2024-01-14",
      "nombre_salaries": 150,
      "statut": "actif",
      "nom_entreprise": "Entreprise ABC",
      "nom_service": "Pack Standard"
    },
    // ... autres contrats
  ]
}
```

### 4. Services

**Endpoint Base:** `/api/admin/services.php`

| Méthode | Endpoint                  | Description                    |
|---------|---------------------------|--------------------------------|
| GET     | `/api/admin/services.php` | Liste tous les services        |
| GET     | `/api/admin/services.php?id={id}` | Détails d'un service   |
| POST    | `/api/admin/services.php` | Crée un nouveau service        |
| PUT     | `/api/admin/services.php?id={id}` | Modifie un service     |
| DELETE  | `/api/admin/services.php?id={id}` | Supprime un service    |

**Exemple de Réponse (GET détails):**
```json
{
  "error": false,
  "data": {
    "id": 2,
    "nom": "Pack Standard",
    "description": "Accès à tous les services de base...",
    "prix_base": 1000.00,
    "prix_par_salarie": 5.00,
    "duree_contrat_mois": 12,
    "inclus_prestations": [1, 3, 5],
    "est_actif": 1,
    "details_prestations": [
      {
        "id": 1,
        "nom": "Atelier Gestion du Stress",
        "description": "Workshop interactif...",
        "prix": 500.00,
        "type": "atelier",
        "categorie": "Bien-être"
      },
      // ... autres prestations incluses
    ]
  }
}
```

## Gestion des Erreurs

L'API utilise les codes de statut HTTP standards:

- `200 OK` - Requête réussie
- `201 Created` - Ressource créée avec succès
- `400 Bad Request` - Paramètres invalides
- `401 Unauthorized` - Authentification requise/échouée
- `403 Forbidden` - Permissions insuffisantes
- `404 Not Found` - Ressource introuvable
- `500 Internal Server Error` - Erreur serveur

Toutes les réponses d'erreur suivent ce format:
```json
{
  "error": true,
  "message": "Description de l'erreur",
  "code": 401
}
```

## Pagination

Pour les endpoints qui retournent plusieurs résultats, la pagination est supportée via:

- `?page=2` - Page à récupérer (commence à 1)
- `?limit=20` - Nombre d'éléments par page (défaut: 10, max: 100)

Exemple: `/api/admin/users.php?page=2&limit=20`

Réponse avec pagination:
```json
{
  "error": false,
  "data": [ /* éléments de la page */ ],
  "pagination": {
    "total": 135,
    "per_page": 20,
    "current_page": 2,
    "last_page": 7,
    "next_page_url": "/api/admin/users.php?page=3&limit=20",
    "prev_page_url": "/api/admin/users.php?page=1&limit=20"
  }
}
```

## Filtrage

Des paramètres de filtrage spécifiques sont disponibles pour certains endpoints:

- `/api/admin/users.php?role_id=2` - Filtrer par rôle
- `/api/admin/contracts.php?status=actif` - Filtrer par statut
- `/api/admin/services.php?est_actif=1` - Filtrer par état d'activité

## Intégration avec l'Application Java

Cette API est conçue pour être utilisée par l'application Java de reporting (`/java-app/`), qui génère des rapports PDF détaillés sur l'activité de Business Care.

Pour l'intégration avec l'application Java:
1. L'application Java s'authentifie et obtient un token
2. Utilise ce token pour les requêtes suivantes
3. Collecte les données nécessaires pour la génération de rapports
4. Génère les fichiers PDF et graphiques visuels
5. Stocke les rapports pour accès ultérieur via le panneau d'administration

## Sécurité et Journalisation

L'API inclut:
- Authentification sécurisée par JWT
- Validation des entrées
- Journalisation complète des accès (table `logs`)
- Protection contre les attaques courantes (CSRF, XSS, Injection SQL)
- Limites de taux (rate limiting) pour prévenir les abus

## Développement

### Ajouter un nouvel Endpoint API

1. Créer un fichier PHP dans le dossier `/api/admin/`
2. Inclure le fichier d'initialisation API commun
3. Implémenter la logique selon la méthode HTTP (GET, POST, etc.)
4. Retourner les réponses au format JSON standardisé
5. Documenter l'endpoint ici

### Tests

Utilisez une application comme Postman ou cURL pour tester les endpoints API:

```bash
# Exemple d'authentification
curl -X POST https://votredomaine.com/api/admin/auth.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"your_password"}'

# Exemple de récupération des utilisateurs avec le token
curl -X GET https://votredomaine.com/api/admin/users.php \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```
