<?php
require_once 'config.php';

/**
 * Fournit une instance PDO pour la connexion à la base de données.
 *
 * Établit la connexion en utilisant les constantes de configuration (DB_HOST, DB_NAME, DB_CHARSET, DB_USER, DB_PASS)
 * et conserve cette instance pour éviter des reconnections multiples. En cas d'échec de connexion, le script s'interrompt
 * en affichant un message d'erreur préfixé par "[FAILURE]".
 *
 * @return PDO Connexion PDO active.
 */
function getDbConnection()
{
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
function validateTableName($table)
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new Exception("[FAILURE] Nom de table invalide");
    }
    return $table;
}

/**
 * Exécute une requête SQL préparée via PDO et retourne le résultat.
 *
 * La requête SQL fournie est préparée et exécutée en y liant les paramètres spécifiés. En cas d'échec lors de la préparation ou de l'exécution, le script est immédiatement interrompu avec un message d'erreur préfixé par "[FAILURE]".
 *
 * @param string $sql La requête SQL à exécuter.
 * @param array $params Les valeurs à lier aux paramètres de la requête.
 * @return PDOStatement Objet PDOStatement contenant le résultat de la requête.
 */
function executeQuery($sql, $params = [])
{
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // Log détaillé de l'erreur
        $errorInfo = isset($stmt) ? $stmt->errorInfo() : $pdo->errorInfo();
        $logMessage = sprintf(
            "[FAILURE] Impossible d'executer la requete.\nSQL: %s\nParams: %s\nError: %s\nPDO Error Info: %s",
            $sql,
            print_r($params, true), // Utiliser print_r pour afficher les tableaux correctement
            $e->getMessage(),
            print_r($errorInfo, true)
        );
        error_log($logMessage); // Envoyer au log d'erreurs PHP

        // Message simplifié pour l'utilisateur final
        die("[FAILURE] Impossible d'executer la requete. Veuillez consulter les logs du serveur pour plus de détails. Message: " . $e->getMessage());
    }
}


function countTableRows($table, $where = '', $params = [])
{
    $table = validateTableName($table);

    $sql = "SELECT COUNT(*) FROM $table";
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
 * @param string $where 
 * @param string $orderBy 
 * @param int $limit 
 * @param int $offset 
 * @param array $params Paramètres pour la clause WHERE
 * @return array 
 */
function fetchAll($table, $where = '', $orderBy = '', $limit = 0, $offset = 0, $params = [])
{
    $table = validateTableName($table);

    $sql = "SELECT * FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    if ($orderBy) {
        if (is_string($orderBy) && !empty(trim($orderBy))) {
            if (preg_match('/^[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?(?:,\s*[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?)*$/', $orderBy)) {
                $sql .= " ORDER BY " . $orderBy;
            } else {
                error_log("[WARNING] Invalid characters detected in orderBy clause in fetchAll for table '$table': " . $orderBy);
            }
        } else {
            error_log("[WARNING] Invalid type or empty orderBy parameter passed to fetchAll for table '$table'. Expected string, got: " . gettype($orderBy));
        }
    }
    if ($limit) {
        $sql .= " LIMIT $limit";
        if ($offset) {
            $sql .= " OFFSET $offset";
        }
    }

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Récupère le premier enregistrement d'une table selon une condition donnée
 *
 * @param string $table Nom de la table concernée
 * @param string $where Condition SQL pour filtrer les enregistrements
 * @param array $params (Optionnel) Tableau associatif des paramètres à lier à la clause WHERE.
 * @param string $orderBy (Optionnel) Clause SQL pour ordonner les résultats.
 * @return array|false Tableau associatif représentant l'enregistrement trouvé ou false si aucun enregistrement ne correspond.
 */
function fetchOne($table, $where, $params = [], $orderBy = '')
{
    $table = validateTableName($table);

    $sql = "SELECT * FROM $table WHERE $where";
    if ($orderBy) {
        if (is_string($orderBy) && !empty(trim($orderBy))) {
            if (preg_match('/^[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?(?:,\s*[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?)*$/', $orderBy)) {
                $sql .= " ORDER BY " . $orderBy;
            } else {
                error_log("[WARNING] Invalid characters detected in orderBy clause in fetchOne for table '$table': " . $orderBy);
            }
        } else {
            error_log("[WARNING] Invalid type or empty orderBy parameter passed to fetchOne for table '$table'. Expected string, got: " . gettype($orderBy));
        }
    }
    $sql .= " LIMIT 1";

    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Insère une nouvelle ligne dans la table spécifiée
 *
 * @param string $table Nom de la table dans laquelle insérer la nouvelle ligne
 * @param array $data Tableau associatif des colonnes et valeurs à insérer
 * @return bool true si la ligne a été insérée, false sinon
 */
function insertRow($table, $data)
{
    $table = validateTableName($table);

    $fields = array_keys($data);
    $placeholders = array_map(function ($field) {
        return ":$field";
    }, $fields);

    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = executeQuery($sql, $data);
    // Retourner true si au moins une ligne a été insérée, false sinon.
    // Ne pas se baser sur lastInsertId() pour les tables sans auto-increment.
    return $stmt->rowCount() > 0;
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
function updateRow($table, $data, $where, $whereParams = [])
{
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
function deleteRow($table, $where, $params = [])
{
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
function beginTransaction()
{
    getDbConnection()->beginTransaction();
}

/**
 * Valide une transaction SQL en cours
 * 
 * @return void
 */
function commitTransaction()
{
    getDbConnection()->commit();
}

/**
 * Annule une transaction SQL en cours
 * 
 * @return void
 */
function rollbackTransaction()
{
    getDbConnection()->rollBack();
}
