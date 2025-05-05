# Composants Partagés de Business Care

Ce répertoire contient les fichiers PHP partagés entre les différentes interfaces web de l'application Business Care. Ces composants communs fournissent des fonctionnalités essentielles et permettent une cohérence à travers l'application.

## Structure

Le répertoire `shared` est organisé en deux sections principales :

```
shared/
├── web-admin/      # Composants partagés pour l'interface administrateur
├── web-client/     # Composants partagés pour l'interface client
```

## Web Admin

Les fichiers partagés pour l'interface d'administration sont localisés dans le dossier `web-admin/`. Ils fournissent les fonctionnalités de base pour toutes les pages administratives.

### Fichiers principaux

* **config.php** - Définition des constantes et paramètres de configuration globaux (URLs, rôles, tables, statuts, etc.)
* **db.php** - Fonctions de connexion et d'interaction avec la base de données (requêtes SQL, transactions, etc.)
* **functions.php** - Fonctions utilitaires communes (formatage de dates, de montants, gestion des messages flash, etc.)
* **auth.php** - (Obsolète/Complémentaire) Fonctions liées à l'ancien système d'authentification par session PHP. Peut contenir des helpers pour récupérer les infos utilisateur local après vérification Firebase.
* **auth_firebase.php** - **(Nouveau/Essentiel)** Fonctions pour la vérification backend des Firebase ID Tokens reçus via l'en-tête Authorization des requêtes API.
* **logging.php** - Fonctions pour la journalisation des évènements et activités système

### Fonctionnalités clés

* **Gestion de base de données** - Interface PDO sécurisée avec prévention des injections SQL
* **Authentification (Firebase)** - La connexion/déconnexion est gérée côté client via le SDK Firebase. Ce répertoire contient la logique backend (`auth_firebase.php`) pour **vérifier** les Firebase ID Tokens et potentiellement (`auth.php`) lier l'UID Firebase à un utilisateur local.
* **Journalisation** - Enregistrement centralisé des activités utilisateurs, opérations métier et évènements de sécurité
* **Fonctions utilitaires** - Formatage des données, messages flash, pagination, validation CSRF
* **Gestion des erreurs** - Centralisation et normalisation du traitement des erreurs

## Web Client

Les fichiers partagés pour l'interface client sont localisés dans le dossier `web-client/`. Ils fournissent les fonctionnalités pour l'accès des clients, salariés et prestataires.

### Fichiers principaux

* **config.php** - Configuration spécifique à l'interface client (constantes, URLs, etc.)
* **db.php** - Fonctions de base de données adaptées aux besoins du client
* **functions.php** - Utilitaires pour l'interface client (formatage, pagination, etc.)
* **auth.php** - (Obsolète/Complémentaire) Fonctions liées à l'ancien système d'authentification par session PHP. Peut contenir des helpers pour récupérer les infos utilisateur local après vérification Firebase.
* **auth_firebase.php** - **(Nouveau/Essentiel)** Fonctions pour la vérification backend des Firebase ID Tokens pour l'API client.
* **logging.php** - Journalisation des activités client avec fonctions spécialisées

### Différences clés avec la version Admin

* Configuration adaptée aux besoins spécifiques de l'interface client
* Fonctions supplémentaires pour gérer les réservations, les paiements et les préférences utilisateur
* Gestion des rôles spécifiques (ROLE_SALARIE, ROLE_PRESTATAIRE, ROLE_ENTREPRISE)
* Intégration avec des services externes comme Stripe pour les paiements

## Utilisation

Pour utiliser ces composants dans les fichiers PHP des interfaces web-admin ou web-client, incluez simplement les fichiers nécessaires avec `require_once` :

```php
// Exemple d'utilisation dans une page d'administration
require_once __DIR__ . '/../../shared/web-admin/config.php';
require_once __DIR__ . '/../../shared/web-admin/auth.php';
require_once __DIR__ . '/../../shared/web-admin/functions.php';


## Points d'attention

* Les fichiers `config.php` définissent les constantes et les paramètres globaux, adaptez-les selon l'environnement de déploiement.
* Les fonctions d'authentification et de sécurité sont essentielles pour la protection de l'application.
* La journalisation est cruciale pour le suivi des opérations et le débogage.
* Les fonctions de base de données incluent des validations pour prévenir les injections SQL.

## Sécurité

Les mécanismes de sécurité implémentés comprennent :

* Protection contre les injections SQL via l'utilisation de requêtes préparées
* **Authentification API via Firebase ID Tokens** (validés côté serveur)
* **Authentification Frontend via Firebase SDK**
* Autorisation basée sur les rôles (vérifiés après authentification Firebase)
* Protection CSRF pour les formulaires (si encore applicable en dehors des appels API)
* Validation des entrées utilisateur
* Journalisation des évènements de sécurité
* (Obsolète: Gestion des sessions avec rotation des tokens, Hachage des mots de passe avec `password_hash()` - Géré par Firebase)
