<?php
require 'db.php';
require 'auth.php';
require 'captcha.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Dummy bcrypt hash, used so unknown usernames still cost one password_verify()
// (keeps response time similar whether or not the account exists).
const DUMMY_HASH = '$2y$10$usesomesillystringforeusesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi';

$errors               = [];
$field_errors         = [];
$field_error_messages = [];
$values               = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username       = trim($_POST['username'] ?? '');
    $password       = $_POST['password'] ?? '';
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
            // Burn the same time a real check would take, then fail generically.
            password_verify($password, DUMMY_HASH);
            $errors[] = 'Invalid credentials.';
            reset_captcha();
        } else {
            // ---- Check lock status ----
            $isLocked = false;
            if (!empty($user['locked_until'])) {
                try {
                    $lockedUntil = new DateTime($user['locked_until']);
                    if (new DateTime() < $lockedUntil) {
                        $isLocked = true;
                    } else {
                        // Lock has expired: clear it so one typo doesn't re-lock immediately.
                        $pdo->prepare('UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?')
                            ->execute([$user['id']]);
                        $user['login_attempts'] = 0;
                        $user['locked_until']   = null;
                    }
                } catch (\Exception $e) {
                    $isLocked = false;
                }
            }

            if ($isLocked) {
                $errors[] = 'Account is temporarily locked due to too many failed attempts. Please try again later.';
                reset_captcha();

            } elseif (!password_verify($password, $user['password_hash'])) {
                // ---- Failed login: increment atomically in SQL ----
                $pdo->prepare('UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?')
                    ->execute([$user['id']]);

                $check = $pdo->prepare('SELECT login_attempts FROM users WHERE id = ?');
                $check->execute([$user['id']]);
                $attempts = (int)$check->fetchColumn();

                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                    $newLock = (new DateTime())
                        ->modify('+' . LOCKOUT_MINUTES . ' minutes')
                        ->format('Y-m-d H:i:s');
                    $pdo->prepare('UPDATE users SET locked_until = ? WHERE id = ?')
                        ->execute([$newLock, $user['id']]);
                    $errors[] = 'Too many failed attempts. Your account has been locked for ' . LOCKOUT_MINUTES . ' minutes.';
                } else {
                    $errors[] = 'Invalid credentials.';
                }
                reset_captcha();

            } else {
                // ---- Successful login: reset counter to 0 (was 5 before) ----
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