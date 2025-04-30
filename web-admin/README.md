# Business Care - Panneau d'Administration

Panneau d'administration backend pour la gestion de Business Care (entreprises, utilisateurs, contrats, services, etc.). Cette application est conçue pour fonctionner dans un environnement Dockerisé, mais peut également être exécutée dans un environnement LAMP/WAMP traditionnel.

## Structure Principale

```
web-admin/
├── includes/           # Fichiers PHP Core
│   ├── init.php        # Initialisation (config, db, session, auth via /shared)
│   └── page_functions/ # Logique métier (fonctions par module)
│       ├── dashboard.php   # Fonctions spécifiques au tableau de bord
│       ├── login.php       # Fonctions de traitement de connexion
│       └── modules/        # Fonctions spécifiques aux modules
│           ├── appointments.php     # Gestion des rendez-vous
│           ├── billing.php          # Gestion des factures
│           ├── companies.php        # Gestion des entreprises
│           ├── contracts.php        # Gestion des contrats
│           ├── donations.php        # Gestion des dons
│           ├── financial.php        # Gestion financière
│           ├── providers.php        # Gestion des prestataires
│           ├── quotes.php           # Gestion des devis
│           ├── services.php         # Gestion des services/prestations
│           └── users.php            # Gestion des utilisateurs
├── modules/            # Modules fonctionnels (vues/logique de présentation)
│   ├── appointments/   # Gestion des rendez-vous
│   ├── billing/        # Gestion des factures
│   ├── companies/      # Gestion des entreprises (liste, ajout, édition, suppression)
│   ├── contracts/      # Gestion des contrats
│   ├── donations/      # Gestion des dons
│   ├── financial/      # Module financier
│   ├── moderation/     # Modération du contenu
│   ├── newsletter/     # Gestion des newsletters
│   ├── offices/        # Gestion des bureaux
│   ├── providers/      # Gestion des prestataires
│   ├── quotes/         # Gestion des devis
│   ├── services/       # Gestion des services
│   └── users/          # Gestion des utilisateurs
├── templates/          # Templates HTML communs
│   ├── header.php      # En-tête HTML avec barre de navigation
│   ├── footer.php      # Pied de page HTML avec scripts JS
│   └── sidebar.php     # Menu latéral avec navigation
├── index.php           # Point d'entrée / Tableau de bord
├── login.php           # Page de connexion admin
├── logout.php          # Traitement de déconnexion
├── error.php           # Gestion des erreurs HTTP
└── install-admin.php   # Configuration initiale (obsolète)
```

**Fichiers Partagés :** Les composants essentiels partagés se trouvent dans `/shared/web-admin/` et sont inclus par `includes/init.php`. Ils comprennent :

- `config.php` - Configuration globale (URLs, constantes, rôles, tables)
- `db.php` - Fonctions de connexion et d'interaction avec la base de données
- `auth.php` - Système d'authentification (login, logout, vérification rôles)
- `functions.php` - Utilitaires communs (formatage, validation, pagination)
- `logging.php` - Journalisation des évènements et activités

## Flux d'Exécution Typique

1. L'utilisateur accède à une page (ex: `modules/companies/index.php`)
2. Le fichier inclut `../../includes/init.php` qui charge les fichiers partagés
3. La page vérifie l'authentification et les permissions via `requireRole(ROLE_ADMIN)`
4. Les fonctions spécifiques au module sont importées de `includes/page_functions/modules/companies.php`
5. La logique est exécutée (ex: récupération de la liste des entreprises)
6. Le template `header.php` est inclus pour commencer le rendu HTML
7. Le template `sidebar.php` est inclus pour afficher le menu de navigation
8. Le contenu spécifique à la page est généré
9. Le template `footer.php` est inclus pour terminer le rendu HTML

## Modules Principaux

- **Tableau de Bord** (`index.php`) - Vue d'ensemble avec statistiques et activités récentes
- **Utilisateurs** (`modules/users/`) - Gestion des administrateurs, prestataires et utilisateurs clients
- **Entreprises** (`modules/companies/`) - Gestion des entreprises clientes
- **Contrats** (`modules/contracts/`) - Gestion des contrats entre entreprises et services
- **Services** (`modules/services/`) - Gestion des services et prestations proposés
- **Prestataires** (`modules/providers/`) - Gestion des prestataires et fournisseurs
- **Facturation** (`modules/billing/`) - Gestion des factures et paiements
- **Devis** (`modules/quotes/`) - Gestion des devis pour les clients
- **Rendez-vous** (`modules/appointments/`) - Planification et suivi des rendez-vous
- **Dons** (`modules/donations/`) - Gestion des dons
- **Finance** (`modules/financial/`) - Suivi financier et reporting

## Architecture Technique

- **Pattern MVC Léger** - Séparation entre les fonctions métier (`includes/page_functions/`) et les vues (`modules/`)
- **Bibliothèques Frontend** - Bootstrap 5, Font Awesome 6, Chart.js
- **Accès Base de Données** - Couche d'abstraction PDO avec requêtes préparées via `shared/web-admin/db.php`
- **Sécurité** - Protection CSRF, validation des entrées, authentification par session
- **Logging** - Système de journalisation des activités et évènements de sécurité

## Setup (Docker Recommandé)

**Prérequis :**
- Docker & Docker Compose

**Installation via Docker :**
1. Assurez-vous que Docker Desktop (ou équivalent) est en cours d'exécution
2. Configurez les variables d'environnement nécessaires dans le fichier `.env` à la racine du projet
3. Depuis la racine du projet, exécutez : `docker-compose up -d --build`
4. Le panneau d'administration sera accessible via `http://localhost/admin`

**Installation Manuelle (Alternative) :**
1. Prérequis : PHP >= 7.4, MySQL >= 5.7, Serveur web (Apache/Nginx)
2. Configurez votre serveur web pour pointer vers le dossier racine du projet
3. Importez les schémas depuis `/database/schemas/`
4. Configurez la connexion BDD dans `/shared/web-admin/config.php`

## Développement

**Ajouter un Module :**
1. Créer un dossier pour le module (ex: `modules/nouveau_module/`)
2. Créer le fichier de fonctions métier (ex: `includes/page_functions/modules/nouveau_module.php`)
3. Créer les pages CRUD nécessaires (ex: `index.php`, `add.php`, `edit.php`, `delete.php`, `view.php`)
4. Ajouter une entrée dans le menu latéral (`templates/sidebar.php`)

**Conventions de Codage :**
- Préfixer les fonctions métier avec le nom du module (ex: `usersGetList()`, `companiesCreate()`)
- Utiliser les fonctions de validation et de nettoyage (`sanitizeInput()`, `validateInput()`)
- Consigner les évènements importants avec les fonctions de journalisation
- Vérifier les permissions requises au début de chaque page

## Sécurité

- **Authentification** - Système complet de session avec fonction "Se souvenir de moi"
- **Autorisation** - Vérification des rôles via `requireRole(ROLE_ADMIN)` et `hasRole()`
- **Protection Données** - Requêtes préparées PDO et validation des entrées
- **CSRF** - Protection via tokens de formulaire (`generateToken()`, `validateToken()`)
- **Journalisation** - Suivi des tentatives d'accès et actions sensibles
- **Validation** - Nettoyage des entrées utilisateur avec `sanitizeInput()`

## External Resources

- **Bibliothèques Frontend** :
  - Bootstrap 5 (CDN) - Framework CSS pour l'interface responsive
  - Font Awesome 6 (CDN) - Icônes vectorielles
  - Chart.js (CDN) - Génération de graphiques pour le tableau de bord et les rapports

---

## Note sur l'API REST

L'application expose une API REST sous `/api/admin/` qui permet l'accès programmatique aux données pour d'autres clients, notamment l'application Java de reporting (`/java-app/`). Cette API est implémentée dans le dossier `/api/admin/` et nécessite une authentification par token Bearer.

Pour plus de détails sur les endpoints disponibles et leur utilisation, consultez `/api/admin/README.md`. 