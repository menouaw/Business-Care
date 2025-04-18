# Business-Care
Projet annuel de deuxième année

## Aperçu du Projet

Business-Care est une application web conçue pour gérer les interactions et services entre une organisation administratrice, des entreprises clientes, leurs employés, et potentiellement des prestataires de services. Elle permet de gérer des entités telles que les entreprises, les utilisateurs (admins, employés, prestataires), les contrats, les services proposés, les devis, la facturation et les rendez-vous.

## Stack Technologique

*   **Backend:** PHP 8.1 (Application principale et API REST distincte dans `/api`)
*   **Frontend:** PHP, HTML, CSS, JavaScript (Interfaces séparées pour Admin dans `/web-admin` et Client dans `/web-client`)
*   **Base de données:** MySQL 8.0 (Schéma, vues, triggers et données initiales dans `/database`)
*   **Serveur Web:** Nginx (Configuration dans `docker/nginx/default.conf`)
*   **Dépendances PHP:** Gérées via Composer (`composer.json`)
*   **Conteneurisation:** Docker & Docker Compose (Configuration dans `Dockerfile`, `docker-compose.yml`, `docker/`)

## Structure du Projet

Le projet est organisé dans les répertoires principaux suivants :

*   `api/`: Contient les points d'accès de l'API REST backend (service distinct).
*   `assets/`: Fichiers statiques (CSS, JS, images, polices) servis directement par Nginx.
*   `database/`: Schémas de base de données, migrations (si applicable), vues, triggers, seeders et scripts d'initialisation.
*   `docker/`: Contient la configuration spécifique à Docker (Dockerfile, Nginx config, script d'init DB).
*   `shared/`: Code PHP partagé (configuration, connexion BDD, fonctions utilitaires, authentification, logging) utilisé par `web-admin` et `web-client`.
*   `web-admin/`: Interface d'administration (application PHP).
*   `web-client/`: Interface destinée aux clients/employés (application PHP).
*   `i18n/`: Fichiers d'internationalisation/localisation.
*   `tests/`: Tests de l'application.
*   `cloud-services/`, `infrastructure/`, `integrations/`, `java-app/`, `mobile-app/`, `desktop-app/`: Répertoires de placeholders ou sous-projets liés.

## Installation et Configuration (Docker Recommandé)

La méthode recommandée pour installer et exécuter Business-Care est via Docker.

1.  **Prérequis:**
    *   Docker ([https://www.docker.com/get-started](https://www.docker.com/get-started))
    *   Docker Compose (généralement inclus avec Docker Desktop)
    *   Git
2.  **Cloner le dépôt:**
    ```bash
    git clone https://github.com/menouaw/Business-Care.git
    cd Business-Care
    ```
3.  **Configurer l'environnement:**
    *   Copiez le fichier d'environnement exemple s'il existe, ou créez un fichier `.env` à la racine du projet.
    *   Assurez-vous que les variables suivantes sont définies dans `.env` (ajustez les valeurs par défaut si nécessaire, **surtout les mots de passe pour la production**) :
        ```dotenv
        MYSQL_DATABASE=business_care
        MYSQL_USER=business_care_user
        MYSQL_PASSWORD=root # Mot de passe pour l'utilisateur applicatif
        MYSQL_ROOT_PASSWORD=root # Mot de passe root de MySQL (utilisé pour l'init)
        MYSQL_BACKUP_PASSWORD=root # Mot de passe pour l'utilisateur de backup
        # MYSQL_PORT=3306 # Décommentez pour exposer un port différent pour MySQL
        ```
    *   **Important:** Ajoutez `.env` à votre fichier `.gitignore` pour éviter de commiter des secrets.
4.  **Construire et Lancer les Conteneurs:**
    *   Depuis la racine du projet, exécutez :
        ```bash
        docker compose up --build -d
        ```
    *   Cette commande va construire l'image PHP, télécharger les images Nginx et MySQL, et démarrer les trois services en arrière-plan (`-d`).
5.  **Initialisation de la Base de Données:**
    *   Lors du premier démarrage du service `db`, le script `docker/db/init.sh` sera automatiquement exécuté.
    *   Ce script crée la base de données, importe le schéma (`business_care.sql`), les vues (`views.sql`), les triggers (`triggers.sql`), crée les utilisateurs (`business_care_user`, `business_care_backup`) et importe les données d'exemple (`sample_data.sql`) si elles existent.
6.  **Accéder à l'Application:**
    *   L'application devrait maintenant être accessible dans votre navigateur :
        *   Interface Admin : `http://localhost/admin`
        *   Interface Client : `http://localhost/client` (ou simplement `http://localhost/` qui pourrait rediriger)

---

### Installation Manuelle (Alternative)

_(Non recommandée si Docker est disponible. Sert principalement de référence.)_

1.  **Prérequis:**
    *   PHP 8.1+ (avec extensions `pdo_mysql`, `zip`, etc.)
    *   MySQL 8.0+
    *   Composer 2+
    *   Serveur Web (Nginx ou Apache avec `mod_rewrite`)
2.  **Cloner le dépôt:** `git clone <url-du-depot>`
3.  **Installer les dépendances PHP:** `cd Business-Care && composer install`
4.  **Configurer la base de données:**
    *   Créez une base de données MySQL (ex: `business_care`).
    *   Créez les utilisateurs nécessaires (ex: `business_care_user`).
    *   Importez les schémas, vues, triggers depuis `database/schemas/`.
    *   Optionnellement, exécutez les seeders depuis `database/seeders/`.
5.  **Configurer la connexion BDD:** Mettre à jour les identifiants dans `shared/web-admin/config.php` et `shared/web-client/config.php`.
6.  **Configurer le serveur web:**
    *   Configurez Nginx ou Apache.
    *   Définissez la racine du projet comme document root ou utilisez des alias/rewrite rules pour mapper `/admin` à `web-admin/`, `/client` à `web-client/`, `/api` à `api/`, et `/assets` à `assets/`.
    *   Assurez-vous que la réécriture d'URL est activée et configurée pour gérer les requêtes PHP et les routes API (voir `docker/nginx/default.conf` comme exemple pour Nginx).

## Utilisation

Une fois l'installation Docker terminée :

*   **Interface Admin:** Accessible via `http://localhost/admin`
*   **Interface Client:** Accessible via `http://localhost/client`

Les identifiants de connexion par défaut dépendront des données d'exemple (`sample_data.sql`) ou devront être créés manuellement.

---

## Structure des Fichiers (Détaillée)

```
tree: |____api
| |____.htaccess
| |____admin
| | |____auth.php
| | |____companies.php
| | |____contracts.php
| | |____services.php
| | |____users.php
| |____client
| | |____appointments.php
| | |____auth.php
| | |____contracts.php
| | |____profile.php
| | |____services.php
| |____index.php
|____assets
| |____css
| | |____admin.css
| | |____client.css
| |____fonts
| |____images
| | |____.gitkeep
| | |____cover
| | | |____cucumber.jpg
| | | |____goldOnBlack.jpg
| | | |____goldOnBlackSimpleLogo.jpg
| | | |____peach.jpg
| | | |____sky.gif
| | |____exemple
| | | |____mugs.png
| | | |____sacs.png
| | |____logo
| | | |____goldOnBlack.jpg
| | | |____goldOnWhite.jpg
| | | |____noBgBlack.png
| | | |____noBgColor.png
| | | |____noBgWhite.png
| | | |____no_padding.png
| | | |____whiteBgColor.png
| |____js
| | |____admin.js
| | |____client.js
|____build
|____cloud-services
|____composer.json
|____database
| |____migrations
| |____schemas
| | |____business_care.sql
| | |____triggers.sql
| | |____views.sql
| |____seeders
| | |____sample_data.sql
| |____setup.sql
|____desktop-app
|____i18n
|____infrastructure
|____integrations
|____java-app
|____mobile-app
|____phpdoc.xml
|____phpDocumentor.phar
|____README.md
|____shared
| |____web-admin
| | |____auth.php
| | |____config.php
| | |____db.php
| | |____functions.php
| | |____logging.php
| |____web-client
| | |____auth.php
| | |____config.php
| | |____db.php
| | |____functions.php
| | |____logging.php
|____tests
|____web-admin
| |____includes
| | |____init.php
| | |____page_functions
| | | |____dashboard.php
| | | |____login.php
| | | |____modules
| | | | |____appointments.php
| | | | |____billing.php
| | | | |____companies.php
| | | | |____contracts.php
| | | | |____donations.php
| | | | |____providers.php
| | | | |____quotes.php
| | | | |____services.php
| | | | |____users.php
| |____index.php
| |____install-admin.php
| |____login.php
| |____logout.php
| |____modules
| | |____appointments
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____billing
| | | |____actions.php
| | | |____index.php
| | | |____view.php
| | |____companies
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____contracts
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____donations
| | | |____view.php
| | |____moderation
| | |____newsletter
| | |____offices
| | |____providers
| | | |____actions.php
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____edit_habilitation.php
| | | |____index.php
| | | |____view.php
| | |____quotes
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____services
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____users
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| |____README.md
| |____templates
| | |____footer.php
| | |____header.php
| | |____sidebar.php
|____web-client
| |____includes
| | |____init.php
| | |____page_functions
| | | |____login.php
| | | |____modules
| | | | |____companies.php
| | | | |____employees.php
| | | | |____providers.php
| |____index.php
| |____login.php
| |____logout.php
| |____modules
| | |____companies
| | | |____contact.php
| | | |____contracts.php
| | | |____employees.php
| | | |____index.php
| | | |____invoices.php
| | | |____quotes.php
| | | |____settings.php
| | |____employees
| | | |____appointments.php
| | | |____communities.php
| | | |____donations.php
| | | |____edit.php
| | | |____events.php
| | | |____history.php
| | | |____index.php
| | | |____reservations.php
| | | |____services.php
| | | |____settings.php
| | | |____view.php
| | |____providers
| | | |____calendar.php
| | | |____categories.php
| | | |____contracts.php
| | | |____index.php
| | | |____invoices.php
| | | |____ratings.php
| | | |____search.php
| | | |____services.php
| | | |____settings.php
| | | |____view.php
| |____README.md
| |____templates
| | |____footer.php
| | |____header.php
