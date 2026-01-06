<?php
// Test bezpośredniego logowania
require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

// Połącz się z bazą
$conn = DriverManager::getConnection([
    'driver' => 'pdo_pgsql',
    'host' => 'db',
    'port' => 5432,
    'dbname' => 'biblioteka_dev',
    'user' => 'biblioteka',
    'password' => 'biblioteka_secure_2024',
]);

$email = 'admin@biblioteka.pl';
$password = 'Admin1234';

// Pobierz użytkownika
$sql = "SELECT id, email, password, roles, verified, blocked, pending_approval FROM app_user WHERE email = ?";
$user = $conn->fetchAssociative($sql, [$email]);

if (!$user) {
    echo "[ERR] Użytkownik nie znaleziony\n";
    exit(1);
}

echo "[OK] Użytkownik znaleziony: {$user['email']}\n";
echo "   ID: {$user['id']}\n";
echo "   Verified: " . ($user['verified'] ? 'TAK' : 'NIE') . "\n";
echo "   Blocked: " . ($user['blocked'] ? 'TAK' : 'NIE') . "\n";
echo "   Pending: " . ($user['pending_approval'] ? 'TAK' : 'NIE') . "\n";

// Weryfikuj hasło
if (password_verify($password, $user['password'])) {
    echo "[OK] Hasło poprawne!\n";
} else {
    echo "[ERR] Hasło niepoprawne\n";
    exit(1);
}

echo "\nNOTE Logowanie powinno działać!\n";
