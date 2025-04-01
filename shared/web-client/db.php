<?php
require_once 'config.php';

/**
 * Retourne une instance PDO pour la connexion à la base de données.
 *
 * La connexion est établie en utilisant les constantes de configuration (DB_HOST, DB_NAME, DB_CHARSET, DB_USER, DB_PASS)
 * et est mise en cache pour éviter des connexions répétées. En cas d'erreur lors de l'établissement de la connexion,
 * le script est interrompu avec un message adapté au mode DEBUG.
 *
 * @return PDO La connexion PDO active.
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
            die("[FAILURE] Connexion a la base de donnees impossible: " . $e->getMessage());
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
        throw new Exception("[FAILURE] Nom de table invalide");
    }
    return $table;
}

/**
 * Exécute une requête SQL préparée via PDO
 *
 * @param string $sql Requête SQL à préparer et exécuter
 * @param array $params Valeurs à lier aux paramètres de la requête
 * @return PDOStatement Instance de PDOStatement représentant le résultat de la requête
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("[FAILURE] Impossible d'executer la requete: " . $e->getMessage());
    }
}

/**
 * Compte le nombre d'enregistrements d'une table, avec une clause WHERE optionnelle
 *
 * @param string $table Nom de la table
 * @param string $where Clause SQL optionnelle pour filtrer les enregistrements
 * @return int Nombre d'enregistrements répondant aux critères
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
 * Récupère l'ensemble des enregistrements d'une table en appliquant des filtres optionnels
 *
 * @param string $table Nom de la table cible
 * @param string $where (Optionnel) Clause WHERE pour filtrer les enregistrements
 * @param string $orderBy (Optionnel) Clause ORDER BY pour trier les résultats
 * @param int $limit (Optionnel) Nombre maximum de lignes à récupérer. Une valeur de 0 désactive la limite
 * @param int $offset (Optionnel) Position de départ pour la récupération des enregistrements
 * @return array Tableau contenant l'ensemble des enregistrements récupérés
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
 * Récupère le premier enregistrement d'une table selon une condition donnée
 *
 * @param string $table Nom de la table concernée
 * @param string $where Condition SQL pour filtrer les enregistrements
 * @param string $orderBy Clause SQL pour ordonner les résultats (facultative)
 * @return array|false Tableau associatif représentant l'enregistrement trouvé ou false si aucun enregistrement ne correspond
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
 * Insère une nouvelle ligne dans la table spécifiée
 *
 * @param string $table Nom de la table dans laquelle insérer la nouvelle ligne
 * @param array $data Tableau associatif des colonnes et valeurs à insérer
 * @return int|false Identifiant de la ligne insérée ou false si l'insertion échoue
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
 * Supprime des lignes de la table spécifiée
 *
 * @param string $table Nom de la table depuis laquelle supprimer les lignes
 * @param string $where Clause WHERE déterminant les critères de suppression
 * @param array $params Valeurs associées à la clause WHERE
 * @return int Le nombre de lignes supprimées
 */
function deleteRow($table, $where, $params = []) {
    $table = validateTableName($table);
    
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Démarre une transaction SQL
 * 
 * @return void
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