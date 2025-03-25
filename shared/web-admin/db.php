<?php
require_once 'config.php';

/**
 * Récupère l'instance unique de connexion PDO à la base de données.
 *
 * Si aucune connexion n'existe, la fonction en initialise une en utilisant les constantes de configuration
 * (DB_HOST, DB_NAME, DB_CHARSET, DB_USER, DB_PASS) et les options PDO recommandées. En cas d'échec, le script
 * s'arrête et affiche un message d'erreur dont le détail varie selon le mode DEBUG.
 *
 * @return PDO L'objet PDO représentant la connexion à la base de données.
 */
function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("[ERREUR] Connexion a la base de donnees impossible: " . $e->getMessage());
            } else {
                die("[ERREUR] Connexion a la base de donnees impossible. Veuillez reessayer plus tard.");
            }
        }
    }
    return $pdo;
}

/**
 * Valide le nom d'une table pour éviter les injections SQL
 * 
 * @param string $table Nom de table à valider
 * @return string Nom de table validé
 * @throws Exception Si le nom de table est invalide
 */
function validateTableName($table) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new Exception("Nom de table invalide");
    }
    return $table;
}

/**
 * Exécute une requête SQL préparée et retourne l'objet PDOStatement associé.
 *
 * Cette fonction prépare et exécute la requête SQL fournie avec les paramètres spécifiés. En cas d'erreur d'exécution, le script s'arrête et affiche un message d'erreur dépendant du mode de débogage.
 *
 * @param string $sql La requête SQL à préparer et exécuter.
 * @param array $params Les valeurs à lier aux paramètres de la requête.
 * @return PDOStatement L'objet résultant de l'exécution de la requête.
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("[ERREUR] Impossible d'executer la requete: " . $e->getMessage());
        } else {
            die("[ERREUR] Une erreur est survenue lors du traitement de votre requete.");
        }
    }
}

/**
 * Compte le nombre de lignes dans une table avec condition optionnelle
 * 
 * @param string $table Nom de la table
 * @param string $where Clause WHERE (optionnelle)
 * @return int Nombre de lignes trouvées
 */
function countTableRows($table, $where = '') {
    $table = validateTableName($table);
    
    $sql = "SELECT COUNT(*) FROM $table";
    $params = [];
    if ($where) {
        $sql .= " WHERE $where";
    }
    
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Récupère toutes les lignes d'une table avec filtres optionnels
 * 
 * @param string $table Nom de la table
 * @param string $where Clause WHERE (optionnelle)
 * @param string $orderBy Clause ORDER BY (optionnelle)
 * @param int $limit Nombre maximum de lignes à récupérer
 * @param int $offset Position de départ pour la récupération
 * @return array Tableau de résultats
 */
function fetchAll($table, $where = '', $orderBy = '', $limit = 0, $offset = 0) {
    $table = validateTableName($table);
    
    $sql = "SELECT * FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    if ($orderBy) {
        $sql .= " ORDER BY $orderBy";
    }
    if ($limit) {
        $sql .= " LIMIT $limit";
        if ($offset) {
            $sql .= " OFFSET $offset";
        }
    }
    
    $stmt = executeQuery($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère une seule ligne d'une table avec filtre
 * 
 * @param string $table Nom de la table
 * @param string $where Clause WHERE
 * @param string $orderBy Clause ORDER BY (optionnelle)
 * @return array|false Ligne trouvée ou false si aucun résultat
 */
function fetchOne($table, $where, $orderBy = '') {
    $table = validateTableName($table);
    
    $sql = "SELECT * FROM $table WHERE $where";
    if ($orderBy) {
        $sql .= " ORDER BY $orderBy";
    }
    $sql .= " LIMIT 1";
    
    $stmt = executeQuery($sql);
    return $stmt->fetch();
}

/**
 * Insère une nouvelle ligne dans une table
 * 
 * @param string $table Nom de la table
 * @param array $data Données à insérer sous forme de tableau associatif
 * @return int|false ID de la ligne insérée ou false en cas d'échec
 */
function insertRow($table, $data) {
    $table = validateTableName($table);
    
    $fields = array_keys($data);
    $placeholders = array_map(function ($field) {
        return ":$field";
    }, $fields);
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = executeQuery($sql, $data);
    return $stmt->rowCount() > 0 ? getDbConnection()->lastInsertId() : false;
}

/**
 * Met à jour des lignes dans une table
 * 
 * @param string $table Nom de la table
 * @param array $data Données à mettre à jour sous forme de tableau associatif
 * @param string $where Clause WHERE pour cibler les lignes à mettre à jour
 * @param array $whereParams Paramètres pour la clause WHERE
 * @return int Nombre de lignes affectées
 */
function updateRow($table, $data, $where, $whereParams = []) {
    $table = validateTableName($table);
    
    $fields = array_keys($data);
    $setClause = array_map(function ($field) {
        return "$field = :$field";
    }, $fields);
    
    $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE $where";
    
    $params = array_merge($data, $whereParams);
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Supprime des lignes d'une table
 * 
 * @param string $table Nom de la table
 * @param string $where Clause WHERE pour cibler les lignes à supprimer
 * @param array $params Paramètres pour la clause WHERE
 * @return int Nombre de lignes supprimées
 */
function deleteRow($table, $where, $params = []) {
    $table = validateTableName($table);
    
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Démarre une nouvelle transaction SQL.
 *
 * Initialise une transaction en appelant la méthode beginTransaction() sur la connexion PDO active
 * obtenue via getDbConnection(). Cette opération permet de regrouper plusieurs requêtes SQL dans un
 * contexte transactionnel afin d'assurer l'intégrité des données.
 */
function beginTransaction() {
    getDbConnection()->beginTransaction();
}

/**
 * Valide une transaction SQL en cours
 * 
 * @return void
 */
function commitTransaction() {
    getDbConnection()->commit();
}

/**
 * Annule une transaction SQL en cours
 * 
 * @return void
 */
function rollbackTransaction() {
    getDbConnection()->rollBack();
} 