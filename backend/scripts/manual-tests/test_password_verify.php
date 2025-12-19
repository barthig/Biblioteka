<?php
$hash = '$2y$13$Xhz8P0vQ.zKL9XGkYPfcReZE8QYj1MJOvGk.W7nRZ7I8x5YqzLVnm';

$passwords = [
    'password123',
    'Password123!',
    'admin',
    'admin123',
    'biblioteka',
    'biblioteka123'
];

foreach ($passwords as $password) {
    $match = password_verify($password, $hash);
    printf("%-20s : %s\n", $password, $match ? 'MATCH ✓' : 'NO MATCH');
}
