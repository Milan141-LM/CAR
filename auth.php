<?php

function require_auth(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',   15);
