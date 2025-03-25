# Business Care - Panneau d'Administration

Le panneau d'administration est la partie backend permettant de gérer toutes les fonctionnalités de Business Care. Cette interface centralise les outils nécessaires à la gestion de l'ensemble des services proposés.

## Structure du projet

```
web-admin/                  # Dossier principal de l'administration
├── includes/               # Fichiers d'inclusion PHP
│   ├── config.php          # Configuration de l'application
│   ├── db.php              # Fonctions de base de données
│   ├── auth.php            # Fonctions d'authentification
│   ├── init.php            # Initialisation du système
│   ├── functions.php       # Fonctions utilitaires
│   └── page_functions/     # Fonctions spécifiques à chaque page
├── modules/                # Modules fonctionnels
│   ├── users/              # Gestion des utilisateurs
│   ├── companies/          # Gestion des entreprises
│   ├── contracts/          # Gestion des contrats
│   └── services/           # Gestion des services
├── templates/              # Modèles d'interface
│   ├── header.php          # En-tête commune
│   ├── footer.php          # Pied de page commun
│   └── sidebar.php         # Barre latérale de navigation
├── assets/                 # Ressources statiques (css, js, images)
├── index.php               # Page d'accueil/tableau de bord
├── login.php               # Page de connexion
├── logout.php              # Script de déconnexion
└── install-admin.php       # Script d'installation
```

## API REST

L'API REST se trouve dans le dossier `/api` à la racine du projet et comprend les points d'entrée suivants :

```
/api/                      # Point d'entrée principal
├── admin/                 # Endpoints pour l'administration
│   ├── users.php          # Gestion des utilisateurs
│   ├── companies.php      # Gestion des entreprises
│   ├── contracts.php      # Gestion des contrats
│   ├── services.php       # Gestion des services
│   ├── auth.php           # Authentification
```

### Points d'entrée API disponibles

- `GET /api/admin/users` - Liste des utilisateurs (avec pagination)
- `GET /api/admin/users/{id}` - Détail d'un utilisateur
- `POST /api/admin/users` - Création d'un utilisateur
- `PUT /api/admin/users/{id}` - Mise à jour d'un utilisateur
- `DELETE /api/admin/users/{id}` - Suppression d'un utilisateur

- `POST /api/admin/auth` - Authentification
- `PUT /api/admin/auth` - Modification de mot de passe
- `DELETE /api/admin/auth` - Déconnexion

- `GET /api/admin/companies` - Liste des entreprises
- `GET /api/admin/contracts` - Liste des contrats
- `GET /api/admin/services` - Liste des services

## Configuration

1. Créer une base de données MySQL
2. Importer le script SQL depuis `/database/setup.sql`
3. Modifier les paramètres de connexion dans `/web-admin/includes/config.php`
4. Exécuter le script d'installation via `/web-admin/install-admin.php`

## Fonctionnalités principales

- Tableau de bord avec statistiques et métriques clés
- Gestion complète des utilisateurs (administrateurs, prestataires)
- Gestion des entreprises clientes et de leurs contrats
- Gestion des services et prestations
- Suivi des événements et des réservations
- Gestion financière (facturation, paiements)
- Rapports et statistiques

## Développement

### Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web Apache avec mod_rewrite activé
- Composer pour la gestion des dépendances

### Installation pour le développement

1. Cloner le dépôt GitHub
2. Créer et configurer la base de données
3. Exécuter `composer install` pour installer les dépendances
4. Configurer le fichier `includes/config.php`
5. Exécuter le script d'installation

## Organisation du code

### Structure des fichiers

Pour maintenir une organisation claire du code, les fonctions spécifiques aux pages sont séparées dans des fichiers dédiés:

- `/web-admin/includes/` - Contient les fichiers principaux du système
  - `config.php` - Configuration générale
  - `db.php` - Connexion à la base de données et fonctions de requêtes
  - `auth.php` - Fonctions d'authentification
  - `functions.php` - Fonctions utilitaires générales
  - `init.php` - Initialisation du système
  - `/page_functions/` - Fonctions spécifiques à chaque page
    - `dashboard.php` - Fonctions pour le tableau de bord
    - `login.php` - Fonctions de traitement du login
    - `/modules/` - Fonctions spécifiques aux modules
      - `companies.php` - Gestion des entreprises
      - `contracts.php` - Gestion des contrats
      - `services.php` - Gestion des services
      - `users.php` - Gestion des utilisateurs

### Directives pour ajouter de nouvelles pages et fonctions

1. Pour chaque nouvelle page:
   - Créer un fichier dans `/includes/page_functions/` portant le nom de la page
   - Y inclure toutes les fonctions spécifiques à cette page
   - Inclure ce fichier en haut de la page correspondante

2. Pour les modules:
   - Créer un fichier dans `/includes/page_functions/modules/` pour chaque module
   - Exemple: `/includes/page_functions/modules/companies.php` pour les fonctions du module entreprises
   - Le nom des fonctions doit commencer par le nom du module (ex: `companiesGetList()`, `companiesGetDetails()`)

3. Règles à suivre:
   - Les fonctions d'une page ne doivent pas être dans le fichier de la page
   - Chaque fichier de fonction doit inclure les dépendances nécessaires
   - Les noms de fonctions doivent être préfixés par le nom de la page ou du module
   - Ajouter de la documentation pour chaque fonction
   - Les fonctions doivent retourner des structures cohérentes (ex: tableau associatif avec clés 'success', 'message', etc.)
   - Séparer clairement la logique métier de la présentation

### Avantages de cette organisation

1. **Meilleure lisibilité du code**: Les fichiers de page sont plus courts et contiennent uniquement la logique de présentation
2. **Réutilisation des fonctions**: Les fonctions peuvent être utilisées dans différents contextes
3. **Facilite la maintenance**: Les modifications de la logique métier sont centralisées
4. **Tests plus simples**: Il est plus facile de tester les fonctions séparément
5. **Évolution plus aisée**: L'ajout de nouvelles fonctionnalités est simplifié

## Sécurité

- Toutes les entrées utilisateur doivent être validées et nettoyées
- L'authentification est gérée via des sessions sécurisées
- Les mots de passe sont hachés avec bcrypt
- Protection contre les attaques XSS et CSRF implémentée
- Les requêtes à l'API nécessitent une authentification par jeton 