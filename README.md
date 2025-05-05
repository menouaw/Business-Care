# Business Care - Plateforme de Bien-être et Services pour Entreprises

Business Care est une application web complète dédiée à la gestion des services de bien-être pour les entreprises. La plateforme permet aux entreprises de souscrire à des contrats de services pour offrir des prestations de bien-être à leurs employés, telles que des cours de yoga, des séminaires sur la gestion du stress, des consultations avec des nutritionnistes, et bien plus encore.

## Architecture et Technologies

### Frontend
- HTML5, CSS3, JavaScript (ES6+)
- Framework CSS : Bootstrap 5
- jQuery pour les interactions dynamiques
- AJAX pour les requêtes asynchrones

### Backend
- PHP 8.1+ pour la logique serveur
- API REST pour la communication entre le client et le serveur
- Java pour l'application de reporting autonome
- MySQL pour la persistance des données

### Sécurité
- Sessions PHP sécurisées (utilisées de manière complémentaire si nécessaire)
- Authentification principale via Firebase Authentication (Email/Password)
- Vérification des Firebase ID Tokens (Bearer Tokens) pour l'API REST
- Protection contre les attaques CSRF
- Validation des données côté serveur
- Authentification à deux facteurs pour les comptes administrateurs
- HTTPS obligatoire
- Règles de firewalling précises

## Structure du Projet

```
business-care/
├── api/                     # API REST (voir api/README.md)
│   ├── admin/
│   └── client/
├── assets/                  # Ressources statiques (CSS, JS, images, fonts)
├── build/                   # Fichiers générés lors du build (optionnel)
├── database/                # Scripts de base de données
│   ├── schemas/             # Définition du schéma principal
│   └── seeders/             # Données initiales
├── docker/                  # Configurations Docker
├── documentation/           # Documentation générée ou manuelle
├── i18n/                    # Fichiers de traduction multilingue
├── integrations/            # Intégrations externes (Stripe, OneSignal)
│   └── onesignal/
├── java-app/                # Application Java pour le reporting PDF
├── shared/                  # Code PHP partagé entre web-admin et web-client
│   ├── web-admin/
│   └── web-client/
├── vendor/                  # Dépendances Composer
├── web-admin/               # Interface d'administration
└── web-client/              # Interface utilisateur client
```

## Interfaces Principales

### Interface Client (web-client)
Permet aux entreprises et à leurs employés d'accéder aux services de bien-être.
- Gestion des comptes entreprises
- Espace dédié aux employés avec tutoriel interactif à la première connexion
- Recherche et réservation de services
- Consultation des contrats et factures
- Participation à des évènements et communautés

### Interface Admin (web-admin)
Permet aux administrateurs de Business Care de gérer la plateforme.
- Gestion des entreprises clientes
- Gestion des prestataires de services
- Facturation et suivi des contrats
- Modération des communautés
- Génération de rapports analytiques

### Application Java de Reporting
Application autonome générant des rapports d'activité périodiques au format PDF.
- Statistiques sur les comptes clients
- Analyse des évènements
- Performance des services
- Graphiques et tableaux de synthèse
- Génération automatique quotidienne à 2h00 du matin

## Installation

### Prérequis
- PHP 8.1+
- MySQL 8.0+
- Serveur Web (Apache/Nginx)
- Java JDK 17+ (pour l'application de reporting)
- Maven (pour compiler l'application Java)
- Composer (pour les dépendances PHP)
- Docker et Docker Compose (pour le déploiement conteneurisé)

### Installation avec Docker (recommandée)

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/your-organization/business-care.git
   cd business-care
   ```

2. **Configuration des variables d'environnement**
   Créez un fichier `.env` à partir de `.env.example` (s'il existe) ou configurez les variables nécessaires pour la base de données, l'API, etc.
   Assurez-vous d'ajouter votre `FIREBASE_PROJECT_ID` au fichier `.env` pour la vérification des tokens backend.
   ```bash
   cp .env.example .env
   # Modifier les variables dans le fichier .env
   ```

3. **Mettre à jour les dépendances Composer (si nécessaire)**
   Si vous avez modifié `composer.json` (par exemple, pour ajouter `firebase/php-jwt`), mettez à jour le fichier `composer.lock` :
   ```bash
   composer update
   ```

4. **Lancer les conteneurs Docker**
   Utilise le fichier `compose.yaml` à la racine.
   ```bash
   docker compose up --build -d
   ```

5. **Accéder à l'application**
   - Interface admin: `https://votre-domaine.com/web-admin/` (ou l'URL configurée)
   - Interface client: `https://votre-domaine.com/web-client/` (ou l'URL configurée)

## Authentification (Firebase)

L'authentification des utilisateurs pour les interfaces `web-admin` et `web-client` est gérée principalement par **Firebase Authentication (Email/Password)**.

### Flux d'authentification

1.  **Client-Side (JavaScript)**:
    *   Les SDK Firebase (`firebase-app.js`, `firebase-auth.js`) sont chargés (via `web-admin/templates/footer.php` et `web-client/.../footer.php`).
    *   Le script `assets/js/firebase-init.js` initialise Firebase avec la configuration de votre projet (`firebaseConfig`) et attache un écouteur d'état d'authentification (`onAuthStateChanged`).
    *   Sur les pages de connexion (`web-admin/login.php`, `web-client/login.php`), le JavaScript intercepte la soumission du formulaire.
    *   La fonction `signInWithEmailAndPassword` (exposée via `window.firebaseAuth` dans `firebase-init.js`) est appelée avec les identifiants de l'utilisateur.
    *   Si la connexion Firebase réussit, l'écouteur `onAuthStateChanged` est déclenché.
    *   L'écouteur récupère le **Firebase ID Token** de l'utilisateur (`user.getIdToken()`) et le stocke dans `sessionStorage` (`firebaseIdToken`).
    *   L'écouteur redirige l'utilisateur vers la page appropriée (tableau de bord admin ou client) en fonction de la page de connexion d'origine.
    *   Lors de la déconnexion (via `window.firebaseAuth.logOut()` appelé par `assets/js/admin.js` ou `client.js`), l'écouteur efface le token et redirige vers la page de connexion appropriée.

2.  **API REST (Backend PHP)**:
    *   Les appels aux endpoints protégés de l'API (dans `api/admin/` et `api/client/`) doivent inclure le Firebase ID Token dans l'en-tête `Authorization` comme un Bearer Token (`Authorization: Bearer <ID_TOKEN>`).
    *   Le JavaScript côté client (par exemple, dans une fonction `fetchProtectedData`) récupère le token depuis `sessionStorage` et l'ajoute à l'en-tête.
    *   Sur le serveur, chaque endpoint protégé inclut `shared/web-admin/auth_firebase.php` (ou une version client si nécessaire).
    *   La fonction `requireFirebaseAuthentication()` (dans `auth_firebase.php`) est appelée au début de l'endpoint.
    *   Cette fonction récupère le token depuis l'en-tête `Authorization`.
    *   Elle appelle `verifyFirebaseToken()` pour valider la signature, l'expiration, l'audience (`aud` doit correspondre à `FIREBASE_PROJECT_ID` dans `.env`), et l'émetteur (`iss`) du token en utilisant les clés publiques de Google (mises en cache via `getFirebasePublicKeys()`).
    *   Si le token est valide, la fonction retourne le payload décodé (contenant le `uid` Firebase, l'email, etc.).
    *   Si le token est invalide ou manquant, la fonction retourne une réponse HTTP 401 Unauthorized et arrête l'exécution.
    *   L'endpoint utilise ensuite le `uid` Firebase (`$firebaseUserPayload->sub`) pour rechercher l'utilisateur correspondant dans la base de données locale (`personnes` table) et vérifier ses rôles/permissions si nécessaire (comme dans `api/admin/users.php`).

### Fichiers Clés

-   `assets/js/firebase-init.js`: Initialisation Firebase, gestionnaire `onAuthStateChanged`, redirection.
-   `web-admin/login.php` / `web-client/login.php`: Formulaires de connexion et JS pour appeler `signInWithEmailAndPassword`.
-   `web-admin/templates/footer.php` / `web-client/.../footer.php`: Inclusion des SDK Firebase et `firebase-init.js`.
-   `assets/js/admin.js` / `assets/js/client.js`: Gestion de la déconnexion (`logOut`).
-   `shared/web-admin/auth_firebase.php`: Fonctions PHP de vérification du token backend.
-   `api/admin/*`, `api/client/*`: Endpoints API utilisant `requireFirebaseAuthentication()`.
-   `composer.json`: Doit inclure `firebase/php-jwt`, `guzzlehttp/guzzle`, et `symfony/cache`.
-   `.env`: Doit définir `FIREBASE_PROJECT_ID`.
-   `docker/nginx/default.conf`: Doit passer l'en-tête `Authorization` à PHP-FPM.

## Fonctionnalités Principales

### Gestion des Entreprises
- Inscription et gestion de profil
- Consultation des contrats actifs
- Suivi des services utilisés par les employés
- Accès aux factures et paiements générés automatiquement en PDF

### Portail des Employés
- Accès aux services disponibles
- Réservation de prestations
- Participation aux évènements
- Interaction au sein des communautés
- Réception de notifications push

### Administration
- Gestion complète des utilisateurs
- Création et modification des services
- Suivi financier et facturation
- Reporting et analyses

## Conteneurisation et Déploiement
Le projet est entièrement conteneurisé via Docker pour faciliter le développement et la production.
Un fichier `compose.yaml` est fourni à la racine du projet pour orchestrer les différents services:
- Serveur web (Nginx)
- PHP-FPM
- MySQL
- Application Java

Les parties 1 et 2 du projet (interfaces web, API, app Java) sont déployées via Docker.

## Multilinguisme
L'application est entièrement multilingue via le dossier `i18n/` contenant les fichiers de traduction.
La langue par défaut est le français, mais l'anglais est également disponible.
Il est possible d'ajouter des langues sans modifier le code ni utiliser Google Traduction.

## Sécurité et Sauvegarde
- Des règles de firewalling très précises ne laissant passer que les flux désirés
- L'accès HTTPS obligatoire via SSL/TLS
- Un système de sauvegarde régulière des données
- La connexion sécurisée via Firebase Authentication. Les appels API sont sécurisés par des Firebase ID Tokens.
- Utilisation potentielle de services Cloud pour l'authentification et la journalisation (à configurer)

## Intégrations

### Stripe
Intégration complète pour la gestion des paiements et la génération automatique de factures.

### OneSignal
Intégration pour l'envoi de notifications push aux employés.

### Services Cloud (Planifié)
L'utilisation de services externes pour la journalisation centralisée (ex: Datalog) et la gestion des secrets (ex: Key Vault) est envisagée pour améliorer la robustesse et la sécurité.

## API REST
L'application expose une API REST complète via le point d'entrée `/api/`. Cette API est essentielle pour la communication entre les interfaces frontend, l'application Java de reporting et potentiellement d'autres services.

Elle est divisée en deux sections principales :
- `/api/admin/` : Endpoints pour l'administration et le reporting (sécurisés par Firebase ID Token).
- `/api/client/` : Endpoints pour l'interface client (sécurisés par Firebase ID Token).

Pour une documentation détaillée des endpoints, de l'authentification et des principes généraux, consultez le fichier [`api/README.md`](api/README.md).

## Gestion de Projet
- Utilisation obligatoire de GitHub pour le versionnement du code
- Mise en place d'un Trello pour la gestion des tâches

## Documentation
La documentation complète du projet est disponible via PHPDocumentor.
Pour générer la documentation:
```bash
php phpDocumentor.phar -c phpdoc.xml
```