function executeQuery($sql, $params = []) {
try {
// === AJOUT LOGGING ===
error_log("[DEBUG] executeQuery - SQL: " . $sql);
error_log("[DEBUG] executeQuery - Params: " . print_r($params, true));
// === FIN AJOUT ===
$pdo = getDbConnection();
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
return $stmt;
} catch (PDOException $e) {
die("[FAILURE] Impossible d'executer la requete: " . $e->getMessage());
}
}