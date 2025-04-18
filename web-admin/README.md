# Business Care - Panneau d'Administration

Panneau d'administration backend pour la gestion de Business Care (entreprises, utilisateurs, contrats, services, etc.). S'exécute dans un environnement Dockerisé.

## Structure Principale

```
web-admin/
├── includes/           # Fichiers PHP Core
│   ├── init.php        # Initialisation (config, db, session, via /shared)
│   └── page_functions/ # Logique métier (fonctions par module)
│       └── modules/    # ex: contracts.php, users.php
├── modules/            # Modules fonctionnels (vues/logique de présentation)
│   ├── users/          # Gestion utilisateurs
│   ├── companies/      # Gestion entreprises
│   ├── contracts/      # Gestion contrats
│   └── ...             # Autres modules (services, quotes, etc.)
├── templates/          # Templates HTML communs
│   ├── header.php      # En-tête HTML
│   ├── footer.php      # Pied de page HTML
│   └── sidebar.php     # Menu latéral
├── assets/             # Ressources statiques (CSS, JS, images) -> Servi par Nginx via /assets
├── index.php           # Point d'entrée / Tableau de bord
├── login.php           # Connexion admin
├── logout.php          # Déconnexion admin
└── install-admin.php   # Obsolète
```

**Fichiers Partagés :** Les fichiers essentiels partagés se trouvent dans `/shared/web-admin/` et sont inclus par `includes/init.php`. Ils comprennent `config.php` (configuration), `db.php` (connexion BDD), `auth.php` (authentification), `functions.php` (utilitaires), et `logging.php` (journalisation). Ces fichiers sont la base du fonctionnement de l'application admin.

## API REST (Service Externe)

Le projet principal expose une API REST distincte sous `/api/admin/` pour la gestion des ressources (utilisateurs, entreprises, etc.). **Ce panneau d'administration (`web-admin`) n'utilise pas directement cette API REST pour ses opérations internes.** Il s'appuie plutôt sur les fonctions PHP définies dans `includes/page_functions/` et les fichiers partagés (`shared/web-admin/`).

Les endpoints `/api/admin/*` existent comme un service séparé qui *pourrait* être consommé par d'autres clients ou potentiellement par ce panneau dans le futur.

_(Voir la section détaillée plus bas pour les endpoints de ce service API externe)_.

## Setup (Docker Recommandé)

**Prérequis :**
- Docker & Docker Compose

**Installation via Docker (Méthode Principale) :**
1.  Assurez-vous que Docker Desktop (ou équivalent) est en cours d'exécution.
2.  Configurez les variables d'environnement nécessaires dans le fichier `.env` à la racine du projet (notamment les mots de passe pour la base de données `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `MYSQL_BACKUP_PASSWORD`). **Ne commitez pas `.env` dans Git.**
3.  Depuis la racine du projet, exécutez : `docker-compose up -d --build`
4.  Le panneau d'administration devrait être accessible via `http://localhost/admin` (ou l'URL configurée dans Nginx).
5.  La base de données sera automatiquement initialisée par le script `docker/db/init.sh` lors du premier démarrage du conteneur `db`.

**Installation Manuelle (Alternatif) :**
_(Non recommandée si Docker est utilisé)_
1.  Prérequis : PHP >= 7.4, MySQL >= 5.7, Serveur web (Apache/Nginx), Composer.
2.  Cloner le dépôt.
3.  Créer une base de données MySQL (ex: `business_care`).
4.  Importer les schémas depuis `/database/schemas/`.
5.  Configurer la connexion BDD dans `/shared/web-admin/config.php`.
6.  Exécuter `composer install` à la racine du projet.
7.  Configurer le serveur web pour pointer vers le dossier racine du projet et gérer la réécriture d'URL si nécessaire (voir exemple `docker/nginx/default.conf`).

## Fonctionnalités Clefs

- Tableau de bord
- Gestion Utilisateurs (admins, prestataires, salariés clients)
- Gestion Entreprises & Contrats associés
- Gestion Prestataires (fournisseurs de services)
- Gestion Services & Prestations proposées
- Gestion Devis
- Gestion Dons
- Suivi Rendez-vous & Événements (si applicable au module admin)
- Gestion Financière (facturation, suivi paiements via Stripe)
- Rapports & Statistiques basiques
- Journalisation des activités importantes via `logging.php`

## Développement

**Architecture :**
- **Logique Métier :** Fonctions PHP dans `/includes/page_functions/modules/` (ex: `contractsGetList()`). Ces fonctions interagissent avec la base de données via les fonctions de `/shared/web-admin/db.php`.
- **Présentation :** Fichiers PHP dans `/modules/` pour l'affichage HTML. Ils incluent les templates et appellent les fonctions métier pour récupérer les données à afficher.
- **Templates Communs :** `header.php`, `footer.php`, `sidebar.php` sont inclus dans les fichiers de vue des modules pour une structure cohérente.
- **Initialisation :** `includes/init.php` est inclus au début des points d'entrée (`index.php`, `login.php`, pages des modules) pour charger la configuration, démarrer la session, et établir la connexion BDD.

**Ajouter un Module :**
1.  Créer un dossier pour le nouveau module, par exemple `/modules/nouveau_module/`.
2.  Créer le fichier de fonctions PHP correspondant dans `/includes/page_functions/modules/nouveau_module.php`. Définir les fonctions pour lire/écrire les données de ce module.
3.  Créer les fichiers de vue nécessaires (ex: `index.php` pour la liste, `edit.php` pour l'édition) dans `/modules/nouveau_module/`. Ces fichiers incluront `init.php` et les templates, et appelleront les fonctions créées à l'étape 2.
4.  Ajouter une entrée dans le menu latéral (`templates/sidebar.php`) si nécessaire.
5.  Mettre à jour les routes ou la logique de routage si applicable (actuellement basé sur la structure des dossiers).

**Journalisation :**
Utiliser les fonctions définies dans `/shared/web-admin/logging.php` :
- `logActivity()`: Actions utilisateur standard (ex: modification d'un contrat).
- `logSystemActivity()`: Événements système (ex: démarrage service).
- `logSecurityEvent()`: Événements de sécurité (ex: connexion échouée).
- `logBusinessOperation()`: Opérations métier critiques (ex: traitement paiement).
Les logs sont stockés dans la table `logs` de la base de données.

## Sécurité

- **Validation/Nettoyage des entrées :** Utilisation probable de fonctions comme `htmlspecialchars()` pour l'affichage (protection XSS) et potentiellement des fonctions de nettoyage personnalisées ou `filter_var` pour les données entrantes (voir `shared/web-admin/functions.php`).
- **Gestion des Sessions :** Gérée via `session_start()` dans `init.php` et les fonctions dans `shared/web-admin/auth.php` (`login()`, `logout()`, `isAuthenticated()`). Vérifier la configuration de la durée de vie (`SESSION_LIFETIME`) et des cookies de session.
- **Hachage des Mots de Passe :** Utilisation de `password_hash()` (avec `PASSWORD_DEFAULT` ou un algorithme robuste) et `password_verify()` (implémenté dans `shared/web-admin/auth.php`).
- **Protection CSRF :** Implémentation via des jetons synchronisés (`generateToken()`, `validateToken()` dans `shared/web-admin/functions.php` ou `auth.php`). À vérifier dans les formulaires POST.
- **Contrôle d'Accès :** Vérifications des rôles/permissions via `isAuthenticated()` et potentiellement des fonctions de vérification de rôle plus spécifiques dans `auth.php` ou les `page_functions`.
- **Journalisation Sécurité :** Utilisation de `logSecurityEvent()`.
- **Configuration Serveur :** Protection des dossiers sensibles (ex: `/shared/`) via la configuration Nginx (`deny all;`). HTTPS doit être configuré en production.

---

## Détail API Endpoints (Service API Externe)

*(Rappel : Ces endpoints appartiennent au service API REST distinct situé sous `/api/admin/` et ne sont pas servis directement par ce panneau d'administration)*

### Authentification API
- `POST /api/admin/auth/login`
- `PUT /api/admin/auth/password`
- `DELETE /api/admin/auth/logout`

### Gestion des utilisateurs API
- `GET /api/admin/users`
- `GET /api/admin/users/{id}`
- `POST /api/admin/users`
- `PUT /api/admin/users/{id}`
- `DELETE /api/admin/users/{id}`

### Gestion des entreprises API
- `GET /api/admin/companies`
- `GET /api/admin/companies/{id}`
- `POST /api/admin/companies`
- `PUT /api/admin/companies/{id}`
- `DELETE /api/admin/companies/{id}`

### Gestion des contrats API
- `GET /api/admin/contracts`
- `GET /api/admin/contracts/{id}`
- `POST /api/admin/contracts`
- `PUT /api/admin/contracts/{id}`
- `DELETE /api/admin/contracts/{id}`

### Gestion des services API
- `GET /api/admin/services`
- `GET /api/admin/services/{id}`
- `POST /api/admin/services`
- `PUT /api/admin/services/{id}`
- `DELETE /api/admin/services/{id}` 