<?php
require 'db.php';
require 'auth.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM cars WHERE id = ?');
$stmt->execute([$id]);
$car = $stmt->fetch();

if (!$car) {
    http_response_code(404);
    exit('Car not found.');
}

$errors       = [];
$field_errors = [];
$values       = [
    'make'  => $car['make'],
    'model' => $car['model'],
    'year'  => $car['year'],
    'color' => $car['color'],
    'price' => $car['price'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $values['make']  = trim($_POST['make']  ?? '');
    $values['model'] = trim($_POST['model'] ?? '');
    $values['year']  = trim($_POST['year']  ?? '');
    $values['color'] = trim($_POST['color'] ?? '');
    $values['price'] = trim($_POST['price'] ?? '');

    if ($values['make'] === '') {
        $errors[]       = 'Make is required.';
        $field_errors[] = 'make';
    }
    if ($values['model'] === '') {
        $errors[]       = 'Model is required.';
        $field_errors[] = 'model';
    }
    if (!ctype_digit($values['year']) || (int)$values['year'] < 1900 || (int)$values['year'] > 2100) {
        $errors[]       = 'Enter a valid year (1900–2100).';
        $field_errors[] = 'year';
    }
    if ($values['color'] === '') {
        $errors[]       = 'Color is required.';
        $field_errors[] = 'color';
    }
    if (!is_numeric($values['price']) || (float)$values['price'] < 0) {
        $errors[]       = 'Enter a valid price (0 or greater).';
        $field_errors[] = 'price';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE cars SET make = ?, model = ?, year = ?, color = ?, price = ? WHERE id = ?'
        );
        $stmt->execute([
            $values['make'],
            $values['model'],
            (int)$values['year'],
            $values['color'],
            (float)$values['price'],
            $id,
        ]);
        header('Location: index.php');
        exit;
    }
}

twig_render('edit.html.twig', [
    'car_id'       => $id,
    'errors'       => $errors,
    'field_errors' => $field_errors,
    'values'       => $values,
]);

