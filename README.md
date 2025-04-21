# Business-Care
Projet annuel de deuxième année

## Aperçu du Projet

Business-Care est une application web conçue pour gérer les interactions et services entre une organisation administratrice, des entreprises clientes, leurs employés, et potentiellement des prestataires de services. Elle permet de gérer des entités telles que les entreprises, les utilisateurs (admins, employés, prestataires), les contrats, les services proposés, les devis, la facturation et les rendez-vous.

## Stack Technologique

*   **Backend (Web & API):** PHP 8.1 (Application principale et API REST distincte dans `/api`)
*   **Backend (Reporting):** Java 17 (Application autonome dans `/java-app` utilisant Maven, iText, JFreeChart)
*   **Frontend:** PHP, HTML, CSS, JavaScript (Interfaces séparées pour Admin dans `/web-admin` et Client dans `/web-client`)
*   **Base de données:** MySQL 8.0 (Schéma, vues, triggers et données initiales dans `/database`)
*   **Serveur Web:** Nginx (Configuration dans `docker/nginx/default.conf`)
*   **Dépendances PHP:** Gérées via Composer (`composer.json`)
*   **Dépendances Java:** Gérées via Maven (`java-app/pom.xml`), utilise `maven-shade-plugin` pour créer un JAR exécutable ("fat JAR").
*   **Conteneurisation:** Docker & Docker Compose (Configuration dans `Dockerfile`, `java-app/Dockerfile`, `docker-compose.yml`, `docker/`)

## Structure du Projet

Le projet est organisé dans les répertoires principaux suivants :

*   `api/`: Contient les points d'accès de l'API REST backend (service distinct).
*   `assets/`: Fichiers statiques (CSS, JS, images, polices) servis directement par Nginx.
*   `database/`: Schémas de base de données, migrations (si applicable), vues, triggers, seeders et scripts d'initialisation.
*   `docker/`: Contient la configuration spécifiques à Docker (Dockerfile Nginx, PHP, script d'init DB).
*   `java-app/`: Contient une application Java autonome (basée sur Maven) pour générer des rapports PDF périodiques basés sur les données de l'API. Le point d'entrée est `src/main/java/com/businesscare/reporting/main/ReportApplication.java`. Voir `java-app/README.md` pour plus de détails.
*   `shared/`: Code PHP partagé (configuration, connexion BDD, fonctions utilitaires, authentification, logging) utilisé par `web-admin` et `web-client`.
*   `web-admin/`: Interface d'administration (application PHP).
*   `web-client/`: Interface destinée aux clients/employés (application PHP).
*   `i18n/`: Fichiers d'internationalisation/localisation.
*   `tests/`: Tests de l'application.
*   `cloud-services/`, `infrastructure/`, `integrations/`, `mobile-app/`, `desktop-app/`: Répertoires de placeholders ou sous-projets liés.

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

        # Variables optionnelles pour l'authentification de l'app Java à l'API PHP
        # API_USER=admin@businesscare.fr
        # API_PASSWORD=admin123
        ```
    *   **Important:** Ajoutez `.env` à votre fichier `.gitignore` pour éviter de commiter des secrets.
4.  **Construire et Lancer les Conteneurs:**
    *   Depuis la racine du projet, exécutez :
        ```bash
        docker compose up --build -d
        ```
    *   Cette commande va construire les images nécessaires (PHP, Java via `java-app/Dockerfile`), télécharger les images Nginx et MySQL, et démarrer les services définis dans `docker-compose.yml` en arrière-plan (`-d`).
    *   Le service `java-app` est configuré pour :
        *   Compiler l'application Java et créer un JAR exécutable (via `maven-shade-plugin` dans `pom.xml` lors de la construction de l'image `java-app/Dockerfile`).
        *   Exécuter le JAR (`java -jar /app/app.jar`) au démarrage du conteneur. Cette application Java est responsable de générer périodiquement (à terme) un rapport PDF.
        *   Utiliser la variable d'environnement `API_BASE_URL=http://nginx/api/admin` (définie dans `docker-compose.yml`) pour communiquer avec l'API PHP.
        *   Utiliser les variables `API_USER` et `API_PASSWORD` de `.env` si définies, sinon les valeurs par défaut codées dans l'app Java, pour l'authentification à l'API.
        *   Monter uniquement le répertoire local `./java-app/output` sur `/app/output` dans le conteneur. Cela permet au rapport généré par l'application Java dans `/app/output` (par exemple `report.pdf`) d'être accessible sur l'hôte dans `./java-app/output/`, sans écraser le JAR de l'application dans `/app/`.
5.  **Initialisation de la Base de Données:**
    *   Lors du premier démarrage du service `db`, le script `docker/db/init.sh` sera automatiquement exécuté.
    *   Ce script crée la base de données, importe le schéma (`business_care.sql`), les vues (`views.sql`), les triggers (`triggers.sql`), crée les utilisateurs (`business_care_user`, `business_care_backup`) et importe les données d'exemple (`sample_data.sql`) si elles existent.
    *   Pour vérifier que l'initialisation s'est correctement déroulée, exécutez :`docker compose logs db | grep "Init complete"`
    *   Si vous ne voyez pas ce message, vérifiez les logs complets pour identifier l'erreur : `docker compose logs db`
6.  **Accéder à l'Application:**
    *   L'application devrait maintenant être accessible dans votre navigateur :
        *   Interface Admin : `http://localhost/admin`
        *   Interface Client : `http://localhost/client` (ou simplement `http://localhost/` qui pourrait rediriger)
    *   Vérifiez que les conteneurs sont bien en cours d'exécution :
        ```bash
        docker compose ps
        ```
    *   Si l'un des conteneurs n'est pas à l'état "Up", consultez ses logs :
        ```bash
        docker compose logs [nom_du_service]
        ```

---

### Installation Manuelle (Alternative)

_(Non recommandée si Docker est disponible. Sert principalement de référence.)_

1.  **Prérequis:**
    *   PHP 8.1+ (avec extensions `pdo_mysql`, `zip`, etc.)
    *   MySQL 8.0+
    *   Composer 2+
    *   Serveur Web (Nginx ou Apache avec `mod_rewrite`)
    *   Java 17+ (JDK)
    *   Maven
2.  **Cloner le dépôt:** `git clone <url-du-depot>`
3.  **Installer les dépendances PHP:** `cd Business-Care && composer install`
4.  **Compiler l'application Java:** `cd java-app && mvn clean package` (Voir `java-app/README.md`)
5.  **Configurer la base de données:**
    *   Créez une base de données MySQL (ex: `business_care`).
    *   Créez les utilisateurs nécessaires (ex: `business_care_user`).
    *   Importez les schémas, vues, triggers depuis `database/schemas/`.
    *   Optionnellement, exécutez les seeders depuis `database/seeders/`.
6.  **Configurer la connexion BDD:** Mettre à jour les identifiants dans `shared/web-admin/config.php` et `shared/web-client/config.php`.
7.  **Configurer le serveur web:**
    *   Configurez Nginx ou Apache.
    *   Définissez la racine du projet comme document root ou utilisez des alias/rewrite rules pour mapper `/admin` à `web-admin/`, `/client` à `web-client/`, `/api` à `api/`, `/assets` à `assets/`, et potentiellement `/java-app/output` pour servir le rapport PDF.
    *   Assurez-vous que la réécriture d'URL est activée et configurée pour gérer les requêtes PHP et les routes API (voir `docker/nginx/default.conf` comme exemple pour Nginx).
8.  **Exécuter l'application Java:** (Voir `java-app/README.md` pour les détails et la configuration des variables d'environnement `API_BASE_URL`, `API_USER`, `API_PASSWORD`). L'exécution est manuelle ou via une tâche planifiée.

## Utilisation

Une fois l'installation Docker terminée :

*   **Interface Admin:** Accessible via `http://localhost/admin`
*   **Interface Client:** Accessible via `http://localhost/client`
*   **Rapport PDF (Java):** L'application Java (`java-app`) s'exécute au démarrage du conteneur Docker. Une fois la logique de génération implémentée, elle écrira le fichier `report.pdf` dans `/app/output` à l'intérieur du conteneur, qui sera visible sur votre machine hôte dans `./java-app/output/report.pdf` grâce au montage de volume configuré. L'accès au rapport depuis l'interface `web-admin` nécessitera une configuration Nginx supplémentaire (voir exemple dans `docker/nginx/default.conf` pour servir `/java-app/output`) et un lien approprié dans l'interface admin.