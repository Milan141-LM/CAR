
<?php

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'mi-linux.wlv.ac.uk');
define('DB_NAME', 'db2410675');
define('DB_USER', '2410675');
define('DB_PASS', 'PROXY@68#[68]');
define('DB_CHAR', 'utf8mb4');

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Please check db.php settings.');
}

// Load Composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

// Twig configuration
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');

$twig = new \Twig\Environment($loader, [
    'autoescape'       => 'html',
    'strict_variables' => false,
]);

$twig->addFunction(new \Twig\TwigFunction('csrf_token', 'csrf_token'));

// Render Twig templates
function twig_render(string $template, array $vars = []): void
{
    global $twig;

    $vars['current_user'] = $_SESSION['user'] ?? null;

    echo $twig->render($template, $vars);
}

// Generate CSRF token
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

// Escape HTML output
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
