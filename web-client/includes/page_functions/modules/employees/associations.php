<?php
require_once __DIR__ . '/../../../../../shared/web-client/db.php';

function getAssociationsList() {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT id, nom, resume, histoire FROM associations ORDER BY nom ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $associations = [];
    foreach ($rows as $row) {
        $associations[] = [
            'id' => $row['id'],
            'nom' => $row['nom'],
            'logo' => 'https://cdn-icons-png.flaticon.com/512/3062/3062634.png', // valeur par défaut
            'resume' => $row['resume'] ?? '',
            'histoire' => $row['histoire'] ?? '',
            'projets' => []
        ];
    }
    return $associations;
}
