<?php
require 'db.php';
require 'auth.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$q = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

if ($q === '') {
    echo '[]';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT DISTINCT make FROM cars WHERE make LIKE ? ORDER BY make LIMIT 10'
);
$stmt->execute(['%' . $q . '%']);
$makes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(array_values($makes), JSON_UNESCAPED_UNICODE);
