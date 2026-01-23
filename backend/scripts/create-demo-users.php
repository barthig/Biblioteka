#!/usr/bin/env php
<?php
// Simple script to create demo users via Symfony console

require __DIR__ . '/../vendor/autoload.php';

use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

$kernel = new App\Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$demoUsers = [
    [
        'email' => 'admin@biblioteka.pl',
        'name' => 'Administrator',
        'roles' => ['ROLE_ADMIN'],
        'password' => 'admin123'
    ],
    [
        'email' => 'bibliotekarz@biblioteka.pl',
        'name' => 'Bibliotekarz Jan',
        'roles' => ['ROLE_LIBRARIAN'],
        'password' => 'lib123'
    ],
    [
        'email' => 'czytelnik@example.com',
        'name' => 'Anna Kowalska',
        'roles' => ['ROLE_USER'],
        'password' => 'user123'
    ],
];

$passwordHasher = $container->get('security.user_password_hasher');

foreach ($demoUsers as $userData) {
    // Check if user exists
    $existing = $em->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
    if ($existing) {
        echo "User {$userData['email']} already exists, skipping.\n";
        continue;
    }

    $user = new User();
    $user->setEmail($userData['email']);
    $user->setName($userData['name']);
    $user->setRoles($userData['roles']);
    
    $hashedPassword = $passwordHasher->hashPassword($user, $userData['password']);
    $user->setPassword($hashedPassword);
    
    $em->persist($user);
    echo "Created user: {$userData['email']} (password: {$userData['password']})\n";
}

$em->flush();
echo "\nDemo users created successfully!\n";
