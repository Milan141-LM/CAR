<?php
require 'db.php';
require 'auth.php';
require 'captcha.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$errors               = [];
$field_errors         = [];
$field_error_messages = [];
$values               = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username         = trim($_POST['username']         ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         = $_POST['password']         ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $captcha_answer   = $_POST['captcha_answer']   ?? '';

    $values['username'] = $username;
    $values['email']    = $email;

    if ($username === '') {
        $field_errors[]                      = 'username';
        $field_error_messages['username']    = 'Username is required.';
    } elseif (!preg_match('/^\w{3,50}$/', $username)) {
        $field_errors[]                      = 'username';
        $field_error_messages['username']    = 'Username must be 3–50 characters and contain only letters, digits, or underscores.';
    }

    if ($email === '') {
        $field_errors[]                   = 'email';
        $field_error_messages['email']    = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors[]                   = 'email';
        $field_error_messages['email']    = 'Enter a valid email address.';
    } elseif (mb_strlen($email) > 255) {
        $field_errors[]                   = 'email';
        $field_error_messages['email']    = 'Email address is too long.';
    }

    if ($password === '') {
        $field_errors[]                      = 'password';
        $field_error_messages['password']    = 'Password is required.';
    } elseif (mb_strlen($password) < 8) {
        $field_errors[]                      = 'password';
        $field_error_messages['password']    = 'Password must be at least 8 characters.';
    }

    if ($password !== '' && $password !== $password_confirm) {
        $field_errors[]                             = 'password_confirm';
        $field_error_messages['password_confirm']   = 'Passwords do not match.';
    }

    if (!verify_captcha($captcha_answer)) {
        $field_errors[] = 'captcha_answer';
        $field_error_messages['captcha_answer'] = 'Incorrect CAPTCHA answer.';
    }

    if (!$field_errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $field_errors[]                   = 'username';
            $field_error_messages['username'] = 'That username is already taken.';
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $field_errors[]                = 'email';
            $field_error_messages['email'] = 'That email address is already registered.';
        }
    }

    if (!$field_errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)')
            ->execute([$username, $email, $hash]);

        $newId = (int)$pdo->lastInsertId();
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        reset_captcha();
        $_SESSION['user'] = [
            'id'       => $newId,
            'username' => $username,
            'email'    => $email,
        ];
        header('Location: index.php');
        exit;
    } else {
        reset_captcha();
    }
}

$captcha = get_captcha();

twig_render('register.html.twig', [
    'errors'               => $errors,
    'field_errors'         => $field_errors,
    'field_error_messages' => $field_error_messages,
    'values'               => $values,
    'captcha'              => $captcha,
]);
