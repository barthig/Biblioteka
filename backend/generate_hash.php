<?php
// Generate hash for 'password123'
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "Cost: 10 (default)\n";

// Verify it works
if (password_verify($password, $hash)) {
    echo "✓ Verification successful\n";
}
