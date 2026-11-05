<?php
require 'db.php';
require 'auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare('DELETE FROM cars WHERE id = ?');
    $stmt->execute([(int)$_POST['delete_id']]);
    header('Location: index.php');
    exit;
}

$where  = [];
$params = [];

$filters = [
    'make'  => trim($_GET['make']  ?? ''),
    'model' => trim($_GET['model'] ?? ''),
    'year'  => trim($_GET['year']  ?? ''),
    'color' => trim($_GET['color'] ?? ''),
    'price' => trim($_GET['price'] ?? ''),
];

if ($filters['make'] !== '') {
    $where[]  = 'make LIKE ?';
    $params[] = '%' . $filters['make'] . '%';
}
if ($filters['model'] !== '') {
    $where[]  = 'model LIKE ?';
    $params[] = '%' . $filters['model'] . '%';
}
if ($filters['year'] !== '') {
    $where[]  = 'year = ?';
    $params[] = (int)$filters['year'];
}
if ($filters['color'] !== '') {
    $where[]  = 'color LIKE ?';
    $params[] = '%' . $filters['color'] . '%';
}
if ($filters['price'] !== '') {
    $where[]  = 'price <= ?';
    $params[] = (float)$filters['price'];
}

$sql = 'SELECT * FROM cars';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

twig_render('index.html.twig', [
    'cars'    => $cars,
    'filters' => $filters,
]);

