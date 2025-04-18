# Business Care - Panneau d'Administration

Panneau d'administration backend pour la gestion de Business Care (entreprises, utilisateurs, contrats, services, etc.).

## Structure Principale

```
web-admin/
├── includes/           # Fichiers PHP Core
│   ├── init.php        # Initialisation (config, db, session)
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
├── assets/             # Ressources statiques (CSS, JS, images)
├── index.php           # Point d'entrée / Tableau de bord
├── login.php           # Connexion admin
├── logout.php          # Déconnexion admin
└── install-admin.php   # Script d'installation
```

**Fichiers Partagés :** Les fichiers essentiels partagés se trouvent dans `/shared/web-admin/` et incluent typiquement `config.php` (configuration), `db.php` (connexion BDD), `auth.php` (authentification), `functions.php` (utilitaires), et `logging.php` (journalisation).

## API REST

Points d'entrée principaux dans `/api/admin/` pour la gestion des ressources (utilisateurs, entreprises, contrats, services) via des méthodes HTTP standard (GET, POST, PUT, DELETE). Authentification via `/api/admin/auth/`.

_(Voir la section détaillée plus bas pour les endpoints spécifiques)_.

## Setup

**Prérequis :**
- PHP >= 7.4
- MySQL >= 5.7
- Serveur web
- Composer

**Installation :**
1.  Cloner le dépôt.
2.  Créer une base de données MySQL.
3.  Importer `/database/schemas/business_care.sql`.
4.  Configurer la connexion BDD dans `/shared/web-admin/config.php`.
5.  Exécuter `composer install` à la racine du projet (si applicable).

## Fonctionnalités Clefs

- Tableau de bord
- Gestion Utilisateurs (admins, prestataires, salariés)
- Gestion Entreprises & Contrats
- Gestion Prestataires
- Gestion Services & Prestations
- Gestion Devis
- Gestion Dons
- Suivi Rendez-vous & Événements
- Gestion Financière (facturation, paiements)
- Rapports & Statistiques
- Journalisation des activités

## Développement

**Architecture :**
- **Logique Métier :** Fonctions PHP dans `/includes/page_functions/modules/` (ex: `contractsGetList()`).
- **Présentation :** Fichiers PHP dans `/modules/` pour l'affichage HTML, appelant les fonctions métier.
- **Templates Communs :** `header.php`, `footer.php`, `sidebar.php` inclus dans les vues des modules.
- **Initialisation :** `includes/init.php` inclus au début des points d'entrée.

**Ajouter un Module :**
1.  Créer un dossier `/modules/nouveau_module/`.
2.  Créer le fichier de fonctions `/includes/page_functions/modules/nouveau_module.php`.
3.  Créer les fichiers de vue (ex: `list.php`, `edit.php`) dans ce dossier.
5.  Appeler les fonctions depuis les fichiers de vue.

**Journalisation :**
Utiliser les fonctions de `/shared/web-admin/logging.php` :
- `logActivity()`: Actions utilisateur standard.
- `logSystemActivity()`: Événements système.
- `logSecurityEvent()`: Événements de sécurité.
- `logBusinessOperation()`: Opérations métier importantes.
(Logs stockés dans la table `logs`).

## Sécurité

- Validation/Nettoyage des entrées (`sanitizeInput()`).
- Sessions sécurisées (`login()`, `logout()`, `isAuthenticated()`).
- Hachage des mots de passe (PASSWORD_DEFAULT).
- Protection XSS (`htmlspecialchars()`).
- Protection CSRF (jetons `generateToken()`, `validateToken()`).
- Expiration des sessions (`SESSION_LIFETIME`).
- Journalisation des événements de sécurité.
- Authentification API par jeton.

---

## Détail API Endpoints (API Principale)

*(Cette section détaille les points d'entrée mentionnés plus haut)*

*(Note: Ces endpoints font référence à l'API REST principale du projet (située dans /api/), que ce panneau d'administration peut consommer, et non à des endpoints servis directement par ce panneau.)*

### Gestion des utilisateurs
- `GET /api/admin/users`
- `GET /api/admin/users/{id}`
- `POST /api/admin/users`
- `PUT /api/admin/users/{id}`
- `DELETE /api/admin/users/{id}`

### Authentification
- `POST /api/admin/auth/login`
- `PUT /api/admin/auth/password`
- `DELETE /api/admin/auth/logout`

### Gestion des entreprises
- `GET /api/admin/companies`
- `GET /api/admin/companies/{id}`
- `POST /api/admin/companies`
- `PUT /api/admin/companies/{id}`
- `DELETE /api/admin/companies/{id}`

### Gestion des contrats
- `GET /api/admin/contracts`
- `GET /api/admin/contracts/{id}`
- `POST /api/admin/contracts`
- `PUT /api/admin/contracts/{id}`
- `DELETE /api/admin/contracts/{id}`

### Gestion des services
- `GET /api/admin/services`
- `GET /api/admin/services/{id}`
- `POST /api/admin/services`
- `PUT /api/admin/services/{id}`
- `DELETE /api/admin/services/{id}` 