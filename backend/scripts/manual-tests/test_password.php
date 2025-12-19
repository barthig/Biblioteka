<?php

$hash = '$2y$10$wqKz9LAIwQ4jB0W1WzMZWeRuXzOv/Q4L3M5tQY5hI4CBmHPREvi/a';
$password = 'Admin1234';

echo "Testing password verification:\n";
echo "Hash: $hash\n";
echo "Password: $password\n";
echo "Match: " . (password_verify($password, $hash) ? 'YES' : 'NO') . "\n";

// Wygeneruj nowy hash
$newHash = password_hash($password, PASSWORD_BCRYPT);
echo "\nNew hash: $newHash\n";
echo "New match: " . (password_verify($password, $newHash) ? 'YES' : 'NO') . "\n";
