<?php
require 'db.php';
require 'auth.php';
require_auth();

$errors       = [];
$field_errors = [];
$values       = ['make' => '', 'model' => '', 'year' => date('Y'), 'color' => '', 'price' => ''];

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
            'INSERT INTO cars (make, model, year, color, price) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $values['make'],
            $values['model'],
            (int)$values['year'],
            $values['color'],
            (float)$values['price'],
        ]);
        header('Location: index.php');
        exit;
    }
}

twig_render('add.html.twig', [
    'errors'       => $errors,
    'field_errors' => $field_errors,
    'values'       => $values,
]);

