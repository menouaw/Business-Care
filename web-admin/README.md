# Business Care - Panneau d'Administration

Le panneau d'administration est la partie backend permettant de gerer toutes les fonctionnalites de Business Care.

## Structure du projet

```
web-admin/                  # Dossier principal de l'administration
├── includes/               # Fichiers d'inclusion PHP
│   ├── config.php          # Configuration de l'application
│   ├── db.php              # Fonctions de base de donnees
│   ├── auth.php            # Fonctions d'authentification
│   └── functions.php       # Fonctions utilitaires
├── modules/                # Modules fonctionnels
│   ├── users/              # Gestion des utilisateurs
│   ├── companies/          # Gestion des entreprises
│   ├── contracts/          # Gestion des contrats
│   ├── services/           # Gestion des services
│   ├── financial/          # Gestion financiere
│   └── reports/            # Rapports et statistiques
├── templates/              # Modeles d'interface
│   ├── header.php          # En-tete commune
│   ├── footer.php          # Pied de page commun
│   └── sidebar.php         # Barre laterale de navigation
├── index.php               # Page d'accueil/tableau de bord
├── login.php               # Page de connexion
└── logout.php              # Script de deconnexion
```

## API REST

L'API REST se trouve dans le dossier `/api` a la racine du projet et comprend les points d'entree suivants :

```
/api/                      # Point d'entree principal
├── admin/                 # Endpoints pour l'administration
│   ├── users.php          # Gestion des utilisateurs
│   ├── companies.php      # Gestion des entreprises
│   ├── contracts.php      # Gestion des contrats
│   ├── services.php       # Gestion des services
│   ├── auth.php           # Authentification
```

### Points d'entree API disponibles

- `GET /api/users` - Liste des utilisateurs (avec pagination)
- `GET /api/users/{id}` - Detail d'un utilisateur
- `POST /api/users` - Creation d'un utilisateur
- `PUT /api/users/{id}` - Mise a jour d'un utilisateur
- `DELETE /api/users/{id}` - Suppression d'un utilisateur

- `POST /api/auth` - Authentification
- `PUT /api/auth` - Modification de mot de passe
- `DELETE /api/auth` - Deconnexion

## Configuration

1. Creer une base de donnees MySQL
2. Importer le script SQL depuis `/database/setup.sql`
3. Modifier les parametres de connexion dans `/web-admin/includes/config.php`

## Fonctionnalites principales

- Tableau de bord avec statistiques
- Gestion des utilisateurs
- Gestion des entreprises
- Gestion des contrats
- Gestion des services
- Gestion financiere
- Rapports et statistiques

## Developpement

### Prerequis

- PHP 7.4 ou superieur
- MySQL 5.7 ou superieur
- Serveur web Apache avec mod_rewrite active 

## Organisation du code

### Structure des fichiers

Pour maintenir une organisation claire du code, les fonctions spécifiques aux pages sont séparées dans des fichiers dédiés:

- `/web-admin/includes/` - Contient les fichiers principaux du système
  - `config.php` - Configuration générale
  - `db.php` - Connexion à la base de données et fonctions de requêtes
  - `auth.php` - Fonctions d'authentification
  - `functions.php` - Fonctions utilitaires générales
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