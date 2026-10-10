<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Heroku (JawsDB) fournit JAWSDB_URL ; sinon on utilise DB_* ou les valeurs XAMPP locales.
$dbUrl = getenv('JAWSDB_URL') ? parse_url(getenv('JAWSDB_URL')) : null;

define('DB_HOST', $dbUrl['host'] ?? (getenv('DB_HOST') ?: 'localhost'));
define('DB_NAME', isset($dbUrl['path']) ? ltrim($dbUrl['path'], '/') : (getenv('DB_NAME') ?: 'vite_gourmand'));
define('DB_USER', $dbUrl['user'] ?? (getenv('DB_USER') ?: 'root'));
define('DB_PASS', $dbUrl['pass'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));

$dbPort = $dbUrl['port'] ?? getenv('DB_PORT');
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
if ($dbPort !== false && $dbPort !== '') {
    $dsn .= ";port=" . $dbPort;
}

try {
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      
            PDO::ATTR_EMULATE_PREPARES   => false,                 
        ]
    );
} catch (PDOException $e) {
    
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die("Erreur de connexion à la base de données.");
}

// Créer token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrfToken() {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Erreur de sécurité CSRF. Veuillez réessayer.');
    }
    // Régénérer le token après utilisation (plus sécurisé)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>   