<?php
require 'db.php';
require 'auth.php';
require 'captcha.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$errors       = [];
$field_errors = [];
$field_error_messages = [];
$values       = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_answer = $_POST['captcha_answer'] ?? '';

    if ($username === '') {
        $field_errors[] = 'username';
    }
    if ($password === '') {
        $field_errors[] = 'password';
    }
    if (!verify_captcha($captcha_answer)) {
        $field_errors[] = 'captcha_answer';
        $field_error_messages['captcha_answer'] = 'Incorrect CAPTCHA answer.';
    }

    $values['username'] = $username;

    if (!$field_errors) {
        $stmt = $pdo->prepare(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Invalid credentials.';
            reset_captcha();
        } else {
            $isLocked = false;
            if ($user['locked_until'] !== null) {
                try {
                    $isLocked = new DateTime() < new DateTime($user['locked_until']);
                } catch (\Exception $e) {
                    $isLocked = false;
                }
            }
            if ($isLocked) {
                $errors[] = 'Account is temporarily locked due to too many failed attempts. Please try again later.';
                reset_captcha();
            } elseif (!password_verify($password, $user['password_hash'])) {
                $attempts = (int)$user['login_attempts'] + 1;
                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                    $lockedUntil = (new DateTime())
                        ->modify('+' . LOCKOUT_MINUTES . ' minutes')
                        ->format('Y-m-d H:i:s');
                    $pdo->prepare('UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?')
                        ->execute([$attempts, $lockedUntil, $user['id']]);
                    $errors[] = 'Too many failed attempts. Your account has been locked for ' . LOCKOUT_MINUTES . ' minutes.';
                } else {
                    $pdo->prepare('UPDATE users SET login_attempts = ? WHERE id = ?')
                        ->execute([$attempts, $user['id']]);
                    $errors[] = 'Invalid credentials.';
                }
                reset_captcha();
            } else {
                $pdo->prepare('UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?')
                    ->execute([$user['id']]);

                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);
                reset_captcha();
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'email'    => $user['email'],
                ];
                header('Location: index.php');
                exit;
            }
        }
    } else {
        reset_captcha();
    }
}

$captcha = get_captcha();

twig_render('login.html.twig', [
    'errors'               => $errors,
    'field_errors'         => $field_errors,
    'field_error_messages' => $field_error_messages,
    'values'               => $values,
    'captcha'              => $captcha,
]);
