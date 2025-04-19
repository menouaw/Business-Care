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
+    *   Pour vérifier que l'initialisation s'est correctement déroulée, exécutez :`docker compose logs db | grep "Init complete"`
+    *   Si vous ne voyez pas ce message, vérifiez les logs complets pour identifier l'erreur : `docker compose logs db`
6.  **Accéder à l'Application:**
    *   L'application devrait maintenant être accessible dans votre navigateur :
        *   Interface Admin : `http://localhost/admin`
        *   Interface Client : `http://localhost/client` (ou simplement `http://localhost/` qui pourrait rediriger)
+    *   Vérifiez que les conteneurs sont bien en cours d'exécution :
+        ```bash
+        docker compose ps
+        ```
+    *   Si l'un des conteneurs n'est pas à l'état "Up", consultez ses logs :
+        ```bash
+        docker compose logs [nom_du_service]
+        ```

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