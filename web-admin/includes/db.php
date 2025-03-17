<?php
require_once 'config.php';

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
                die("[ERREUR] Connexion à la base de donnees impossible: " . $e->getMessage());
            } else {
                die("[ERREUR] Connexion à la base de donnees impossible. Veuillez reessayer plus tard.");
            }
        }
    }
    return $pdo;
}

function executeQuery($sql, $params = []) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("[ERREUR] Impossible d'executer la requête: " . $e->getMessage());
        } else {
            die("[ERREUR] Une erreur est survenue lors du traitement de votre requête.");
        }
    }
}

function countTableRows($table, $where = '') {
    $sql = "SELECT COUNT(*) FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    
    $stmt = executeQuery($sql);
    return $stmt->fetchColumn();
}

function fetchAll($table, $where = '', $orderBy = '', $limit = 0, $offset = 0) {
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

function fetchOne($table, $where, $orderBy = '') {
    $sql = "SELECT * FROM $table WHERE $where";
    if ($orderBy) {
        $sql .= " ORDER BY $orderBy";
    }
    $sql .= " LIMIT 1";
    
    $stmt = executeQuery($sql);
    return $stmt->fetch();
}

function insertRow($table, $data) {
    $fields = array_keys($data);
    $placeholders = array_map(function ($field) {
        return ":$field";
    }, $fields);
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = executeQuery($sql, $data);
    return $stmt->rowCount() > 0 ? getDbConnection()->lastInsertId() : false;
}

function updateRow($table, $data, $where) {
    $fields = array_keys($data);
    $setClause = array_map(function ($field) {
        return "$field = :$field";
    }, $fields);
    
    $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE $where";
    
    $stmt = executeQuery($sql, $data);
    return $stmt->rowCount();
}

function deleteRow($table, $where) {
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = executeQuery($sql);
    return $stmt->rowCount();
}

function beginTransaction() {
    getDbConnection()->beginTransaction();
}

function commitTransaction() {
    getDbConnection()->commit();
}

function rollbackTransaction() {
    getDbConnection()->rollBack();
} 