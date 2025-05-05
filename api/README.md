# Business Care - API REST

Cette API REST fournit un accès programmatique aux données et fonctionnalités de la plateforme Business Care. Elle est divisée en deux sections principales pour répondre aux besoins distincts des interfaces d'administration et client.

## Structure Générale

L'API est organisée comme suit :

```
api/
├── admin/      # Endpoints pour l'interface d'administration et l'application Java de reporting
└── client/     # Endpoints pour l'interface client (entreprises, employés)
```

## Authentification

L'accès à la plupart des endpoints de l'API nécessite une authentification.

-   **Méthode Unifiée (Firebase ID Token)**: L'authentification pour **tous** les endpoints protégés (dans `/api/admin/` et `/api/client/`) est gérée via les **Firebase ID Tokens** obtenus par le client lors de la connexion via le SDK Firebase.
-   **Transmission**: Le client doit inclure le Firebase ID Token valide dans l'en-tête `Authorization` de chaque requête API comme un Jeton Bearer:
    `Authorization: Bearer <FIREBASE_ID_TOKEN>`
-   **Vérification Backend**: Le serveur (PHP) utilise les fonctions dans `shared/web-admin/auth_firebase.php` (ou équivalent client) pour vérifier la validité (signature, expiration, audience, émetteur) du token en utilisant les clés publiques de Google. Si le token est invalide ou manquant, une réponse 401 Unauthorized est retournée.
-   **Obsolète**: Les anciens mécanismes d'authentification spécifiques à `/api/admin/auth.php` (JWT personnalisé) ou `/api/client/auth.php` (sessions) ne sont plus utilisés pour l'authentification API principale.

## Endpoints Disponibles

### API Admin (`/api/admin/`)

Ces endpoints sont utilisés par le panneau d'administration (`web-admin/`) et l'application Java de reporting (`java-app/`) pour gérer les données globales de la plateforme.

-   **`/auth.php`**: Authentification, gestion des tokens JWT.
-   **`/companies.php`**: Gestion des entreprises clientes (CRUD).
-   **`/contracts.php`**: Gestion des contrats de service (CRUD).
-   **`/services.php`**: Gestion des services et prestations proposés (CRUD).
-   **`/users.php`**: Gestion des utilisateurs (administrateurs, prestataires, etc.).
-   **`/events.php`**: Gestion des évènements (ateliers, séminaires). (Mentionné dans `java-app/README.md`)
-   **`/quotes.php`**: Gestion des devis. (Mentionné dans `java-app/README.md`)
-   **`/invoices.php`**: Gestion des factures. (Mentionné dans `java-app/README.md`)

*Pour plus de détails, consultez [`api/admin/README.md`](admin/README.md).*

### API Client (`/api/client/`)

Ces endpoints sont utilisés par l'interface client (`web-client/`) pour permettre aux entreprises et à leurs employés d'interagir avec la plateforme.

-   **`/auth.php`**: Authentification des utilisateurs clients/employés, gestion des sessions.
-   **`/profile.php`**: Gestion du profil utilisateur (employé ou contact entreprise).
-   **`/services.php`**: Consultation du catalogue de services, recherche, détails.
-   **`/contracts.php`**: Consultation des contrats actifs de l'entreprise.
-   **`/appointments.php`**: Gestion des réservations de prestations par les employés.
-   **`/companies.php`**: Informations spécifiques à l'entreprise connectée.
-   **`/employees.php`**: Gestion des informations relatives aux employés (pour les managers d'entreprise).
-   **`/events.php`**: Consultation et inscription aux évènements.
-   **`/communities.php`**: Interaction avec les fonctionnalités communautaires.

*La documentation détaillée pour l'API Client est à retrouver dans [`api/client/README.md`](client/README.md) (à créer/compléter).*

## Principes Généraux

-   **Format des Données**: JSON est utilisé pour les corps de requêtes et les réponses.
-   **Gestion des Erreurs**: Utilisation des codes de statut HTTP standards (2xx, 4xx, 5xx). Les réponses d'erreur incluent généralement un objet JSON avec les clés `error` (booléen) et `message` (chaîne de caractères).
-   **Sécurité**: Implémentation de mesures contre les attaques courantes (CSRF, XSS, Injection SQL). L'accès HTTPS est obligatoire.
-   **Versioning**: (Optionnel) Prévoir un préfixe de version dans l'URL (ex: `/api/v1/admin/`) si des changements majeurs sont anticipés.

## Développement

Pour ajouter ou modifier des endpoints, suivez la structure existante dans les dossiers `admin/` ou `client/` et mettez à jour la documentation correspondante.
